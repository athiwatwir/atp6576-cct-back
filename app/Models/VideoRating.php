<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * นักเรียนให้ดาวและคำแนะนำกับวิดีโอที่เรียน คนละหนึ่งรายการต่อวิดีโอ แก้ไขได้
 */
class VideoRating extends Model
{
    protected $fillable = [
        'user_id',
        'video_id',
        'course_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return array{rating: int, comment: string|null, updated_at: string|null}
     */
    public function toStudentArray(): array
    {
        return [
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
