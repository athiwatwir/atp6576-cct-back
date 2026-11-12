<?php

namespace App\Support;

/**
 * รหัสผ่านแบบง่ายที่ส่งให้นักเรียนทางอีเมล: ตัวอักษรพิมพ์ใหญ่ 1 ตัว (ไม่มี O เพราะสับสนกับเลข 0) ตามด้วยตัวเลข 5 ตัว เช่น K48217
 */
class SimplePassword
{
    private const LETTERS = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';

    public static function generate(): string
    {
        $letter = self::LETTERS[random_int(0, strlen(self::LETTERS) - 1)];
        $digits = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);

        return $letter.$digits;
    }
}
