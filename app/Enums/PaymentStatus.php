<?php

namespace App\Enums;

use App\Support\StatusPalette;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case AwaitingVerification = 'awaiting_verification';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'รอชำระเงิน',
            self::AwaitingVerification => 'รอตรวจสอบ',
            self::Paid => 'ชำระแล้ว',
            self::Failed => 'ชำระไม่สำเร็จ',
            self::Refunded => 'คืนเงินแล้ว',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending, self::Refunded => StatusPalette::NEUTRAL,
            self::AwaitingVerification => StatusPalette::WARNING,
            self::Paid => StatusPalette::SUCCESS,
            self::Failed => StatusPalette::DANGER,
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
