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
        'trial_seconds',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'is_free' => 'boolean',
            'trial_seconds' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * จำนวนวินาทีที่ผู้ทดลองเรียนดูได้ null = ดูได้ทั้งคลิป (ไม่ได้ตั้ง หรือตั้งไว้ไม่น้อยกว่าความยาวคลิป)
     */
    public function trialLimitSeconds(): ?int
    {
        $limit = (int) $this->trial_seconds;

        if (! $this->is_free || $limit <= 0) {
            return null;
        }

        $duration = (int) $this->duration_seconds;

        return $duration > 0 && $limit >= $duration ? null : $limit;
    }

    /**
     * ข้อความสั้นสำหรับหลังบ้าน เช่น "3 นาที 20 วินาที"
     */
    public static function secondsLabel(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return trim(($minutes > 0 ? $minutes.' นาที ' : '').($rest > 0 || $minutes === 0 ? $rest.' วินาที' : ''));
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
