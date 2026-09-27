<?php

namespace App\Enums;

use App\Support\StatusPalette;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'รอดำเนินการ',
            self::Processing => 'กำลังจัดเตรียม',
            self::Shipped => 'จัดส่งแล้ว',
            self::Completed => 'สำเร็จ',
            self::Cancelled => 'ยกเลิก',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => StatusPalette::NEUTRAL,
            self::Processing => StatusPalette::INFO,
            self::Shipped => StatusPalette::WARNING,
            self::Completed => StatusPalette::SUCCESS,
            self::Cancelled => StatusPalette::DANGER,
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
