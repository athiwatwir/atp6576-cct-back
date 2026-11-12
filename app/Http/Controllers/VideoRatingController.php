<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Video;
use App\Models\VideoRating;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoRatingController extends Controller
{
    private const TIMEZONE = 'Asia/Bangkok';

    private const SORTS = [
        'latest' => 'ล่าสุดก่อน',
        'oldest' => 'เก่าสุดก่อน',
        'rating_desc' => 'คะแนนมากไปน้อย',
        'rating_asc' => 'คะแนนน้อยไปมาก',
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $base = VideoRating::query()->tap(fn (Builder $query) => $this->applyFilters($query, $filters));

        $distribution = (clone $base)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->map(fn ($total) => (int) $total);

        $total = $distribution->sum();
        $stats = [
            'total' => $total,
            'average' => $total > 0
                ? round($distribution->reduce(fn ($carry, $count, $rating) => $carry + $rating * $count, 0) / $total, 2)
                : null,
            'with_comment' => (clone $base)->whereNotNull('comment')->count(),
            'low' => ($distribution[1] ?? 0) + ($distribution[2] ?? 0),
            'distribution' => collect(range(5, 1))->mapWithKeys(fn ($star) => [$star => $distribution[$star] ?? 0])->all(),
        ];

        $ratings = (clone $base)
            ->when($filters['rating'] !== null, fn ($query) => $query->where('rating', $filters['rating']))
            ->with([
                'user:id,name,email,phone',
                'course:id,code,name',
                'video:id,title,duration_seconds',
                'video.chapters:id,course_id,title',
            ])
            ->tap(fn (Builder $query) => $this->applySort($query, $filters['sort']))
            ->paginate(20)
            ->withQueryString();

        $courses = Course::query()
            ->whereIn('id', VideoRating::query()->select('course_id'))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $videos = $filters['course_id']
            ? Video::query()
                ->whereIn('id', VideoRating::query()->where('course_id', $filters['course_id'])->select('video_id'))
                ->orderBy('title')
                ->get(['id', 'title'])
            : collect();

        return view('pages.video-ratings.index', [
            'title' => 'คำแนะนำ/ติชม',
            'ratings' => $ratings,
            'stats' => $stats,
            'filters' => $filters,
            'courses' => $courses,
            'videos' => $videos,
            'sorts' => self::SORTS,
            'timezone' => self::TIMEZONE,
        ]);
    }

    /**
     * @return array{search: string, course_id: int|null, video_id: int|null, rating: int|null, comment: string, from: string, to: string, sort: string}
     */
    private function filters(Request $request): array
    {
        $rating = $request->integer('rating');
        $comment = $request->string('comment')->toString();
        $sort = $request->string('sort')->toString();
        $courseId = $request->integer('course_id') ?: null;

        return [
            'search' => $request->string('search')->trim()->toString(),
            'course_id' => $courseId,
            'video_id' => $courseId ? ($request->integer('video_id') ?: null) : null,
            'rating' => $rating >= 1 && $rating <= 5 ? $rating : null,
            'comment' => in_array($comment, ['with', 'without'], true) ? $comment : '',
            'from' => $this->dateInput($request->string('from')->toString()),
            'to' => $this->dateInput($request->string('to')->toString()),
            'sort' => array_key_exists($sort, self::SORTS) ? $sort : 'latest',
        ];
    }

    private function dateInput(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, self::TIMEZONE)->format('Y-m-d') === $value ? $value : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = $filters['search'];

        $query
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('comment', 'like', $like)
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like))
                        ->orWhereHas('video', fn ($video) => $video->where('title', 'like', $like));
                });
            })
            ->when($filters['course_id'], fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->when($filters['video_id'], fn ($query, $videoId) => $query->where('video_id', $videoId))
            ->when($filters['comment'] === 'with', fn ($query) => $query->whereNotNull('comment'))
            ->when($filters['comment'] === 'without', fn ($query) => $query->whereNull('comment'))
            ->when($filters['from'] !== '', fn ($query) => $query->where(
                'updated_at',
                '>=',
                Carbon::createFromFormat('Y-m-d', $filters['from'], self::TIMEZONE)->startOfDay()->utc(),
            ))
            ->when($filters['to'] !== '', fn ($query) => $query->where(
                'updated_at',
                '<=',
                Carbon::createFromFormat('Y-m-d', $filters['to'], self::TIMEZONE)->endOfDay()->utc(),
            ));
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('updated_at')->orderBy('id'),
            'rating_desc' => $query->orderByDesc('rating')->orderByDesc('updated_at'),
            'rating_asc' => $query->orderBy('rating')->orderByDesc('updated_at'),
            default => $query->orderByDesc('updated_at')->orderByDesc('id'),
        };
    }
}
