<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Video;

/**
 * สิทธิ์ดูวิดีโอบทเรียนของนักเรียน: ซื้อวิชาแล้วและสิทธิ์ยังไม่หมดอายุ หรือวิชาเปิดทดลองเรียนและคลิปนั้นเป็นคลิปทดลอง
 */
class VideoAccess
{
    public static function canWatch(?int $userId, Course $course, Video $video): bool
    {
        if ($course->is_trial_available && $video->is_free) {
            return true;
        }

        if (! $userId) {
            return false;
        }

        return Enrollment::query()
            ->where('user_id', $userId)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public static function belongsToCourse(Course $course, Video $video): bool
    {
        return $course->chapters()
            ->whereHas('videos', fn ($query) => $query->where('videos.id', $video->id))
            ->exists();
    }
}
