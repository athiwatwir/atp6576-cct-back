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

    /** ตัวขึ้นต้นเลขออเดอร์ (DocumentSequence::nextOrder) */
    public function orderPrefix(): string
    {
        return match ($this) {
            self::Book => 'B',
            self::Enrollment => 'C',
        };
    }
}
