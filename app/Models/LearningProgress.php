<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningProgress extends Model
{
    protected $table = 'learning_progress';

    protected $fillable = [
        'user_id',
        'course_id',
        'curriculum_id',
        'progress_percent',
        'completed_items',
        'total_items',
        'completed_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'decimal:2',
            'completed_items' => 'integer',
            'total_items' => 'integer',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
}
