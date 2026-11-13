<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\MediaStorage;
use App\Support\ThaiProvinces;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * แดชบอร์ดการเรียน: นักเรียนต่อวิชา สิทธิ์ใกล้หมดอายุ และความคืบหน้าของแต่ละคน
 * ความคืบหน้าคำนวณสดจากวิดีโอที่ดูจบ (video_progress.completed_at) ÷ วิดีโอทั้งหมดของวิชา
 */
class LearningDashboardController extends Controller
{
    private const SOON_OPTIONS = [7, 14, 30];

    private const EXPIRED_WINDOW_DAYS = 30;

    private const ONLINE_MINUTES = 5;

    private const ONLINE_LIST_LIMIT = 50;

    private const ALL_LIST_LIMIT = 500;

    public function index(Request $request): View
    {
        $soonDays = in_array($request->integer('soon'), self::SOON_OPTIONS, true) ? $request->integer('soon') : 7;
        $filters = [
            'course_id' => $request->integer('course_id') ?: null,
            'search' => $request->string('search')->trim()->toString(),
            'state' => in_array($request->string('state')->toString(), ['active', 'soon', 'expired', 'all'], true) ? $request->string('state')->toString() : 'active',
            'sort' => in_array($request->string('sort')->toString(), ['expires', 'progress_asc', 'progress_desc', 'recent', 'name'], true) ? $request->string('sort')->toString() : 'expires',
            'soon' => $soonDays,
        ];

        $now = now();
        $soonUntil = $now->copy()->addDays($soonDays);
        $expiredSince = $now->copy()->subDays(self::EXPIRED_WINDOW_DAYS);

        $enrollments = $this->enrollments($expiredSince);
        $rows = $this->withProgress($enrollments);

        $rows = $rows->map(function (array $row) use ($now, $soonUntil) {
            $expires = $row['expires_at'];
            $row['state'] = match (true) {
                $expires !== null && $expires->lte($now) => 'expired',
                $expires !== null && $expires->lte($soonUntil) => 'soon',
                default => 'active',
            };
            $row['days_left'] = $expires !== null && $expires->gt($now) ? (int) ceil($now->diffInHours($expires) / 24) : null;

            return $row;
        });

        $active = $rows->whereIn('state', ['active', 'soon']);

        $metrics = [
            'students' => $active->pluck('user_id')->unique()->count(),
            'enrollments' => $active->count(),
            'soon_enrollments' => $rows->where('state', 'soon')->count(),
            'soon_students' => $rows->where('state', 'soon')->pluck('user_id')->unique()->count(),
            'expired_enrollments' => $rows->where('state', 'expired')->count(),
            'avg_progress' => $this->average($active),
            'completed' => $active->filter(fn ($row) => $row['progress'] !== null && $row['progress'] >= 100)->count(),
            'not_started' => $active->filter(fn ($row) => ($row['done'] ?? 0) === 0)->count(),
        ];

        $courses = $active->groupBy('course_id')
            ->map(fn (Collection $group) => [
                'id' => $group->first()['course_id'],
                'name' => $group->first()['course_name'],
                'code' => $group->first()['course_code'],
                'videos' => $group->first()['total'],
                'students' => $group->pluck('user_id')->unique()->count(),
                'enrollments' => $group->count(),
                'soon' => $group->where('state', 'soon')->count(),
                'avg_progress' => $this->average($group),
                'completed' => $group->filter(fn ($row) => $row['progress'] !== null && $row['progress'] >= 100)->count(),
                'not_started' => $group->filter(fn ($row) => ($row['done'] ?? 0) === 0)->count(),
            ])
            ->sortByDesc('students')
            ->values();

        $list = $rows
            ->when($filters['course_id'], fn (Collection $c) => $c->where('course_id', $filters['course_id']))
            ->when($filters['state'] === 'active', fn (Collection $c) => $c->whereIn('state', ['active', 'soon']))
            ->when(in_array($filters['state'], ['soon', 'expired'], true), fn (Collection $c) => $c->where('state', $filters['state']))
            ->when($filters['search'] !== '', fn (Collection $c) => $c->filter(fn ($row) => str_contains(mb_strtolower($row['student_name'].' '.$row['student_email']), mb_strtolower($filters['search']))));

        $list = match ($filters['sort']) {
            'progress_asc' => $list->sortBy(fn ($row) => $row['progress'] ?? -1),
            'progress_desc' => $list->sortByDesc(fn ($row) => $row['progress'] ?? -1),
            'recent' => $list->sortByDesc(fn ($row) => $row['last_activity_at']?->getTimestamp() ?? 0),
            'name' => $list->sortBy(fn ($row) => mb_strtolower($row['student_name'])),
            default => $list->sortBy(fn ($row) => $row['expires_at']?->getTimestamp() ?? PHP_INT_MAX),
        };

        $perPage = 25;
        $page = max(1, $request->integer('page', 1));
        $paginator = new LengthAwarePaginator(
            $list->values()->forPage($page, $perPage)->values(),
            $list->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('pages.learning-dashboard.index', [
            'title' => 'แดชบอร์ดการเรียน',
            'metrics' => $metrics,
            'courses' => $courses,
            'enrollments' => $paginator,
            'filters' => $filters,
            'soonOptions' => self::SOON_OPTIONS,
            'expiredWindow' => self::EXPIRED_WINDOW_DAYS,
            'courseOptions' => Course::query()->whereIn('id', $rows->pluck('course_id')->unique())->orderBy('name')->get(['id', 'name']),
            'online' => $this->onlineSnapshot(),
            'thailandMap' => self::thailandMap(),
        ]);
    }

    public function online(Request $request): JsonResponse
    {
        return response()->json($this->onlineSnapshot($request->string('scope')->toString() === 'all' ? 'all' : 'online'));
    }

    /**
     * นักเรียนออนไลน์ = เรียก API ด้วย token ภายใน ONLINE_MINUTES นาที (personal_access_tokens.last_used_at)
     * กำลังเรียน = บันทึกความคืบหน้าวิดีโอภายในช่วงเดียวกัน (learning_progress.last_activity_at ระบุวิชา)
     * scope=all รวมนักเรียนที่ออฟไลน์ในรายชื่อด้วย ส่วนจำนวนรายจังหวัดนับทั้งออนไลน์และทั้งหมดเสมอ
     *
     * @return array<string, mixed>
     */
    private function onlineSnapshot(string $scope = 'online'): array
    {
        $now = now();
        $since = $now->copy()->subMinutes(self::ONLINE_MINUTES);

        $seen = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('last_used_at', '>=', $since)
            ->groupBy('tokenable_id')
            ->selectRaw('tokenable_id as user_id, MAX(last_used_at) as last_seen_at')
            ->pluck('last_seen_at', 'user_id');

        $studying = DB::table('learning_progress')
            ->join('courses', 'courses.id', '=', 'learning_progress.course_id')
            ->whereNotNull('learning_progress.course_id')
            ->where('learning_progress.last_activity_at', '>=', $since)
            ->orderByDesc('learning_progress.last_activity_at')
            ->get(['learning_progress.user_id', 'learning_progress.last_activity_at', 'courses.id as course_id', 'courses.name as course_name', 'courses.code as course_code'])
            ->unique('user_id')
            ->keyBy('user_id');

        $videos = DB::table('video_progress')
            ->join('videos', 'videos.id', '=', 'video_progress.video_id')
            ->where('video_progress.last_watched_at', '>=', $since)
            ->orderByDesc('video_progress.last_watched_at')
            ->get(['video_progress.user_id', 'videos.title'])
            ->unique('user_id')
            ->pluck('title', 'user_id');

        $onlineIds = $seen->keys()->merge($studying->keys())->unique();

        $everyone = User::query()
            ->students()
            ->get(['id', 'name', 'email', 'phone', 'avatar', 'school_province', 'province', 'last_login_at', 'updated_at']);

        $lastUsed = $scope === 'all'
            ? DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->groupBy('tokenable_id')
                ->selectRaw('tokenable_id as user_id, MAX(last_used_at) as last_used_at')
                ->pluck('last_used_at', 'user_id')
            : collect();

        $rows = $everyone
            ->map(function (User $user) use ($seen, $studying, $videos, $onlineIds, $lastUsed) {
                $isOnline = $onlineIds->contains($user->id);
                $course = $isOnline ? $studying->get($user->id) : null;
                $lastSeen = collect([$seen->get($user->id), $course?->last_activity_at, $lastUsed->get($user->id), $isOnline ? null : $user->last_login_at])
                    ->filter()
                    ->map(fn ($value) => Carbon::parse($value))
                    ->sortDesc()
                    ->first();

                return [
                    'id' => $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) ($user->email ?: $user->phone),
                    'avatar_url' => self::avatarUrl($user),
                    'url' => route('students.show', $user->id),
                    'online' => $isOnline,
                    'province' => self::studentProvince($user),
                    'course_id' => $course?->course_id,
                    'course_name' => $course?->course_name,
                    'course_code' => $course?->course_code,
                    'video_title' => $course ? $videos->get($user->id) : null,
                    'last_seen_at' => $lastSeen?->toIso8601String(),
                    'last_seen_human' => $lastSeen?->locale('th')->diffForHumans(),
                ];
            });

        $provinces = $rows
            ->groupBy(fn (array $row) => $row['province'] ?? '')
            ->map(fn (Collection $group) => [
                'online' => $group->where('online', true)->count(),
                'total' => $group->count(),
            ]);

        $students = $rows
            ->when($scope === 'online', fn (Collection $c) => $c->where('online', true))
            ->sortBy(fn (array $row) => [$row['online'] ? 0 : 1, $row['course_id'] === null ? 1 : 0, -strtotime((string) $row['last_seen_at'])])
            ->values();

        $courses = $students
            ->whereNotNull('course_id')
            ->groupBy('course_id')
            ->map(fn (Collection $group) => [
                'id' => $group->first()['course_id'],
                'name' => $group->first()['course_name'],
                'code' => $group->first()['course_code'],
                'url' => route('courses.show', $group->first()['course_id']),
                'students' => $group->count(),
            ])
            ->sortByDesc('students')
            ->values();

        return [
            'scope' => $scope,
            'minutes' => self::ONLINE_MINUTES,
            'online' => $rows->where('online', true)->count(),
            'total' => $rows->count(),
            'studying' => $rows->whereNotNull('course_id')->count(),
            'courses' => $courses->all(),
            'students' => $students->take($scope === 'all' ? self::ALL_LIST_LIMIT : self::ONLINE_LIST_LIMIT)->all(),
            'listed' => $students->count(),
            'provinces' => $provinces->except([''])->all(),
            'unknown_province' => $provinces->get('', ['online' => 0, 'total' => 0]),
            'checked_at' => $now->copy()->timezone('Asia/Bangkok')->format('H:i:s'),
        ];
    }

    /**
     * จังหวัดที่เรียน ถ้าไม่มีใช้จังหวัดในที่อยู่ ชื่อต้องตรงกับ 77 จังหวัด ไม่ตรงถือว่าไม่ระบุ
     */
    private static function studentProvince(User $user): ?string
    {
        foreach ([$user->school_province, $user->province] as $value) {
            $name = ThaiProvinces::normalize($value);

            if ($name !== null && in_array($name, ThaiProvinces::all(), true)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return array{viewBox: string, provinces: list<array{code: string, name: string, d: string, x: float, y: float}>}
     */
    private static function thailandMap(): array
    {
        return json_decode((string) file_get_contents(resource_path('data/thailand-provinces.json')), true);
    }

    /**
     * สิทธิ์เรียนรายวิชาของนักเรียน: ใช้งานอยู่ และที่หมดอายุภายใน 30 วันที่ผ่านมา
     *
     * @return Collection<int, Enrollment>
     */
    private function enrollments(\DateTimeInterface $expiredSince): Collection
    {
        return Enrollment::query()
            ->with(['user:id,name,email,avatar,updated_at', 'course:id,name,code'])
            ->whereNotNull('course_id')
            ->where('status', EnrollmentStatus::Active->value)
            ->whereHas('user', fn ($query) => $query->students())
            ->whereHas('course')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $expiredSince))
            ->get(['id', 'user_id', 'course_id', 'started_at', 'expires_at', 'source']);
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return Collection<int, array<string, mixed>>
     */
    private function withProgress(Collection $enrollments): Collection
    {
        $courseIds = $enrollments->pluck('course_id')->unique()->values();
        $userIds = $enrollments->pluck('user_id')->unique()->values();

        $totals = DB::table('chapter_videos')
            ->join('chapters', 'chapters.id', '=', 'chapter_videos.chapter_id')
            ->join('videos', 'videos.id', '=', 'chapter_videos.video_id')
            ->whereNull('chapters.deleted_at')
            ->whereNull('videos.deleted_at')
            ->whereIn('chapters.course_id', $courseIds)
            ->groupBy('chapters.course_id')
            ->selectRaw('chapters.course_id, COUNT(DISTINCT chapter_videos.video_id) as total')
            ->pluck('total', 'course_id');

        $done = DB::table('video_progress')
            ->join('chapter_videos', 'chapter_videos.video_id', '=', 'video_progress.video_id')
            ->join('chapters', 'chapters.id', '=', 'chapter_videos.chapter_id')
            ->join('videos', 'videos.id', '=', 'video_progress.video_id')
            ->whereNull('chapters.deleted_at')
            ->whereNull('videos.deleted_at')
            ->whereIn('chapters.course_id', $courseIds)
            ->whereIn('video_progress.user_id', $userIds)
            ->groupBy('chapters.course_id', 'video_progress.user_id')
            ->selectRaw('chapters.course_id, video_progress.user_id,
                COUNT(DISTINCT CASE WHEN video_progress.completed_at IS NOT NULL THEN video_progress.video_id END) as done,
                MAX(video_progress.last_watched_at) as last_watched_at')
            ->get()
            ->keyBy(fn ($row) => $row->course_id.':'.$row->user_id);

        return $enrollments->map(function (Enrollment $enrollment) use ($totals, $done) {
            $total = (int) ($totals[$enrollment->course_id] ?? 0);
            $progress = $done->get($enrollment->course_id.':'.$enrollment->user_id);
            $completed = min($total, (int) ($progress->done ?? 0));

            return [
                'id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'student_name' => (string) $enrollment->user?->name,
                'student_email' => (string) $enrollment->user?->email,
                'student_avatar_url' => self::avatarUrl($enrollment->user),
                'course_id' => $enrollment->course_id,
                'course_name' => (string) $enrollment->course?->name,
                'course_code' => $enrollment->course?->code,
                'started_at' => $enrollment->started_at,
                'expires_at' => $enrollment->expires_at,
                'source' => $enrollment->source,
                'total' => $total,
                'done' => $completed,
                'progress' => $total > 0 ? round($completed / $total * 100, 1) : null,
                'last_activity_at' => isset($progress->last_watched_at) ? \Illuminate\Support\Carbon::parse($progress->last_watched_at) : null,
            ];
        });
    }

    private static function avatarUrl(?User $user): ?string
    {
        return $user?->avatar ? MediaStorage::url($user->avatar, $user->updated_at?->getTimestamp()) : null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function average(Collection $rows): ?float
    {
        $values = $rows->pluck('progress')->filter(fn ($value) => $value !== null);

        return $values->isEmpty() ? null : round((float) $values->avg(), 1);
    }
}
