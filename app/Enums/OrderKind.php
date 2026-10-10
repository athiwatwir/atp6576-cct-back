<?php

namespace App\Enums;

enum OrderKind: string
{
    case Book = 'book';
    case Enrollment = 'enrollment';

    public function label(): string
    {
        return match ($this) {
            self::Book => 'ขายหนังสือ',
            self::Enrollment => 'สมัครเรียน',
        };
    }
}
