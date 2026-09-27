<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'properties',
        'batch_uuid',
        'ip_address',
        'user_agent',
        'request_method',
        'request_url',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    public function scopeForEntity(Builder $query, Model $entity): Builder
    {
        return $query
            ->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', $entity->getKey());
    }

    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeActionLike(Builder $query, string $pattern): Builder
    {
        return $query->where('action', 'like', $pattern);
    }

    public function getChangedAttributesAttribute(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        return array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
    }
}
