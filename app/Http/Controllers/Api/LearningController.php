<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningProgress;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoProgress;
use App\Models\VideoRating;
use App\Support\VideoAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    public function updateVideoProgress(Request $request, string $slug, Video $video): JsonResponse
    {
        $data = $request->validate([
            'position_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        $course = Course::query()
            ->learnableBy((int) $request->user()->id)
            ->where('slug', $slug)
            ->first();

        if (! $course) {
            abort(404, 'ไม่พบวิชานี้');
        }

        if (! VideoAccess::belongsToCourse($course, $video)) {
            abort(404, 'ไม่พบวิดีโอนี้ในวิชา');
        }

        /** @var User $user */
        $user = $request->user();

        if (! VideoAccess::canWatch($user->id, $course, $video)) {
            abort(403, 'ยังไม่มีสิทธิ์ดูวิดีโอนี้');
        }

        $duration = (int) ($data['duration_seconds'] ?? 0);
        if ($duration <= 0) {
            $duration = (int) $video->duration_seconds;
        }

        $position = (int) $data['position_seconds'];
        if ($duration > 0) {
            $position = min($position, $duration);
        }

        // ผู้ทดลองเรียนที่จำกัดเวลา บันทึกได้ไม่เกินเวลาทดลอง และไม่นับว่าดูจบ
        $limit = VideoAccess::watchLimit($user->id, $course, $video);
        if ($limit !== null) {
            $position = min($position, $limit);
        }

        $percent = $duration > 0 ? round(min(100, ($position / $duration) * 100), 2) : 0;
        $markComplete = $limit === null && ($request->boolean('completed') || ($duration > 0 && $percent >= 90));

        $existing = VideoProgress::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->first();

        $progress = VideoProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'video_id' => $video->id],
            [
                'position_seconds' => $position,
                'duration_seconds' => $duration,
                'progress_percent' => $percent,
                'completed_at' => $markComplete ? ($existing?->completed_at ?? now()) : $existing?->completed_at,
                'last_watched_at' => now(),
            ],
        );

        $this->refreshCourseProgress($user, $course);

        return response()->json([
            'data' => [
                'video_id' => $video->id,
                ...$progress->toStudentArray(),
            ],
        ]);
    }

    public function rateVideo(Request $request, string $slug, Video $video): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'rating' => 'คะแนน',
            'comment' => 'คำแนะนำ',
        ]);

        $course = Course::query()
            ->learnableBy((int) $request->user()->id)
            ->where('slug', $slug)
            ->first();

        if (! $course) {
            abort(404, 'ไม่พบวิชานี้');
        }

        if (! VideoAccess::belongsToCourse($course, $video)) {
            abort(404, 'ไม่พบวิดีโอนี้ในวิชา');
        }

        /** @var User $user */
        $user = $request->user();

        if (! VideoAccess::canWatch($user->id, $course, $video)) {
            abort(403, 'ยังไม่มีสิทธิ์ดูวิดีโอนี้');
        }

        $comment = trim((string) ($data['comment'] ?? ''));

        $rating = VideoRating::query()->updateOrCreate(
            ['user_id' => $user->id, 'video_id' => $video->id],
            [
                'course_id' => $course->id,
                'rating' => (int) $data['rating'],
                'comment' => $comment === '' ? null : $comment,
            ],
        );

        return response()->json([
            'message' => 'ขอบคุณสำหรับคำติชม',
            'data' => [
                'video_id' => $video->id,
                ...$rating->toStudentArray(),
            ],
        ]);
    }

    private function refreshCourseProgress(User $user, Course $course): void
    {
        $course->load(['chapters.videos']);
        $videoIds = $course->chapters
            ->flatMap(fn ($chapter) => $chapter->videos->pluck('id'))
            ->unique()
            ->values();

        $total = $videoIds->count();
        $completed = $total === 0
            ? 0
            : VideoProgress::query()
                ->where('user_id', $user->id)
                ->whereIn('video_id', $videoIds)
                ->whereNotNull('completed_at')
                ->count();

        $existing = LearningProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $finished = $total > 0 && $completed >= $total;

        LearningProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'progress_percent' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'completed_items' => $completed,
                'total_items' => $total,
                'completed_at' => $finished ? ($existing?->completed_at ?? now()) : null,
                'last_activity_at' => now(),
            ],
        );
    }
}
