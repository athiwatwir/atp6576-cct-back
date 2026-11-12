<?php

namespace App\Support;

/**
 * ที่อยู่เว็บนักเรียน (Nuxt) สำหรับลิงก์ในอีเมลและการ redirect
 * ใช้ STUDENT_APP_URL ถ้าไม่ตั้ง ใช้ origin จาก FRONTEND_URL (ข้าม * และบน production ข้าม localhost) สุดท้ายใช้ APP_URL
 */
class StudentApp
{
    public static function url(string $path = ''): string
    {
        $base = self::base();
        $path = ltrim($path, '/');

        return $path === '' ? $base : $base.'/'.$path;
    }

    public static function loginUrl(): string
    {
        return self::url('login');
    }

    public static function base(): string
    {
        $explicit = trim((string) config('services.storefront.app_url'));

        if ($explicit !== '') {
            return rtrim($explicit, '/');
        }

        $origins = array_values(array_filter(
            array_map(fn (string $origin) => rtrim(trim($origin), '/'), explode(',', (string) config('services.storefront.frontend_url'))),
            fn (string $origin) => $origin !== '' && $origin !== '*' && str_starts_with($origin, 'http'),
        ));

        if (app()->isProduction()) {
            $public = array_values(array_filter($origins, fn (string $origin) => ! self::isLocal($origin)));

            if ($public !== []) {
                return $public[0];
            }

            report(new \RuntimeException('ยังไม่ได้ตั้ง STUDENT_APP_URL ลิงก์ในอีเมลอาจชี้ผิดที่'));
        }

        return $origins[0] ?? rtrim((string) config('app.url'), '/');
    }

    private static function isLocal(string $origin): bool
    {
        $host = (string) parse_url($origin, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.local');
    }
}
