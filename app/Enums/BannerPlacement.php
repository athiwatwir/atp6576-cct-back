<?php

namespace App\Enums;

enum BannerPlacement: string
{
    case Home = 'home';
    case Courses = 'courses';
    case Books = 'books';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'หน้าแรก',
            self::Courses => 'หน้าคอร์ส',
            self::Books => 'หน้าหนังสือ',
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

    public static function labelFor(?string $placement): string
    {
        return self::tryFrom((string) $placement)?->label() ?? 'ไม่ระบุตำแหน่ง';
    }
}
