<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ซื้อโดยไม่ต้องสมัครสมาชิกก่อน: ใช้อีเมลหาบัญชีนักเรียน ถ้ายังไม่มีระบบสร้างให้และส่งรหัสผ่านทางอีเมล
 * บัญชีที่มีอยู่แล้วไม่ถูกเปลี่ยนรหัสผ่าน และไม่มีการออก token เข้าสู่ระบบจากอีเมลอย่างเดียว
 */
class GuestCheckout
{
    public function __construct(
        private readonly StorefrontCheckout $checkout,
        private readonly MailService $mail,
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
     * @return array{order: Order, account_created: bool, email_sent: bool}
     */
    public function place(array $customer, array $items, ?string $couponCode, ?string $notes, array $shipping): array
    {
        $email = $this->normalizeEmail($customer['email']);
        $password = null;
        $created = false;

        // ตรวจตะกร้า ที่อยู่ และคูปองก่อน จะได้ไม่สร้างบัญชีทิ้งไว้เมื่อรายการไม่ผ่าน
        $this->checkout->quote($this->quoteCustomer($email), $items, $couponCode);

        $student = DB::transaction(function () use ($email, $customer, &$password, &$created) {
            $existing = $this->existingUser($email, lock: true);

            if ($existing) {
                $this->assertUsable($existing);

                return $existing;
            }

            $password = Str::password(10, symbols: false);
            $created = true;

            $user = User::query()->create([
                'name' => filled($customer['name'] ?? null) ? trim((string) $customer['name']) : Str::before($email, '@'),
                'email' => $email,
                'phone' => filled($customer['phone'] ?? null) ? trim((string) $customer['phone']) : null,
                'password' => $password,
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

        $sent = $created
            ? $this->sendNewAccountMail($student, (string) $password, $order)
            : $this->sendExistingAccountMail($student, $order);

        return [
            'order' => $order->refresh()->load(['items', 'latestPayment.slips']),
            'account_created' => $created,
            'email_sent' => $sent,
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

    private function sendNewAccountMail(User $student, string $password, Order $order): bool
    {
        try {
            $this->mail->sendCustomer(
                to: $student,
                subject: 'ข้อมูลบัญชีและคำสั่งซื้อ #'.$order->order_no,
                view: 'emails.customer.guest-account',
                data: [
                    'password' => $password,
                    'orderNo' => $order->order_no,
                    'amount' => '฿'.number_format((float) $order->total_amount, 2),
                    'items' => $order->items->pluck('item_name')->filter()->values()->all(),
                    'actionUrl' => $this->loginUrl(),
                    'actionText' => 'เข้าสู่ระบบ',
                ],
                queue: false,
            );

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function sendExistingAccountMail(User $student, Order $order): bool
    {
        try {
            $this->mail->sendCustomerNotification(
                to: $student,
                subject: 'มีคำสั่งซื้อใหม่ #'.$order->order_no,
                message: 'มีการสั่งซื้อด้วยอีเมลนี้ เลขที่คำสั่งซื้อ '.$order->order_no
                    .' ยอด ฿'.number_format((float) $order->total_amount, 2)
                    .' เข้าสู่ระบบด้วยบัญชีเดิมเพื่อดูคำสั่งซื้อและเข้าเรียน ถ้าลืมรหัสผ่านใช้เมนูลืมรหัสผ่านในหน้าเข้าสู่ระบบ'
                    .' ถ้าคุณไม่ได้สั่งซื้อรายการนี้ ไม่ต้องชำระเงิน คำสั่งซื้อจะไม่มีผล',
                actionUrl: $this->loginUrl(),
                actionText: 'เข้าสู่ระบบ',
                queue: false,
            );

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
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
