<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ซื้อโดยไม่ต้องสมัครสมาชิกก่อน: ใช้อีเมลหาบัญชีนักเรียน ถ้ายังไม่มีระบบสร้างให้
 * ยังไม่ส่งอีเมลใด ๆ ตอนสั่งซื้อ บัญชีใหม่ได้รหัสผ่านในอีเมล "ผลการสั่งซื้อ" หลังชำระเงินสำเร็จ (StudentPurchase)
 * บัญชีที่มีอยู่แล้วไม่ถูกเปลี่ยนรหัสผ่าน และไม่มีการออก token เข้าสู่ระบบจากอีเมลอย่างเดียว
 */
class GuestCheckout
{
    /** จำนวนวันหลังชำระเงินที่ยังเข้าสู่ระบบจากหน้าออเดอร์ได้โดยไม่ต้องใช้รหัสผ่าน */
    public const AUTO_LOGIN_DAYS = 7;

    public function __construct(
        private readonly StorefrontCheckout $checkout,
    ) {}

    /**
     * ใช้คำนวณยอดโดยไม่สร้างบัญชี
     */
    public function quoteCustomer(string $email): User
    {
        $existing = $this->existingUser($email);

        if ($existing) {
            $this->assertUsable($existing);

            return $existing;
        }

        return new User(['email' => $email]);
    }

    /**
     * @param  array{email: string, name?: ?string, phone?: ?string, school_name?: ?string, school_province?: ?string}  $customer
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $shipping
     * @return array{order: Order, account_created: bool}
     */
    public function place(array $customer, array $items, ?string $couponCode, ?string $notes, array $shipping): array
    {
        $email = $this->normalizeEmail($customer['email']);
        $created = false;

        // ตรวจรายการที่เลือก ที่อยู่ และคูปองก่อน จะได้ไม่สร้างบัญชีทิ้งไว้เมื่อรายการไม่ผ่าน
        $this->checkout->quote($this->quoteCustomer($email), $items, $couponCode);

        $student = DB::transaction(function () use ($email, $customer, &$created) {
            $existing = $this->existingUser($email, lock: true);

            if ($existing) {
                $this->assertUsable($existing);
                $this->fillBlankProfile($existing, $customer);

                return $existing;
            }

            $created = true;

            $user = User::query()->forceCreate([
                'name' => filled($customer['name'] ?? null) ? trim((string) $customer['name']) : Str::before($email, '@'),
                'email' => $email,
                'phone' => $this->availablePhone($customer['phone'] ?? null),
                'school_name' => $customer['school_name'] ?? null,
                'school_province' => $customer['school_province'] ?? null,
                // ไม่มีใครรู้รหัสผ่านนี้ รหัสผ่านจริงออกให้ทางอีเมลหลังชำระเงินสำเร็จ
                'password' => Str::password(40),
                'credentials_pending_at' => now(),
                'status' => AccountStatus::Active->value,
            ]);
            $user->roles()->attach(Role::query()->where('name', 'student')->firstOrFail()->id);

            return $user;
        });

        try {
            $order = $this->checkout->place($student, $items, $couponCode, $notes, $shipping);
        } catch (\Throwable $exception) {
            if ($created) {
                $student->roles()->detach();
                $student->forceDelete();
            }

            throw $exception;
        }

        $order->forceFill(['guest_token' => Str::random(48), 'guest_account_created' => $created])->save();

        if ($created) {
            AdminNotifier::studentRegistered($student);
        }

        Audit::log(
            'storefront.guest_order',
            $order,
            description: ($created ? 'สร้างบัญชีและ' : '').'สั่งซื้อแบบไม่เข้าสู่ระบบ '.$order->order_no,
            new: ['email' => $email, 'account_created' => $created],
        );

        return [
            'order' => $order->refresh()->load(['items', 'latestPayment.slips']),
            'account_created' => $created,
        ];
    }

    public function findByToken(string $token): ?Order
    {
        if (strlen($token) !== 48) {
            return null;
        }

        return Order::query()->with(['items', 'latestPayment.slips', 'user'])->where('guest_token', $token)->first();
    }

    /**
     * เข้าสู่ระบบจากออเดอร์ guest ได้เฉพาะบัญชีที่ออเดอร์นี้สร้างขึ้นเอง หลังชำระเงินแล้ว และภายในเวลาที่กำหนด
     * บัญชีที่มีอยู่ก่อนต้องใช้รหัสผ่าน เพราะใครก็พิมพ์อีเมลของคนอื่นตอนสั่งซื้อได้
     */
    public function canAutoLogin(Order $order): bool
    {
        $user = $order->user;

        return $order->guest_account_created
            && $order->payment_status === PaymentStatus::Paid->value
            && $order->paid_at !== null
            && $order->paid_at->greaterThan(now()->subDays(self::AUTO_LOGIN_DAYS))
            && $user !== null
            && ! $user->trashed()
            && $user->status === AccountStatus::Active->value
            && $user->hasRole('student')
            && ! $user->canAccessBackend();
    }

    public function autoLogin(Order $order): User
    {
        if (! $this->canAutoLogin($order)) {
            throw ValidationException::withMessages([
                'order' => 'เข้าสู่ระบบอัตโนมัติไม่ได้ กรุณาเข้าสู่ระบบด้วยอีเมลและรหัสผ่าน',
            ]);
        }

        $user = $order->user;
        $user->forceFill(['last_login_at' => now()])->save();

        Audit::log(
            'storefront.guest_auto_login',
            $order,
            description: 'เข้าสู่ระบบอัตโนมัติจากออเดอร์ '.$order->order_no,
            new: ['user_id' => $user->id],
        );

        return $user;
    }

    public function loginUrl(): string
    {
        return StudentApp::loginUrl();
    }

    private function existingUser(string $email, bool $lock = false): ?User
    {
        return User::withTrashed()
            ->where('email', $this->normalizeEmail($email))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    private function assertUsable(User $user): void
    {
        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'บัญชีของอีเมลนี้ถูกปิดใช้งาน กรุณาติดต่อแอดมิน',
            ]);
        }

        if (! $user->hasRole('student') || $user->canAccessBackend()) {
            throw ValidationException::withMessages([
                'email' => 'อีเมลนี้ใช้สั่งซื้อไม่ได้ กรุณาใช้อีเมลอื่น',
            ]);
        }

        if ($user->status !== AccountStatus::Active->value) {
            throw ValidationException::withMessages([
                'email' => 'บัญชีของอีเมลนี้ถูกปิดใช้งาน กรุณาติดต่อแอดมิน',
            ]);
        }
    }

    /**
     * ใครก็พิมพ์อีเมลของบัญชีเดิมได้ จึงเติมเฉพาะช่องที่ยังว่าง ไม่ทับข้อมูลที่นักเรียนกรอกไว้
     *
     * @param  array{school_name?: ?string, province?: ?string}  $customer
     */
    private function fillBlankProfile(User $user, array $customer): void
    {
        $changes = collect(['school_name', 'school_province'])
            ->filter(fn (string $field) => blank($user->{$field}) && filled($customer[$field] ?? null))
            ->mapWithKeys(fn (string $field) => [$field => $customer[$field]])
            ->all();

        if ($changes !== []) {
            $user->forceFill($changes)->save();
        }
    }

    /**
     * เบอร์โทรใช้เข้าสู่ระบบได้ ถ้าซ้ำกับนักเรียนคนอื่นไม่บันทึกเบอร์ลงบัญชีใหม่ แต่ยังสั่งซื้อต่อได้
     */
    private function availablePhone(?string $value): ?string
    {
        $phone = Phone::normalize($value);

        if ($phone === null || User::query()->students()->where('phone', $phone)->exists()) {
            return null;
        }

        return $phone;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
