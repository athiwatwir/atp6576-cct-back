<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SystemSettings
{
    public const NOTIFICATION_EMAILS = 'admin_notification_emails';

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Cache::remember(
            self::cacheKey($key),
            now()->addMinutes(10),
            fn () => Setting::query()->where('key', $key)->value('value')
        );

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function put(string $key, ?string $value): void
    {
        $normalized = filled($value) ? trim((string) $value) : null;

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $normalized],
        );

        Cache::forget(self::cacheKey($key));
    }

    /**
     * อีเมลรับแจ้งเตือนสมัครใหม่ / ออเดอร์ใหม่ (หนึ่งอีเมลต่อบรรทัดหรือคั่นด้วยจุลภาค)
     *
     * @return list<string>
     */
    public static function notificationEmails(): array
    {
        return self::parseEmails(self::get(self::NOTIFICATION_EMAILS));
    }

    public static function notificationEmailsText(): string
    {
        return implode("\n", self::notificationEmails());
    }

    /**
     * @return list<string>
     */
    public static function parseEmails(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/u', $raw) ?: [];

        return collect($parts)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private static function cacheKey(string $key): string
    {
        return 'system_settings.'.$key;
    }
}
