<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Question;
use App\Support\ExamAccess;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ทำข้อสอบในเว็บนักเรียน ข้อสอบในวิชาและข้อสอบขายแยกใช้ endpoint เดียวกัน
 * คำถามส่งไปโดยไม่มีเฉลย ตรวจคะแนนที่ server ตอนส่งคำตอบ แล้วจึงส่งเฉลยกลับ
 */
class ExamAttemptController extends Controller
{
    /** ส่งคำตอบช้ากว่าเวลาหมดได้เล็กน้อย เผื่อเน็ตช้า */
    private const GRACE_SECONDS = 60;

    public function show(Request $request, string $slug): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $exam = $this->exam($slug, $userId);
        $this->expireStale($exam, $userId);

        $attempts = $exam->attempts()
            ->where('user_id', $userId)
            ->orderByDesc('attempt_number')
            ->get();

        $finished = $attempts->where('status', '!=', 'in_progress');
        $active = $attempts->firstWhere('status', 'in_progress');
        $used = $attempts->count();
        $remaining = $exam->max_attempts ? max(0, $exam->max_attempts - $used) : null;

        return response()->json([
            'data' => [
                ...$this->examPayload($exam),
                'courses' => ExamAccess::ownedCourses($userId, $exam)
                    ->map(fn ($course) => ['id' => $course->id, 'name' => $course->name, 'slug' => $course->slug])
                    ->values(),
                'attempts_used' => $used,
                'attempts_remaining' => $remaining,
                'can_start' => $active !== null || $remaining === null || $remaining > 0,
                'active_attempt_id' => $active?->id,
                'best_percentage' => $finished->isNotEmpty() ? (float) $finished->max('percentage') : null,
                'attempts' => $finished->map(fn (AssessmentAttempt $attempt) => $this->attemptSummary($attempt))->values(),
            ],
        ]);
    }

    public function start(Request $request, string $slug): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $exam = $this->exam($slug, $userId);
        $questions = $this->questions($exam);

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['exam' => 'ข้อสอบนี้ยังไม่มีคำถาม']);
        }

        $attempt = DB::transaction(function () use ($exam, $userId, $questions) {
            $this->expireStale($exam, $userId);

            $active = $exam->attempts()
                ->where('user_id', $userId)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($active) {
                return $active;
            }

            $used = $exam->attempts()->where('user_id', $userId)->count();

            if ($exam->max_attempts && $used >= $exam->max_attempts) {
                throw ValidationException::withMessages(['exam' => 'ทำข้อสอบนี้ครบ '.$exam->max_attempts.' ครั้งแล้ว']);
            }

            return $exam->attempts()->create([
                'user_id' => $userId,
                'attempt_number' => $used + 1,
                'status' => 'in_progress',
                'started_at' => now(),
                'max_score' => round((float) $questions->sum('points'), 2),
            ]);
        });

        return response()->json([
            'data' => [
                ...$this->attemptSummary($attempt),
                'deadline_at' => $this->deadline($exam, $attempt)?->toIso8601String(),
                'questions' => $questions->values()->map(fn (Question $question, int $index) => [
                    'id' => $question->id,
                    'number' => $index + 1,
                    'text' => $question->question_text,
                    'image_url' => $question->image_url,
                    'points' => (float) $question->points,
                    'choices' => $question->choices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'text' => $choice->choice_text,
                    ])->values(),
                ]),
            ],
        ], $attempt->wasRecentlyCreated ? 201 : 200);
    }

    public function submit(Request $request, string $slug, AssessmentAttempt $attempt): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $exam = $this->exam($slug, $userId);
        $this->assertOwnAttempt($exam, $attempt, $userId);

        $data = $request->validate([
            'answers' => ['present', 'array', 'max:500'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.choice_id' => ['nullable', 'integer'],
        ]);

        if ($attempt->status !== 'in_progress') {
            return response()->json(['data' => $this->resultPayload($exam, $attempt->fresh())]);
        }

        $deadline = $this->deadline($exam, $attempt);
        if ($deadline && now()->greaterThan($deadline->copy()->addSeconds(self::GRACE_SECONDS))) {
            $this->finalize($exam, $attempt, [], 'expired');

            return response()->json([
                'message' => 'หมดเวลาทำข้อสอบแล้ว ครั้งนี้ไม่ได้คะแนน',
                'data' => $this->resultPayload($exam, $attempt->fresh()),
            ], 422);
        }

        $selected = collect($data['answers'])
            ->filter(fn (array $answer) => isset($answer['choice_id']))
            ->mapWithKeys(fn (array $answer) => [(int) $answer['question_id'] => (int) $answer['choice_id']])
            ->all();

        $this->finalize($exam, $attempt, $selected, 'submitted');

        return response()->json(['data' => $this->resultPayload($exam, $attempt->fresh())]);
    }

    public function result(Request $request, string $slug, AssessmentAttempt $attempt): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $exam = $this->exam($slug, $userId);
        $this->assertOwnAttempt($exam, $attempt, $userId);

        if ($attempt->status === 'in_progress') {
            throw ValidationException::withMessages(['attempt' => 'ยังไม่ได้ส่งคำตอบครั้งนี้']);
        }

        return response()->json(['data' => $this->resultPayload($exam, $attempt)]);
    }

    private function exam(string $slug, int $userId): Assessment
    {
        $exam = Assessment::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'draft')
            ->withCount(['questions' => fn ($query) => $query->where('status', 'active')])
            ->first();

        if (! $exam) {
            abort(404, 'ไม่พบข้อสอบนี้');
        }

        if (! ExamAccess::canTake($userId, $exam)) {
            abort(403, 'ยังไม่มีสิทธิ์ทำข้อสอบนี้');
        }

        return $exam;
    }

    /**
     * @return Collection<int, Question>
     */
    private function questions(Assessment $exam): Collection
    {
        return $exam->questions()
            ->where('status', 'active')
            ->with('choices')
            ->get()
            ->filter(fn (Question $question) => $question->choices->count() >= 2)
            ->values();
    }

    private function assertOwnAttempt(Assessment $exam, AssessmentAttempt $attempt, int $userId): void
    {
        if ((int) $attempt->assessment_id !== (int) $exam->id || (int) $attempt->user_id !== $userId) {
            abort(404, 'ไม่พบการทำข้อสอบครั้งนี้');
        }
    }

    private function deadline(Assessment $exam, AssessmentAttempt $attempt): ?\Illuminate\Support\Carbon
    {
        if (! $exam->duration_minutes || ! $attempt->started_at) {
            return null;
        }

        return $attempt->started_at->copy()->addMinutes($exam->duration_minutes);
    }

    /**
     * ครั้งที่เปิดค้างจนเลยเวลาแล้วนับเป็นหมดเวลา (0 คะแนน) เพื่อให้เริ่มครั้งใหม่ได้
     */
    private function expireStale(Assessment $exam, int $userId): void
    {
        if (! $exam->duration_minutes) {
            return;
        }

        $exam->attempts()
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->get()
            ->each(function (AssessmentAttempt $attempt) use ($exam) {
                $deadline = $this->deadline($exam, $attempt);
                if ($deadline && now()->greaterThan($deadline->copy()->addSeconds(self::GRACE_SECONDS))) {
                    $this->finalize($exam, $attempt, [], 'expired');
                }
            });
    }

    /**
     * @param  array<int, int>  $selected  question_id => choice_id
     */
    private function finalize(Assessment $exam, AssessmentAttempt $attempt, array $selected, string $status): void
    {
        $questions = $this->questions($exam);

        DB::transaction(function () use ($exam, $attempt, $selected, $status, $questions) {
            $locked = AssessmentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'in_progress') {
                return;
            }

            $score = 0.0;
            $maxScore = 0.0;

            foreach ($questions as $question) {
                $points = (float) $question->points;
                $maxScore += $points;
                $choiceId = $selected[$question->id] ?? null;
                $choice = $choiceId ? $question->choices->firstWhere('id', $choiceId) : null;
                $correct = $choice !== null && (bool) $choice->is_correct;
                $awarded = $correct ? $points : 0.0;
                $score += $awarded;

                $locked->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'selected_choice_id' => $choice?->id,
                        'is_correct' => $choice !== null ? $correct : null,
                        'points_awarded' => $awarded,
                    ],
                );
            }

            $percentage = $maxScore > 0 ? round($score / $maxScore * 100, 2) : 0.0;

            $locked->update([
                'status' => $status,
                'submitted_at' => now(),
                'score' => round($score, 2),
                'max_score' => round($maxScore, 2),
                'percentage' => $percentage,
                'passed' => $exam->passing_score !== null ? $percentage >= (float) $exam->passing_score : null,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function examPayload(Assessment $exam): array
    {
        return [
            'id' => $exam->id,
            'slug' => $exam->slug,
            'name' => $exam->title,
            'description' => $exam->description,
            'type' => $exam->type,
            'duration_minutes' => $exam->duration_minutes,
            'passing_score' => $exam->passing_score !== null ? (float) $exam->passing_score : null,
            'max_attempts' => $exam->max_attempts,
            'question_count' => (int) ($exam->questions_count ?? 0),
            'thumbnail_url' => $exam->thumbnail_url,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptSummary(AssessmentAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'score' => (float) $attempt->score,
            'max_score' => (float) $attempt->max_score,
            'percentage' => (float) $attempt->percentage,
            'passed' => $attempt->passed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultPayload(Assessment $exam, AssessmentAttempt $attempt): array
    {
        $answers = $attempt->answers()->get()->keyBy('question_id');
        $questions = $this->questions($exam);

        return [
            ...$this->attemptSummary($attempt),
            'passing_score' => $exam->passing_score !== null ? (float) $exam->passing_score : null,
            'correct_count' => $answers->where('is_correct', true)->count(),
            'question_count' => $questions->count(),
            'review' => $questions->values()->map(function (Question $question, int $index) use ($answers) {
                $answer = $answers->get($question->id);

                return [
                    'id' => $question->id,
                    'number' => $index + 1,
                    'text' => $question->question_text,
                    'image_url' => $question->image_url,
                    'points' => (float) $question->points,
                    'explanation' => $question->explanation,
                    'selected_choice_id' => $answer?->selected_choice_id,
                    'correct_choice_id' => $question->choices->firstWhere('is_correct', true)?->id,
                    'is_correct' => (bool) ($answer?->is_correct),
                    'points_awarded' => (float) ($answer?->points_awarded ?? 0),
                    'choices' => $question->choices->map(fn ($choice) => [
                        'id' => $choice->id,
                        'text' => $choice->choice_text,
                    ])->values(),
                ];
            }),
        ];
    }
}
