<?php

namespace App\Enums;

use App\Support\StatusPalette;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'กำลังเรียน',
            self::Expired => 'หมดอายุ',
            self::Cancelled => 'ยกเลิก',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => StatusPalette::SUCCESS,
            self::Expired => StatusPalette::WARNING,
            self::Cancelled => StatusPalette::NEUTRAL,
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
