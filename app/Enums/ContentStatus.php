<?php

namespace App\Enums;

use App\Support\StatusPalette;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Inactive = 'inactive';
    case Active = 'active';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'ร่าง',
            self::Published => 'เผยแพร่',
            self::Inactive => 'ปิดใช้งาน',
            self::Active => 'เปิดใช้งาน',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => StatusPalette::NEUTRAL,
            self::Published, self::Active => StatusPalette::SUCCESS,
            self::Inactive => StatusPalette::DANGER,
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

    /**
     * @param  list<string>  $only
     * @return list<array{value: string, label: string}>
     */
    public static function filterOptions(array $only): array
    {
        return array_map(
            fn (string $value) => ['value' => $value, 'label' => self::from($value)->label()],
            $only
        );
    }

    public static function labelFor(?string $status): string
    {
        $value = (string) $status;

        return self::tryFrom($value)?->label() ?? $value;
    }

    public static function classFor(?string $status, string $fallback = 'draft'): string
    {
        return (self::tryFrom((string) $status) ?? self::tryFrom($fallback))?->badgeClass()
            ?? StatusPalette::NEUTRAL;
    }
}
