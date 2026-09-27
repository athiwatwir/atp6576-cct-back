<?php

namespace App\Enums;

enum ShippingCarrier: string
{
    case ThailandPost = 'Thailand Post';
    case Kerry = 'Kerry Express';
    case Flash = 'Flash Express';
    case Jt = 'J&T Express';
    case Spx = 'SPX Express';
    case NinjaVan = 'Ninja Van';
    case Best = 'Best Express';
    case Dhl = 'DHL';
    case Shopee = 'Shopee Express';
    case Lazada = 'Lazada Express';
    case Other = 'อื่นๆ';

    public function label(): string
    {
        return match ($this) {
            self::ThailandPost => 'ไปรษณีย์ไทย (Thailand Post)',
            self::Kerry => 'Kerry Express',
            self::Flash => 'Flash Express',
            self::Jt => 'J&T Express',
            self::Spx => 'SPX Express',
            self::NinjaVan => 'Ninja Van',
            self::Best => 'Best Express',
            self::Dhl => 'DHL',
            self::Shopee => 'Shopee Express',
            self::Lazada => 'Lazada Express',
            self::Other => 'อื่นๆ',
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
