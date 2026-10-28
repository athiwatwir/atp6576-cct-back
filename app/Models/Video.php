<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail',
        'storage_provider',
        'storage_key',
        'hls_path',
        'duration_seconds',
        'is_free',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'is_free' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(Chapter::class, 'chapter_videos')
            ->withPivot('sort_order', 'is_required')
            ->orderByPivot('sort_order');
    }

    public function curriculums(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class, 'curriculum_videos')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function previewCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'preview_video_id');
    }

    public function getUrlAttribute(): ?string
    {
        return \App\Support\MediaStorage::url($this->storage_key);
    }

    /**
     * ลิงก์สตรีมผ่าน Laravel ที่ลงลายเซ็นและหมดอายุ ผูกกับวิชาและผู้ใช้ ตรวจสิทธิ์ทุกครั้งที่ตัวเล่นขอข้อมูล
     */
    public function streamUrl(int $courseId, ?int $userId, int $minutes = 240): ?string
    {
        if (blank($this->storage_key)) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'api.videos.stream',
            now()->addMinutes($minutes),
            ['video' => $this->id, 'c' => $courseId, 'u' => $userId ?? 0],
        );
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return \App\Support\MediaStorage::url($this->thumbnail);
    }
}
