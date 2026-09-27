<?php

namespace App\Enums;

enum ShippingStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case NotRequired = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'รอจัดส่ง',
            self::Ready => 'พร้อมส่ง',
            self::Shipped => 'กำลังจัดส่ง',
            self::Delivered => 'ส่งถึงแล้ว',
            self::Failed => 'จัดส่งไม่สำเร็จ',
            self::NotRequired => 'ไม่ต้องจัดส่ง',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
