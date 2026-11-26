<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Support\Collection;
use Throwable;

class AdminNotifier
{
    public static function studentRegistered(User $student): void
    {
        $name = $student->name ?: ($student->email ?: $student->phone ?: 'นักเรียนใหม่');

        self::notifyBackend(
            type: 'student.registered',
            title: 'มีการสมัครสมาชิกใหม่',
            message: $name.' สมัครเข้าใช้งาน',
            data: [
                'student_id' => $student->id,
                'url' => route('students.show', $student),
            ],
            actionText: 'ดูนักเรียน',
        );
    }

    public static function orderCreated(Order $order): void
    {
        if ($order->gift_for_order_id) {
            return;
        }

        $actor = auth()->user();
        if ($actor instanceof User && $actor->canAccessBackend()) {
            return;
        }

        $customer = $order->user?->name
            ?: $order->shipping_name
            ?: 'ลูกค้า';

        self::notifyBackend(
            type: 'order.created',
            title: 'มีออเดอร์ใหม่',
            message: $customer.' สั่งซื้อ '.$order->order_no.' (฿'.number_format((float) $order->total_amount, 2).')',
            data: [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'url' => $order->adminUrl(),
            ],
            actionText: 'เปิดออเดอร์',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function notifyBackend(
        string $type,
        string $title,
        ?string $message,
        array $data = [],
        string $actionText = 'เปิดดู',
    ): void {
        self::storeInApp($type, $title, $message, $data);
        self::emailConfiguredRecipients($title, $message, $data, $actionText);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function storeInApp(string $type, string $title, ?string $message, array $data): void
    {
        /** @var Collection<int, int|string> $ids */
        $ids = User::query()
            ->where('status', AccountStatus::Active->value)
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', User::BACKEND_ROLES))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $now = now();
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);

        $rows = $ids->map(fn ($id) => [
            'user_id' => $id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $payload === false ? null : $payload,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 100) as $chunk) {
            Notification::query()->insert($chunk);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function emailConfiguredRecipients(
        string $title,
        ?string $message,
        array $data,
        string $actionText,
    ): void {
        $emails = SystemSettings::notificationEmails();

        if ($emails === []) {
            return;
        }

        $mail = app(MailService::class);
        $url = is_string($data['url'] ?? null) ? $data['url'] : null;

        foreach ($emails as $email) {
            try {
                $mail->sendStaffNotification(
                    to: $email,
                    subject: $title,
                    message: $message ?? $title,
                    actionUrl: $url,
                    actionText: $actionText,
                );
            } catch (Throwable) {
                // ไม่ให้การส่งอีเมลล้มทำให้สมัคร/สั่งซื้อล้ม
            }
        }
    }
}
