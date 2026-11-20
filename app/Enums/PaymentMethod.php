<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case ThaiQr = 'thai_qr';
    case TrueMoney = 'truemoney';
    case ShopeePay = 'shopeepay';
    case RabbitLinePay = 'rabbit_linepay';
    case WeChatPay = 'wechat_pay';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'เก็บเงินปลายทาง',
            self::BankTransfer => 'โอนเงิน',
            self::CreditCard => 'บัตรเครดิต',
            self::ThaiQr => 'QR พร้อมเพย์',
            self::TrueMoney => 'TrueMoney Wallet',
            self::ShopeePay => 'ShopeePay',
            self::RabbitLinePay => 'Rabbit LINE Pay',
            self::WeChatPay => 'WeChat Pay',
        };
    }

    /**
     * qr = สแกน QR พร้อมเพย์, card = กรอกบัตร, transfer = โอนแล้วแนบสลิป, wallet = e-Wallet (พาไปแอปหรือแสดง QR)
     */
    public function type(): string
    {
        return match ($this) {
            self::ThaiQr => 'qr',
            self::CreditCard => 'card',
            self::BankTransfer, self::Cod => 'transfer',
            default => 'wallet',
        };
    }

    public function provider(): string
    {
        return match ($this) {
            self::ThaiQr => 'kgp',
            self::BankTransfer, self::Cod => 'manual',
            default => 'omise',
        };
    }

    public function isWallet(): bool
    {
        return $this->type() === 'wallet';
    }

    /** โอน/COD — แอดมินยืนยันเอง ไม่เปิดหน้า /pay */
    public function isManual(): bool
    {
        return $this->type() === 'transfer';
    }

    /**
     * ประเภท source ของ Omise ตามอุปกรณ์ mobile = เปิดแอป, desktop = แสดง QR หรือหน้าเว็บของผู้ให้บริการ
     */
    public function omiseSourceType(string $platform): ?string
    {
        $mobile = $platform === 'mobile';

        return match ($this) {
            self::TrueMoney => $mobile ? 'truemoney_jumpapp' : 'truemoney_qr',
            self::ShopeePay => $mobile ? 'shopeepay_jumpapp' : 'shopeepay',
            self::RabbitLinePay => 'rabbit_linepay',
            self::WeChatPay => $mobile ? 'wechat_pay' : 'wechat_pay_mpm',
            default => null,
        };
    }

    /**
     * วิธีชำระที่ลูกค้าเลือกได้ เรียงตามที่แสดงในหน้าตั้งค่าและ API
     *
     * @return list<self>
     */
    public static function customerMethods(): array
    {
        return [
            self::ThaiQr,
            self::CreditCard,
            self::TrueMoney,
            self::ShopeePay,
            self::RabbitLinePay,
            self::WeChatPay,
            self::BankTransfer,
        ];
    }

    /**
     * @return list<self>
     */
    public static function wallets(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case) => $case->isWallet()));
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
