<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * อันดับคะแนนของข้อสอบ: นักเรียนแต่ละคนใช้ครั้งที่ได้เปอร์เซ็นต์สูงสุด (เท่ากันใช้ครั้งที่ส่งก่อน)
 * นับเฉพาะครั้งที่ส่งคำตอบแล้ว (submitted) คะแนนเท่ากันได้อันดับเดียวกัน
 */
class ExamLeaderboard
{
    public const TOP = 50;

    /**
     * @return Collection<int, AssessmentAttempt> ครั้งที่ดีที่สุดของแต่ละคน เรียงจากคะแนนสูงไปต่ำ
     */
    public function bestAttempts(Assessment $exam): Collection
    {
        return AssessmentAttempt::query()
            ->where('assessment_id', $exam->id)
            ->where('status', 'submitted')
            ->orderByDesc('percentage')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'score', 'max_score', 'percentage', 'submitted_at'])
            ->unique('user_id')
            ->values();
    }

    /**
     * อันดับของครั้งนี้เทียบกับคะแนนดีที่สุดของนักเรียนคนอื่น
     *
     * @return array{rank: int, total_students: int}|null
     */
    public function rankOf(Assessment $exam, AssessmentAttempt $attempt): ?array
    {
        if ($attempt->status !== 'submitted') {
            return null;
        }

        $others = $this->bestAttempts($exam)->where('user_id', '!=', $attempt->user_id);
        $percentage = (float) $attempt->percentage;

        return [
            'rank' => 1 + $others->filter(fn (AssessmentAttempt $row) => (float) $row->percentage > $percentage)->count(),
            'total_students' => $others->count() + 1,
        ];
    }

    /**
     * @return array{total_students: int, top: list<array<string, mixed>>, me: array<string, mixed>|null}
     */
    public function board(Assessment $exam, int $userId): array
    {
        $best = $this->bestAttempts($exam);
        $ranks = $this->ranks($best);
        $topRows = $best->take(self::TOP);
        $mine = $best->firstWhere('user_id', $userId);

        $userIds = $topRows->pluck('user_id')->push($userId)->unique();
        $users = User::withTrashed()->whereIn('id', $userIds)->get(['id', 'name', 'nickname', 'school_name'])->keyBy('id');

        $row = fn (AssessmentAttempt $attempt) => [
            'rank' => $ranks[$attempt->user_id],
            'name' => $this->displayName($users->get($attempt->user_id)),
            'school_name' => $users->get($attempt->user_id)?->school_name,
            'score' => (float) $attempt->score,
            'max_score' => (float) $attempt->max_score,
            'percentage' => (float) $attempt->percentage,
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'is_me' => (int) $attempt->user_id === $userId,
        ];

        return [
            'total_students' => $best->count(),
            'top' => $topRows->map($row)->values()->all(),
            'me' => $mine ? $row($mine) : null,
        ];
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $best
     * @return array<int, int> user_id => อันดับ
     */
    private function ranks(Collection $best): array
    {
        $ranks = [];
        $previous = null;
        $rank = 0;

        foreach ($best->values() as $index => $attempt) {
            $percentage = (float) $attempt->percentage;
            if ($previous === null || $percentage < $previous) {
                $rank = $index + 1;
                $previous = $percentage;
            }
            $ranks[$attempt->user_id] = $rank;
        }

        return $ranks;
    }

    /**
     * ไม่เปิดชื่อเต็มของนักเรียนคนอื่น: ใช้ชื่อเล่นถ้าตั้งไว้ ไม่เช่นนั้นชื่อต้น + อักษรแรกของนามสกุล
     */
    private function displayName(?User $user): string
    {
        if (filled($user?->nickname)) {
            return trim((string) $user->nickname);
        }

        $parts = preg_split('/\s+/u', trim((string) $user?->name)) ?: [];
        $first = $parts[0] ?? '';

        if ($first === '') {
            return 'นักเรียน';
        }

        $last = $parts[1] ?? '';

        return $last === '' ? $first : $first.' '.Str::substr($last, 0, 1).'.';
    }
}
