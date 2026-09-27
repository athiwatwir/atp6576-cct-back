<?php

namespace App\Enums;

enum DocumentType: string
{
    case Course = 'course';
    case Exam = 'exam';
    case Book = 'book';
    case Order = 'order';

    public function prefix(): string
    {
        return match ($this) {
            self::Course => 'C',
            self::Exam => 'E',
            self::Book => 'B',
            self::Order => 'O',
        };
    }
}
