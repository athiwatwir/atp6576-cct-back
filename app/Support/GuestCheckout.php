<?php

namespace App\Support;

use App\Enums\AccountStatus;
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
     * @param  array{email: string, name?: ?string, phone?: ?string}  $customer
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $shipping
     * @return array{order: Order, account_created: bool}
     */
    public function place(array $customer, array $items, ?string $couponCode, ?string $notes, array $shipping): array
    {
        $email = $this->normalizeEmail($customer['email']);
        $created = false;

        // ตรวจตะกร้า ที่อยู่ และคูปองก่อน จะได้ไม่สร้างบัญชีทิ้งไว้เมื่อรายการไม่ผ่าน
        $this->checkout->quote($this->quoteCustomer($email), $items, $couponCode);

        $student = DB::transaction(function () use ($email, $customer, &$created) {
            $existing = $this->existingUser($email, lock: true);

            if ($existing) {
                $this->assertUsable($existing);

                return $existing;
            }

            $created = true;

            $user = User::query()->forceCreate([
                'name' => filled($customer['name'] ?? null) ? trim((string) $customer['name']) : Str::before($email, '@'),
                'email' => $email,
                'phone' => filled($customer['phone'] ?? null) ? trim((string) $customer['phone']) : null,
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

        $order->forceFill(['guest_token' => Str::random(48)])->save();

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

    public function loginUrl(): string
    {
        return $this->studentAppUrl().'/login';
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

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function studentAppUrl(): string
    {
        $explicit = config('services.storefront.app_url');
        $source = is_string($explicit) && $explicit !== ''
            ? $explicit
            : (string) config('services.storefront.frontend_url');

        foreach (explode(',', $source) as $origin) {
            $origin = trim($origin);

            if ($origin !== '' && $origin !== '*') {
                return rtrim($origin, '/');
            }
        }

        return rtrim((string) config('app.url'), '/');
    }
}
