<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'เก็บเงินปลายทาง',
            self::BankTransfer => 'โอนเงิน',
            self::CreditCard => 'บัตรเครดิต',
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
