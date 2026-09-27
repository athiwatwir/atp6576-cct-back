<?php

namespace App\Enums;

use App\Support\StatusPalette;

enum AccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'เปิดใช้งาน',
            self::Inactive => 'ปิดใช้งาน',
            self::Suspended => 'ระงับ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => StatusPalette::SUCCESS,
            self::Inactive => StatusPalette::NEUTRAL,
            self::Suspended => StatusPalette::DANGER,
        };
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, string>
     */
    public static function options(?array $only = null): array
    {
        $cases = self::cases();

        if ($only !== null) {
            $cases = array_values(array_filter(
                $cases,
                fn (self $case) => in_array($case->value, $only, true)
            ));
        }

        return collect($cases)
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    public static function labelFor(?string $status): string
    {
        $value = (string) $status;

        return self::tryFrom($value)?->label() ?? $value;
    }

    public static function classFor(?string $status, string $fallback = 'inactive'): string
    {
        return (self::tryFrom((string) $status) ?? self::tryFrom($fallback))?->badgeClass()
            ?? StatusPalette::NEUTRAL;
    }
}
