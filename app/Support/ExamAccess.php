<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * สิทธิ์ทำข้อสอบของนักเรียน
 * - ข้อสอบขายแยก: มีสิทธิ์ของข้อสอบนั้น (enrollments.assessment_id)
 * - ข้อสอบในวิชา: มีสิทธิ์เรียนวิชาที่ผูกข้อสอบนั้นไว้ (assessment_courses)
 * สิทธิ์ต้องเปิดใช้งานและยังไม่หมดอายุ
 */
class ExamAccess
{
    public static function canTake(int $userId, Assessment $exam): bool
    {
        if (self::activeEnrollments($userId)->where('assessment_id', $exam->id)->exists()) {
            return true;
        }

        return self::ownedCourses($userId, $exam)->isNotEmpty();
    }

    /**
     * วิชาที่ผูกข้อสอบนี้และนักเรียนมีสิทธิ์เรียนอยู่ ใช้ทำลิงก์กลับไปหน้าเรียน
     *
     * @return Collection<int, Course>
     */
    public static function ownedCourses(int $userId, Assessment $exam): Collection
    {
        $courseIds = self::activeEnrollments($userId)
            ->whereNotNull('course_id')
            ->pluck('course_id');

        if ($courseIds->isEmpty()) {
            return collect();
        }

        return $exam->courses()
            ->where('courses.status', 'published')
            ->whereIn('courses.id', $courseIds)
            ->get(['courses.id', 'courses.name', 'courses.slug']);
    }

    private static function activeEnrollments(int $userId): Builder
    {
        return Enrollment::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
