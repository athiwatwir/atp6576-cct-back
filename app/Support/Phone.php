<?php

namespace App\Support;

/**
 * เบอร์โทรใน users.phone เก็บเป็นตัวเลขล้วน เบอร์ไทยแบบ +66 แปลงเป็น 0 นำหน้า
 * เพื่อให้เข้าสู่ระบบด้วยเบอร์โทรได้ไม่ว่าจะพิมพ์ขีด เว้นวรรค หรือ +66
 */
final class Phone
{
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '66') && in_array(strlen($digits), [10, 11], true)) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    public static function looksLikePhone(string $value): bool
    {
        return preg_match('/^[0-9+\-\s().]+$/', trim($value)) === 1;
    }
}
