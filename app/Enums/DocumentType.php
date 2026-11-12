<?php

namespace App\Enums;

enum DocumentType: string
{
    case Course = 'course';
    case Exam = 'exam';
    case Book = 'book';
    case Order = 'order';
    case Receipt = 'receipt';

    public function prefix(): string
    {
        return match ($this) {
            self::Course => 'C',
            self::Exam => 'E',
            self::Book => 'B',
            // เลขออเดอร์ใหม่ใช้ OrderKind::orderPrefix() ส่วน O เป็นของออเดอร์เก่า
            self::Order => 'O',
            self::Receipt => 'RC',
        };
    }
}
