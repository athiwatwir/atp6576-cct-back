<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Throwable;

class Audit
{
    protected static ?string $batchUuid = null;

    protected static bool $enabled = true;

    /**
     * บันทึก log แบบยืดหยุ่น — ใช้ได้ทั้งระบบ
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @param  array<string, mixed>|null  $properties
     */
    public static function log(
        string $action,
        ?Model $entity = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
        ?array $properties = null,
        ?User $actor = null,
    ): ?AuditLog {
        if (! self::$enabled) {
            return null;
        }

        try {
            $request = Request::instance();

            return AuditLog::query()->create([
                'user_id' => ($actor ?? Auth::user())?->getAuthIdentifier(),
                'action' => $action,
                'description' => $description,
                'entity_type' => $entity?->getMorphClass(),
                'entity_id' => $entity?->getKey(),
                'old_values' => self::sanitize($old),
                'new_values' => self::sanitize($new),
                'properties' => self::sanitize($properties),
                'batch_uuid' => self::$batchUuid,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 1000, '') : null,
                'request_method' => $request?->method(),
                'request_url' => $request ? Str::limit($request->fullUrl(), 1000, '') : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     */
    public static function created(Model $model, ?string $description = null, ?array $attributes = null): ?AuditLog
    {
        $new = $attributes ?? self::modelAttributes($model);

        return self::log(
            action: self::actionName($model, 'created'),
            entity: $model,
            new: $new,
            description: $description ?? self::defaultDescription($model, 'สร้าง'),
        );
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function updated(
        Model $model,
        array $old,
        ?array $new = null,
        ?string $description = null,
    ): ?AuditLog {
        $new ??= collect($old)
            ->mapWithKeys(fn ($value, $key) => [$key => $model->getAttribute($key)])
            ->all();

        $old = self::onlyChanged($old, $new);
        $new = array_intersect_key($new, $old);

        if ($old === [] && $new === []) {
            return null;
        }

        return self::log(
            action: self::actionName($model, 'updated'),
            entity: $model,
            old: $old,
            new: $new,
            description: $description ?? self::defaultDescription($model, 'แก้ไข'),
        );
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     */
    public static function deleted(Model $model, ?string $description = null, ?array $attributes = null): ?AuditLog
    {
        return self::log(
            action: self::actionName($model, 'deleted'),
            entity: $model,
            old: $attributes ?? self::modelAttributes($model),
            description: $description ?? self::defaultDescription($model, 'ลบ'),
        );
    }

    /**
     * Log การกระทำที่ไม่มี model โดยตรง
     *
     * @param  array<string, mixed>|null  $properties
     */
    public static function event(
        string $action,
        ?string $description = null,
        ?array $properties = null,
        ?Model $entity = null,
    ): ?AuditLog {
        return self::log(
            action: $action,
            entity: $entity,
            description: $description,
            properties: $properties,
        );
    }

    public static function withoutLogging(callable $callback): mixed
    {
        $previous = self::$enabled;
        self::$enabled = false;

        try {
            return $callback();
        } finally {
            self::$enabled = $previous;
        }
    }

    public static function batch(callable $callback): mixed
    {
        $previous = self::$batchUuid;
        self::$batchUuid = (string) Str::uuid();

        try {
            return $callback();
        } finally {
            self::$batchUuid = $previous;
        }
    }

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function modelAttributes(Model $model): array
    {
        $attributes = $model->attributesToArray();

        foreach (self::hiddenKeys($model) as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array<string, mixed>
     */
    public static function onlyChanged(array $old, array $new): array
    {
        $changed = [];

        foreach ($old as $key => $value) {
            $newValue = $new[$key] ?? null;

            if (self::normalizeComparable($value) !== self::normalizeComparable($newValue)) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }

    protected static function actionName(Model $model, string $event): string
    {
        $base = Str::of(class_basename($model))->snake()->toString();

        return "{$base}.{$event}";
    }

    protected static function defaultDescription(Model $model, string $verb): string
    {
        $label = method_exists($model, 'getAuditLabel')
            ? (string) $model->getAuditLabel()
            : (string) (class_basename($model).' #'.$model->getKey());

        return "{$verb} {$label}";
    }

    /**
     * @return list<string>
     */
    protected static function hiddenKeys(Model $model): array
    {
        $defaults = [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        if (property_exists($model, 'auditHidden') && is_array($model->auditHidden)) {
            return array_values(array_unique(array_merge($defaults, $model->auditHidden)));
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    protected static function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array($key, ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'], true)) {
                continue;
            }

            if (is_string($value) && Str::length($value) > 2000) {
                $value = Str::limit($value, 2000);
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    protected static function normalizeComparable(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) ($value ?? '');
    }
}
