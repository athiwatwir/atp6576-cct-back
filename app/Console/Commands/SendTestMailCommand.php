<?php

namespace App\Console\Commands;

use App\Enums\MailAudience;
use App\Services\Mail\MailService;
use Illuminate\Console\Command;

class SendTestMailCommand extends Command
{
    protected $signature = 'mail:test
                            {email : Recipient email address}
                            {--audience=staff : staff|customer}
                            {--type=notification : notification|password|forgot-password|payment|announcement}
                            {--sync : Send immediately without queue}';

    protected $description = 'Send a test email using the system MailService layouts';

    public function handle(MailService $mailService): int
    {
        $email = (string) $this->argument('email');
        $audience = MailAudience::tryFrom((string) $this->option('audience')) ?? MailAudience::Staff;
        $type = (string) $this->option('type');
        $queue = ! $this->option('sync');

        match ([$audience, $type]) {
            [MailAudience::Staff, 'password'] => $mailService->sendStaffPassword(
                to: $email,
                password: 'TempPass@123',
                queue: $queue,
            ),
            [MailAudience::Staff, 'notification'] => $mailService->sendStaffNotification(
                to: $email,
                subject: 'ทดสอบแจ้งเตือนพนักงาน',
                message: "นี่คืออีเมลทดสอบสำหรับ layout พนักงาน\nระบบ Click Class Tutor พร้อมใช้งาน",
                actionUrl: route('login'),
                actionText: 'เข้าสู่ระบบ',
                queue: $queue,
            ),
            [MailAudience::Customer, 'password'] => $mailService->sendCustomerPassword(
                to: $email,
                password: 'Student@123',
                queue: $queue,
            ),
            [MailAudience::Customer, 'forgot-password'] => $mailService->sendCustomerForgotPassword(
                to: $email,
                resetUrl: config('app.url').'/reset-password/demo-token',
                queue: $queue,
            ),
            [MailAudience::Customer, 'payment'] => $mailService->sendCustomerPaymentStatus(
                to: $email,
                status: 'paid',
                orderNo: 'ORD-DEMO-001',
                amount: '1,500.00 บาท',
                message: 'การชำระเงินของคุณสำเร็จแล้ว ระบบได้เปิดสิทธิ์การเรียนให้เรียบร้อย',
                statusLabel: 'ชำระเงินสำเร็จ',
                actionUrl: config('app.url'),
                queue: $queue,
            ),
            [MailAudience::Customer, 'announcement'] => $mailService->sendCustomerAnnouncement(
                to: $email,
                subject: 'ข่าวสารทดสอบ',
                message: "มีคอร์สใหม่เปิดให้ลงทะเบียนแล้ว\nเข้ามาดูรายละเอียดได้เลย",
                queue: $queue,
            ),
            default => $mailService->sendCustomerNotification(
                to: $email,
                subject: 'ทดสอบแจ้งเตือนนักเรียน',
                message: 'นี่คืออีเมลทดสอบสำหรับ layout ลูกค้า / นักเรียน',
                queue: $queue,
            ),
        };

        $this->info("Queued/sent {$audience->value} ({$type}) mail to {$email}");

        if ($queue) {
            $this->comment('ใช้ queue อยู่ อย่าลืมรัน php artisan queue:work');
        }

        return self::SUCCESS;
    }
}
