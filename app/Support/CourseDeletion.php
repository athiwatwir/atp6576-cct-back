<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Document;
use App\Models\Question;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class CourseDeletion
{
    /**
     * @return list<string>
     */
    public static function stepKeys(): array
    {
        return [
            'videos',
            'documents',
            'assessments',
            'chapters',
            'learning_progress',
            'reviews',
            'trial_access',
            'cart_items',
            'relations',
            'enrollments',
            'thumbnail',
            'course',
        ];
    }

    /**
     * @return array{
     *     course: array{id:int,name:string,code:?string},
     *     items: list<array{key:string,label:string,description:string,count:int,default:bool,locked:bool,warning:?string}>
     * }
     */
    public static function summary(Course $course): array
    {
        $videoCount = self::courseVideosQuery($course)->count();
        $documentCount = self::courseDocumentsQuery($course)->count();
        $assessmentCount = $course->assessments()->count();
        $chapterCount = $course->chapters()->count();
        $progressCount = $course->learningProgress()->count();
        $reviewCount = $course->reviews()->count();
        $trialCount = $course->trialAccess()->count();
        $cartCount = $course->cartItems()->count();
        $relationCount = $course->curriculums()->count()
            + $course->promotions()->count()
            + $course->contents()->count();
        $enrollmentCount = $course->enrollments()->count();
        $hasThumbnail = filled($course->thumbnail);

        return [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
            ],
            'items' => [
                self::item('videos', 'วิดีโอบทเรียน', 'ลบบันทึกวิดีโอและไฟล์บน storage', $videoCount, true),
                self::item('documents', 'เอกสารประกอบ', 'ลบบันทึกเอกสารและไฟล์ที่อัปโหลด', $documentCount, true),
                self::item('assessments', 'แบบทดสอบในคอร์ส', 'ลบแบบฝึกหัด/ข้อสอบในคอร์ส รวมคำถาม', $assessmentCount, true),
                self::item('chapters', 'บทเรียน', 'ลบบทเรียนทั้งหมดของคอร์ส', $chapterCount, true),
                self::item('learning_progress', 'ความคืบหน้าการเรียน', 'ลบประวัติความคืบหน้าของผู้เรียน', $progressCount, true),
                self::item('reviews', 'รีวิว', 'ลบรีวิวของคอร์สนี้', $reviewCount, true),
                self::item('trial_access', 'สิทธิ์ทดลองเรียน', 'ลบสิทธิ์ทดลองเรียนที่ผูกกับคอร์ส', $trialCount, true),
                self::item('cart_items', 'รายการในตะกร้า', 'ลบคอร์สนี้ออกจากตะกร้าผู้ใช้', $cartCount, true),
                self::item('relations', 'ความสัมพันธ์อื่น', 'ถอดออกจากหลักสูตร / โปรโมชัน / เนื้อหา', $relationCount, true),
                self::item(
                    'enrollments',
                    'การลงทะเบียนผู้เรียน',
                    'ลบข้อมูลการลงทะเบียนของคอร์สนี้',
                    $enrollmentCount,
                    $enrollmentCount === 0,
                    false,
                    $enrollmentCount > 0
                        ? 'มีผู้เรียนลงทะเบียนอยู่ ต้องเลือกลบรายการนี้ก่อนจึงจะลบคอร์สได้'
                        : null
                ),
                self::item('thumbnail', 'ไฟล์ปกคอร์ส', 'ลบรูปปกออกจาก storage', $hasThumbnail ? 1 : 0, true),
                self::item('course', 'ลบคอร์ส', 'Soft delete ตัวคอร์ส (บังคับ)', 1, true, true),
            ],
        ];
    }

    /**
     * @return array{ok:bool,step:string,label:string,deleted:int,message:string}
     */
    public static function runStep(Course $course, string $step): array
    {
        if (! in_array($step, self::stepKeys(), true)) {
            throw new InvalidArgumentException("Unknown deletion step [{$step}]");
        }

        if ($step === 'course' && $course->enrollments()->exists()) {
            throw new InvalidArgumentException('ไม่สามารถลบคอร์สที่มีผู้เรียนลงทะเบียนอยู่ได้ กรุณาเลือกลบการลงทะเบียนก่อน');
        }

        return match ($step) {
            'videos' => self::deleteVideos($course),
            'documents' => self::deleteDocuments($course),
            'assessments' => self::deleteAssessments($course),
            'chapters' => self::deleteChapters($course),
            'learning_progress' => self::deleteLearningProgress($course),
            'reviews' => self::deleteReviews($course),
            'trial_access' => self::deleteTrialAccess($course),
            'cart_items' => self::deleteCartItems($course),
            'relations' => self::detachRelations($course),
            'enrollments' => self::deleteEnrollments($course),
            'thumbnail' => self::deleteThumbnail($course),
            'course' => self::deleteCourse($course),
        };
    }

    /**
     * @return array{key:string,label:string,description:string,count:int,default:bool,locked:bool,warning:?string}
     */
    private static function item(
        string $key,
        string $label,
        string $description,
        int $count,
        bool $default,
        bool $locked = false,
        ?string $warning = null
    ): array {
        return compact('key', 'label', 'description', 'count', 'default', 'locked', 'warning');
    }

    /**
     * @return array{ok:bool,step:string,label:string,deleted:int,message:string}
     */
    private static function result(string $step, string $label, int $deleted, string $message): array
    {
        return [
            'ok' => true,
            'step' => $step,
            'label' => $label,
            'deleted' => $deleted,
            'message' => $message,
        ];
    }

    private static function courseVideosQuery(Course $course)
    {
        return Video::query()->whereHas(
            'chapters',
            fn ($query) => $query->where('course_id', $course->id)
        );
    }

    private static function courseDocumentsQuery(Course $course)
    {
        return Document::query()->whereHas(
            'chapters',
            fn ($query) => $query->where('course_id', $course->id)
        );
    }

    private static function deleteVideos(Course $course): array
    {
        $deleted = 0;

        DB::transaction(function () use ($course, &$deleted) {
            $videos = self::courseVideosQuery($course)->get();

                foreach ($videos as $video) {
                foreach ($video->chapters()->where('chapters.course_id', $course->id)->get() as $chapter) {
                    $chapter->videos()->detach($video->id);
                }

                if ($video->chapters()->count() === 0) {
                    MediaStorage::delete($video->storage_key);
                    if ($video->thumbnail) {
                        MediaStorage::delete($video->thumbnail);
                    }
                    $video->delete();
                    $deleted++;
                }
            }

            if ($course->preview_video_id) {
                $course->update(['preview_video_id' => null]);
            }
        });

        return self::result('videos', 'วิดีโอบทเรียน', $deleted, "ลบวิดีโอ {$deleted} รายการ");
    }

    private static function deleteDocuments(Course $course): array
    {
        $deleted = 0;

        DB::transaction(function () use ($course, &$deleted) {
            $documents = self::courseDocumentsQuery($course)->get();

            foreach ($documents as $document) {
                foreach ($document->chapters()->where('chapters.course_id', $course->id)->get() as $chapter) {
                    $chapter->documents()->detach($document->id);
                }

                if ($document->chapters()->count() === 0) {
                    if ($document->file_path) {
                        Storage::disk('public')->delete($document->file_path);
                    }
                    $document->delete();
                    $deleted++;
                }
            }
        });

        return self::result('documents', 'เอกสารประกอบ', $deleted, "ลบเอกสาร {$deleted} รายการ");
    }

    private static function deleteAssessments(Course $course): array
    {
        $deleted = 0;

        DB::transaction(function () use ($course, &$deleted) {
            $assessments = $course->assessments()->get();

            foreach ($assessments as $assessment) {
                /** @var Assessment $assessment */
                $assessment->questions()->each(function (Question $question) {
                    $question->choices()->delete();
                    $question->delete();
                });

                $course->assessments()->detach($assessment->id);

                if (! $assessment->is_independent && $assessment->courses()->count() === 0) {
                    $assessment->delete();
                }

                $deleted++;
            }
        });

        return self::result('assessments', 'แบบทดสอบในคอร์ส', $deleted, "ลบแบบทดสอบ {$deleted} รายการ");
    }

    private static function deleteChapters(Course $course): array
    {
        $deleted = 0;

        DB::transaction(function () use ($course, &$deleted) {
            foreach ($course->chapters()->get() as $chapter) {
                $chapter->videos()->detach();
                $chapter->documents()->detach();
                $chapter->delete();
                $deleted++;
            }
        });

        return self::result('chapters', 'บทเรียน', $deleted, "ลบบทเรียน {$deleted} รายการ");
    }

    private static function deleteLearningProgress(Course $course): array
    {
        $deleted = $course->learningProgress()->count();
        $course->learningProgress()->delete();

        return self::result('learning_progress', 'ความคืบหน้าการเรียน', $deleted, "ลบความคืบหน้า {$deleted} รายการ");
    }

    private static function deleteReviews(Course $course): array
    {
        $deleted = $course->reviews()->count();
        $course->reviews()->delete();

        return self::result('reviews', 'รีวิว', $deleted, "ลบรีวิว {$deleted} รายการ");
    }

    private static function deleteTrialAccess(Course $course): array
    {
        $deleted = $course->trialAccess()->count();
        $course->trialAccess()->delete();

        return self::result('trial_access', 'สิทธิ์ทดลองเรียน', $deleted, "ลบสิทธิ์ทดลองเรียน {$deleted} รายการ");
    }

    private static function deleteCartItems(Course $course): array
    {
        $deleted = $course->cartItems()->count();
        $course->cartItems()->delete();

        return self::result('cart_items', 'รายการในตะกร้า', $deleted, "ลบจากตะกร้า {$deleted} รายการ");
    }

    private static function detachRelations(Course $course): array
    {
        $deleted = 0;

        DB::transaction(function () use ($course, &$deleted) {
            $deleted += $course->curriculums()->count();
            $course->curriculums()->detach();

            $deleted += $course->promotions()->count();
            $course->promotions()->detach();

            $deleted += $course->contents()->count();
            $course->contents()->detach();
        });

        return self::result('relations', 'ความสัมพันธ์อื่น', $deleted, "ถอดความสัมพันธ์ {$deleted} รายการ");
    }

    private static function deleteEnrollments(Course $course): array
    {
        $deleted = $course->enrollments()->count();
        $course->enrollments()->delete();

        return self::result('enrollments', 'การลงทะเบียนผู้เรียน', $deleted, "ลบการลงทะเบียน {$deleted} รายการ");
    }

    private static function deleteThumbnail(Course $course): array
    {
        $deleted = 0;

        if ($course->thumbnail) {
            MediaStorage::delete($course->thumbnail);
            $course->update(['thumbnail' => null]);
            $deleted = 1;
        }

        return self::result('thumbnail', 'ไฟล์ปกคอร์ส', $deleted, $deleted ? 'ลบไฟล์ปกคอร์สแล้ว' : 'ไม่มีไฟล์ปก');
    }

    private static function deleteCourse(Course $course): array
    {
        $course->delete();

        return self::result('course', 'ลบคอร์ส', 1, 'ลบคอร์สเรียบร้อยแล้ว');
    }
}
