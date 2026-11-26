<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\OrderKind;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminNotifier
{
    public static function studentRegistered(User $student): void
    {
        $name = $student->name ?: ($student->email ?: $student->phone ?: 'นักเรียนใหม่');
        $lines = [
            'มีนักเรียนสมัครสมาชิกใหม่',
            '',
            'ชื่อ: '.$name,
            'อีเมล: '.($student->email ?: '-'),
            'เบอร์โทร: '.($student->phone ?: '-'),
            'โรงเรียน: '.($student->school_name ?: '-'),
            'จังหวัดที่เรียน: '.($student->school_province ?: '-'),
            'เวลา: '.now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];

        self::notifyBackend(
            type: 'student.registered',
            title: 'มีการสมัครสมาชิกใหม่',
            message: implode("\n", $lines),
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

        $order->loadMissing(['user', 'items', 'coupon']);

        $kind = OrderKind::tryFrom((string) $order->kind);
        $isEnrollment = $kind === OrderKind::Enrollment;
        $kindLabel = $kind?->label() ?? (string) $order->kind;
        $customer = $order->user;

        $title = $isEnrollment ? 'มีการสมัครเรียนใหม่' : 'มีออเดอร์สั่งซื้อใหม่';
        $lines = [
            $title,
            '',
            'เลขที่ออเดอร์: '.($order->order_no ?: '#'.$order->id),
            'ประเภท: '.$kindLabel,
            'สถานะออเดอร์: '.($order->status_label ?? $order->status),
            'สถานะชำระเงิน: '.($order->payment_status_label ?? $order->payment_status),
            '',
            'ลูกค้า: '.($customer?->name ?: ($order->shipping_name ?: '-')),
            'อีเมล: '.($customer?->email ?: '-'),
            'เบอร์โทร: '.($customer?->phone ?: ($order->shipping_phone ?: '-')),
            'โรงเรียน: '.($customer?->school_name ?: '-'),
            'จังหวัดที่เรียน: '.($customer?->school_province ?: '-'),
        ];

        if ($order->requires_shipping) {
            $lines[] = '';
            $lines[] = 'ที่อยู่จัดส่ง:';
            $lines[] = 'ชื่อผู้รับ: '.($order->shipping_name ?: '-');
            $lines[] = 'เบอร์: '.($order->shipping_phone ?: '-');
            $lines[] = 'ที่อยู่: '.($order->shipping_full_address ?: '-');
        }

        $lines[] = '';
        $lines[] = 'รายการ:';

        if ($order->items->isEmpty()) {
            $lines[] = '- (ยังไม่มีรายการ)';
        } else {
            foreach ($order->items as $index => $item) {
                $qty = (int) $item->quantity;
                $lines[] = ($index + 1).'. '.$item->item_name
                    .' × '.$qty
                    .' = ฿'.number_format((float) $item->total_price, 2);
            }
        }

        $lines[] = '';
        $lines[] = 'ยอดรวม: ฿'.number_format((float) $order->subtotal, 2);
        if ((float) $order->discount_amount > 0) {
            $coupon = $order->coupon?->code ? ' (คูปอง '.$order->coupon->code.')' : '';
            $lines[] = 'ส่วนลด: ฿'.number_format((float) $order->discount_amount, 2).$coupon;
        }
        if ((float) $order->shipping_amount > 0) {
            $lines[] = 'ค่าส่ง: ฿'.number_format((float) $order->shipping_amount, 2);
        }
        $lines[] = 'ยอดสุทธิ: ฿'.number_format((float) $order->total_amount, 2);
        $lines[] = 'เวลา: '.($order->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i')
            ?? now()->timezone(config('app.timezone'))->format('d/m/Y H:i'));

        if (filled($order->notes)) {
            $lines[] = '';
            $lines[] = 'หมายเหตุ: '.$order->notes;
        }

        self::notifyBackend(
            type: $isEnrollment ? 'enrollment.created' : 'order.created',
            title: $title.' '.$order->order_no,
            message: implode("\n", $lines),
            data: [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'kind' => $order->kind,
                'url' => $order->adminUrl(),
            ],
            actionText: $isEnrollment ? 'เปิดใบสมัครเรียน' : 'เปิดออเดอร์',
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
            Log::info('AdminNotifier: ไม่มีอีเมลแจ้งเตือนในตั้งค่าระบบ', [
                'title' => $title,
            ]);

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
            } catch (Throwable $e) {
                Log::warning('AdminNotifier: ส่งอีเมลแจ้งเตือนไม่สำเร็จ', [
                    'email' => $email,
                    'title' => $title,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
