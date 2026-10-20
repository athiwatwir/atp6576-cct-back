<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    protected $table = 'video_progress';

    protected $fillable = [
        'user_id',
        'video_id',
        'position_seconds',
        'duration_seconds',
        'progress_percent',
        'completed_at',
        'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'position_seconds' => 'integer',
            'duration_seconds' => 'integer',
            'progress_percent' => 'decimal:2',
            'completed_at' => 'datetime',
            'last_watched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * @return array{status: string, position_seconds: int, progress_percent: float, last_watched_at: string|null}
     */
    public function toStudentArray(): array
    {
        $status = 'unwatched';

        if ($this->completed_at) {
            $status = 'completed';
        } elseif ($this->position_seconds >= 3) {
            $status = 'in_progress';
        }

        if ($status === 'unwatched') {
            return self::unwatchedPayload();
        }

        return [
            'status' => $status,
            'position_seconds' => (int) $this->position_seconds,
            'progress_percent' => (float) $this->progress_percent,
            'last_watched_at' => $this->last_watched_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{status: string, position_seconds: int, progress_percent: float, last_watched_at: null}
     */
    public static function unwatchedPayload(): array
    {
        return [
            'status' => 'unwatched',
            'position_seconds' => 0,
            'progress_percent' => 0,
            'last_watched_at' => null,
        ];
    }
}
