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
        if (self::isTrialVideo($course, $video)) {
            return true;
        }

        return self::isEnrolled($userId, $course);
    }

    /**
     * จำนวนวินาทีที่ผู้ใช้คนนี้ดูได้ null = ดูได้ทั้งคลิป (ซื้อแล้ว หรือคลิปทดลองที่ไม่จำกัดเวลา)
     */
    public static function watchLimit(?int $userId, Course $course, Video $video): ?int
    {
        if (! self::isTrialVideo($course, $video) || self::isEnrolled($userId, $course)) {
            return null;
        }

        return $video->trialLimitSeconds();
    }

    public static function isTrialVideo(Course $course, Video $video): bool
    {
        return (bool) $course->is_trial_available && (bool) $video->is_free;
    }

    public static function isEnrolled(?int $userId, Course $course): bool
    {
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
