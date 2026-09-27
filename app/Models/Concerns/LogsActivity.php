<?php

namespace App\Models\Concerns;

use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;

/**
 * ติดกับ Model เพื่อบันทึก created / updated / deleted อัตโนมัติ
 *
 * ตัวเลือกบน Model:
 * - public array $auditEvents = ['created', 'updated', 'deleted'];
 * - public array $auditExclude = ['updated_at', 'sort_order'];
 * - public array $auditOnly = ['status', 'name']; // ถ้ากำหนด จะ log เฉพาะคีย์เหล่านี้ตอน update
 * - public array $auditHidden = ['secret'];
 * - public function getAuditLabel(): string
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            if (! $model->shouldAuditEvent('created')) {
                return;
            }

            Audit::created($model);
        });

        static::updated(function (Model $model) {
            if (! $model->shouldAuditEvent('updated')) {
                return;
            }

            $changes = $model->getChanges();
            unset($changes[$model->getUpdatedAtColumn()]);

            $changes = $model->filterAuditAttributes($changes);

            if ($changes === []) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            Audit::updated($model, $old, $changes);
        });

        static::deleted(function (Model $model) {
            if (! $model->shouldAuditEvent('deleted')) {
                return;
            }

            Audit::deleted($model);
        });
    }

    protected function shouldAuditEvent(string $event): bool
    {
        $events = property_exists($this, 'auditEvents') && is_array($this->auditEvents)
            ? $this->auditEvents
            : ['created', 'updated', 'deleted'];

        return in_array($event, $events, true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function filterAuditAttributes(array $attributes): array
    {
        $exclude = property_exists($this, 'auditExclude') && is_array($this->auditExclude)
            ? $this->auditExclude
            : ['updated_at', 'created_at', 'remember_token', 'password'];

        $only = property_exists($this, 'auditOnly') && is_array($this->auditOnly)
            ? $this->auditOnly
            : null;

        $filtered = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, $exclude, true)) {
                continue;
            }

            if ($only !== null && ! in_array($key, $only, true)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }
}
