<?php

namespace App\Services\Mail;

use App\Enums\MailAudience;
use App\Mail\TemplatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Generic send for reuse across the whole system.
     *
     * @param  array<string, mixed>  $data
     */
    public function send(
        MailAudience $audience,
        User|string $to,
        string $subject,
        string $view,
        array $data = [],
        bool $queue = true,
    ): void {
        [$email, $name] = $this->resolveRecipient($to);

        $mailable = new TemplatedMail(
            audience: $audience,
            mailSubject: $subject,
            viewName: $view,
            data: array_merge($data, [
                'mailSubject' => $subject,
                'email' => $email,
            ]),
            recipientName: $name,
        );

        $pending = Mail::to($email, $name);

        if ($queue) {
            $pending->queue($mailable);

            return;
        }

        $pending->send($mailable);
    }

    public function sendNow(
        MailAudience $audience,
        User|string $to,
        string $subject,
        string $view,
        array $data = [],
    ): void {
        $this->send($audience, $to, $subject, $view, $data, queue: false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendStaff(
        User|string $to,
        string $subject,
        string $view,
        array $data = [],
        bool $queue = true,
    ): void {
        $this->send(MailAudience::Staff, $to, $subject, $view, $data, $queue);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendCustomer(
        User|string $to,
        string $subject,
        string $view,
        array $data = [],
        bool $queue = true,
    ): void {
        $this->send(MailAudience::Customer, $to, $subject, $view, $data, $queue);
    }

    public function sendStaffPassword(
        User|string $to,
        string $password,
        ?string $loginUrl = null,
        bool $queue = true,
    ): void {
        $loginUrl ??= route('login');

        $this->sendStaff(
            to: $to,
            subject: 'ข้อมูลเข้าใช้งานระบบหลังบ้าน',
            view: 'emails.staff.password',
            data: [
                'password' => $password,
                'actionUrl' => $loginUrl,
                'actionText' => 'เข้าสู่ระบบ',
            ],
            queue: $queue,
        );
    }

    public function sendStaffNotification(
        User|string $to,
        string $subject,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $heading = null,
        bool $queue = true,
    ): void {
        $this->sendStaff(
            to: $to,
            subject: $subject,
            view: 'emails.staff.notification',
            data: [
                'heading' => $heading ?? $subject,
                'message' => $message,
                'actionUrl' => $actionUrl,
                'actionText' => $actionText,
            ],
            queue: $queue,
        );
    }

    public function sendCustomerPassword(
        User|string $to,
        string $password,
        ?string $loginUrl = null,
        bool $queue = true,
    ): void {
        $this->sendCustomer(
            to: $to,
            subject: 'ยินดีต้อนรับสู่ '.config('app.name'),
            view: 'emails.customer.password',
            data: [
                'password' => $password,
                'actionUrl' => $loginUrl ?? config('app.url'),
                'actionText' => 'เริ่มเรียนเลย',
            ],
            queue: $queue,
        );
    }

    public function sendCustomerForgotPassword(
        User|string $to,
        string $resetUrl,
        int $expiresMinutes = 60,
        bool $queue = true,
    ): void {
        $this->sendCustomer(
            to: $to,
            subject: 'รีเซ็ตรหัสผ่าน '.config('app.name'),
            view: 'emails.customer.forgot-password',
            data: [
                'actionUrl' => $resetUrl,
                'actionText' => 'ตั้งรหัสผ่านใหม่',
                'expiresMinutes' => $expiresMinutes,
            ],
            queue: $queue,
        );
    }

    public function sendCustomerPaymentStatus(
        User|string $to,
        string $status,
        ?string $orderNo = null,
        ?string $amount = null,
        ?string $message = null,
        ?string $statusLabel = null,
        ?string $actionUrl = null,
        bool $queue = true,
    ): void {
        $this->sendCustomer(
            to: $to,
            subject: 'อัปเดตสถานะการชำระเงิน'.($orderNo ? " #{$orderNo}" : ''),
            view: 'emails.customer.payment-status',
            data: [
                'status' => $status,
                'statusLabel' => $statusLabel ?? $status,
                'orderNo' => $orderNo,
                'amount' => $amount,
                'message' => $message,
                'actionUrl' => $actionUrl,
                'actionText' => 'ดูรายละเอียดคำสั่งซื้อ',
            ],
            queue: $queue,
        );
    }

    public function sendCustomerAnnouncement(
        User|string $to,
        string $subject,
        string $message,
        ?string $heading = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        bool $queue = true,
    ): void {
        $this->sendCustomer(
            to: $to,
            subject: $subject,
            view: 'emails.customer.announcement',
            data: [
                'heading' => $heading ?? $subject,
                'message' => $message,
                'actionUrl' => $actionUrl,
                'actionText' => $actionText ?? 'อ่านเพิ่มเติม',
            ],
            queue: $queue,
        );
    }

    public function sendCustomerNotification(
        User|string $to,
        string $subject,
        string $message,
        ?string $heading = null,
        ?string $actionUrl = null,
        ?string $actionText = null,
        bool $queue = true,
    ): void {
        $this->sendCustomer(
            to: $to,
            subject: $subject,
            view: 'emails.customer.notification',
            data: [
                'heading' => $heading ?? $subject,
                'message' => $message,
                'actionUrl' => $actionUrl,
                'actionText' => $actionText,
            ],
            queue: $queue,
        );
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function resolveRecipient(User|string $to): array
    {
        if ($to instanceof User) {
            return [(string) $to->email, $to->name];
        }

        return [$to, null];
    }
}
