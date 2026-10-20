<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\BookResource;
use App\Http\Resources\Student\CatalogItemResource;
use App\Http\Resources\Student\CourseResource;
use App\Http\Resources\Student\CurriculumResource;
use App\Http\Resources\Student\ExamResource;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VideoProgress;
use App\Support\PricePlans;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    public function catalog(Request $request): AnonymousResourceCollection
    {
        $this->rememberPurchases($request);
        $types = $this->catalogTypes($request);
        $featured = $request->boolean('featured');
        $search = $request->string('search')->trim()->toString();

        $union = null;

        foreach ($types as $type) {
            if ($featured && $type === 'exam') {
                continue;
            }

            $branch = $this->catalogBranch($type, $search, $featured);
            $union = $union instanceof Builder ? $union->unionAll($branch) : $branch;
        }

        if (! $union instanceof Builder) {
            $paginator = Assessment::query()->whereRaw('1 = 0')->paginate($this->perPage($request));
            $paginator->setCollection(collect());

            return CatalogItemResource::collection($paginator);
        }

        $paginator = DB::query()
            ->fromSub($union, 'catalog_items')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $rows = collect($paginator->items());
        $courses = Course::query()
            ->with(['category:id,name,slug', 'subject:id,name,slug', 'instructor:id,name,slug', 'activePricePlans'])
            ->withCount('chapters')
            ->whereIn('id', $rows->where('item_type', 'course')->pluck('id'))
            ->get()
            ->keyBy('id');
        $curriculums = Curriculum::query()
            ->with(['category:id,name,slug', 'activePricePlans'])
            ->withCount('courses')
            ->whereIn('id', $rows->where('item_type', 'curriculum')->pluck('id'))
            ->get()
            ->keyBy('id');
        $exams = Assessment::query()
            ->with('activePricePlans')
            ->whereIn('id', $rows->where('item_type', 'exam')->pluck('id'))
            ->get()
            ->keyBy('id');

        $paginator->setCollection($rows->map(function (object $row) use ($request, $courses, $curriculums, $exams) {
            return match ($row->item_type) {
                'course' => $this->courseCard($courses->get($row->id), $request),
                'curriculum' => $this->curriculumCard($curriculums->get($row->id), $request),
                'exam' => $this->examCard($exams->get($row->id), $request),
                default => null,
            };
        })->filter()->values());

        return CatalogItemResource::collection($paginator);
    }

    public function showCourse(Request $request, string $slug): CourseResource
    {
        $this->rememberPurchases($request);

        $course = Course::query()
            ->with([
                'category:id,name,slug',
                'subject:id,name,slug',
                'instructor:id,name,slug',
                'chapters' => fn ($query) => $query->orderBy('seq'),
                'chapters.videos',
                'chapters.documents',
                'assessments' => fn ($query) => $query->withCount('questions'),
                'activePricePlans',
            ])
            ->withCount('chapters')
            ->where('status', 'published')
            ->where('slug', $slug)
            ->first();

        if (! $course) {
            abort(404, 'ไม่พบคอร์สนี้');
        }

        $user = $request->user() ?? auth('sanctum')->user();
        $videoIds = $course->chapters->flatMap(fn ($chapter) => $chapter->videos->pluck('id'))->unique()->values();
        $progress = collect();

        if ($user && $videoIds->isNotEmpty()) {
            $progress = VideoProgress::query()
                ->where('user_id', $user->id)
                ->whereIn('video_id', $videoIds)
                ->get()
                ->keyBy('video_id');
        }

        $request->attributes->set('video_progress', $progress);

        return CourseResource::make($course);
    }

    public function showCurriculum(Request $request, string $slug): CurriculumResource
    {
        $this->rememberPurchases($request);

        $curriculum = Curriculum::query()
            ->with([
                'category:id,name,slug',
                'courses' => fn ($query) => $query
                    ->where('courses.status', 'published')
                    ->select('courses.id', 'courses.name', 'courses.slug', 'courses.thumbnail'),
                'activePricePlans',
            ])
            ->withCount('courses')
            ->where('status', 'published')
            ->where('slug', $slug)
            ->first();

        if (! $curriculum) {
            abort(404, 'ไม่พบหลักสูตรนี้');
        }

        return CurriculumResource::make($curriculum);
    }

    public function showExam(Request $request, string $slug): ExamResource
    {
        $this->rememberPurchases($request);

        $exam = Assessment::query()
            ->forSale()
            ->with('activePricePlans')
            ->withCount('questions')
            ->where('slug', $slug)
            ->first();

        if (! $exam) {
            abort(404, 'ไม่พบข้อสอบนี้');
        }

        return ExamResource::make($exam);
    }

    public function books(Request $request): AnonymousResourceCollection
    {
        $this->rememberPurchases($request);
        $search = $request->string('search')->trim()->toString();

        $books = Product::query()
            ->forSale()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return BookResource::collection($books);
    }

    public function showBook(Request $request, string $slug): BookResource
    {
        $this->rememberPurchases($request);

        $book = Product::query()
            ->forSale()
            ->with([
                'images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'video',
            ])
            ->where('slug', $slug)
            ->first();

        if (! $book) {
            abort(404, 'ไม่พบหนังสือนี้');
        }

        return BookResource::make($book);
    }

    /**
     * @return list<'course'|'curriculum'|'exam'>
     */
    private function catalogTypes(Request $request): array
    {
        $allowed = ['course', 'curriculum', 'exam'];
        $raw = $request->string('type')->trim()->toString();

        if ($raw === '') {
            return $allowed;
        }

        $types = array_values(array_unique(array_filter(array_map(
            fn (string $type) => trim($type),
            explode(',', $raw),
        ))));

        if ($types === [] || array_diff($types, $allowed) !== []) {
            throw ValidationException::withMessages([
                'type' => 'ประเภทสินค้าต้องเป็น course, curriculum หรือ exam',
            ]);
        }

        return $types;
    }

    private function catalogBranch(string $type, string $search, bool $featured): Builder
    {
        if ($type === 'exam') {
            return Assessment::query()
                ->forSale()
                ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
                ->selectRaw('? as item_type, id, title as name, 0 as is_featured, 0 as sort_order', ['exam']);
        }

        $query = $type === 'course' ? Course::query() : Curriculum::query();

        return $query
            ->where('status', 'published')
            ->when($featured, fn ($inner) => $inner->where('is_featured', true))
            ->when($search !== '', fn ($inner) => $inner->where('name', 'like', '%'.$search.'%'))
            ->selectRaw('? as item_type, id, name, is_featured, sort_order', [$type]);
    }

    /**
     * @return array<string, mixed>
     */
    private function courseCard(?Course $course, Request $request): array
    {
        if (! $course) {
            return [];
        }

        return [
            'item_type' => 'course',
            'id' => $course->id,
            'code' => $course->code,
            'slug' => $course->slug,
            'name' => $course->name,
            'short_description' => $course->short_description,
            'thumbnail_url' => $course->thumbnail_url,
            'price' => (float) $course->price,
            'sale_price' => $course->sale_price !== null ? (float) $course->sale_price : null,
            'effective_price' => $course->effective_price,
            'price_plans' => PricePlans::payload($course),
            'is_featured' => (bool) $course->is_featured,
            'purchased' => in_array($course->id, $request->attributes->get('purchased_course_ids', []), true),
            'access_expires_at' => $request->attributes->get('access_expires', [])['course'][$course->id] ?? null,
            'category' => $this->categoryCard($course->category),
            'subject' => $course->subject ? [
                'id' => $course->subject->id,
                'name' => $course->subject->name,
                'slug' => $course->subject->slug,
            ] : null,
            'instructor' => $course->instructor ? [
                'id' => $course->instructor->id,
                'name' => $course->instructor->name,
                'slug' => $course->instructor->slug,
            ] : null,
            'chapter_count' => (int) ($course->chapters_count ?? 0),
            'course_count' => null,
            'duration_minutes' => null,
            'is_trial_available' => (bool) $course->is_trial_available,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function curriculumCard(?Curriculum $curriculum, Request $request): array
    {
        if (! $curriculum) {
            return [];
        }

        return [
            'item_type' => 'curriculum',
            'id' => $curriculum->id,
            'code' => null,
            'slug' => $curriculum->slug,
            'name' => $curriculum->name,
            'short_description' => $curriculum->short_description,
            'thumbnail_url' => $curriculum->thumbnail_url,
            'price' => (float) $curriculum->price,
            'sale_price' => $curriculum->sale_price !== null ? (float) $curriculum->sale_price : null,
            'effective_price' => $curriculum->effective_price,
            'price_plans' => PricePlans::payload($curriculum),
            'is_featured' => (bool) $curriculum->is_featured,
            'purchased' => in_array($curriculum->id, $request->attributes->get('purchased_curriculum_ids', []), true),
            'access_expires_at' => $request->attributes->get('access_expires', [])['curriculum'][$curriculum->id] ?? null,
            'category' => $this->categoryCard($curriculum->category),
            'subject' => null,
            'instructor' => null,
            'chapter_count' => null,
            'course_count' => (int) ($curriculum->courses_count ?? 0),
            'duration_minutes' => null,
            'is_trial_available' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function examCard(?Assessment $exam, Request $request): array
    {
        if (! $exam) {
            return [];
        }

        return [
            'item_type' => 'exam',
            'id' => $exam->id,
            'code' => $exam->code,
            'slug' => $exam->slug,
            'name' => $exam->title,
            'short_description' => null,
            'thumbnail_url' => $exam->thumbnail_url,
            'price' => (float) $exam->price,
            'sale_price' => null,
            'effective_price' => $exam->effective_price,
            'price_plans' => PricePlans::payload($exam),
            'is_featured' => false,
            'purchased' => in_array($exam->id, $request->attributes->get('purchased_exam_ids', []), true),
            'access_expires_at' => $request->attributes->get('access_expires', [])['exam'][$exam->id] ?? null,
            'category' => null,
            'subject' => null,
            'instructor' => null,
            'chapter_count' => null,
            'course_count' => null,
            'duration_minutes' => $exam->duration_minutes,
            'is_trial_available' => null,
        ];
    }

    /**
     * @return array{id:int,name:string,slug:string}|null
     */
    private function categoryCard(mixed $category): ?array
    {
        if (! $category) {
            return null;
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    private function perPage(Request $request): int
    {
        return min(50, max(1, (int) $request->integer('per_page', 12)));
    }

    private function rememberPurchases(Request $request): void
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (! $user instanceof User) {
            return;
        }

        $active = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get(['course_id', 'curriculum_id', 'assessment_id', 'expires_at']);

        $expires = ['course' => [], 'curriculum' => [], 'exam' => []];

        foreach ([['course', 'course_id'], ['curriculum', 'curriculum_id'], ['exam', 'assessment_id']] as [$type, $column]) {
            foreach ($active->whereNotNull($column) as $enrollment) {
                $expires[$type][(int) $enrollment->{$column}] = $enrollment->expires_at?->toIso8601String();
            }
        }

        $request->attributes->set('access_expires', $expires);
        $request->attributes->set('purchased_course_ids', array_keys($expires['course']));
        $request->attributes->set('purchased_curriculum_ids', array_keys($expires['curriculum']));
        $request->attributes->set('purchased_exam_ids', array_keys($expires['exam']));

        $request->attributes->set('purchased_book_ids', OrderItem::query()
            ->whereNotNull('product_id')
            ->whereHas('order', fn ($order) => $order
                ->where('user_id', $user->id)
                ->where('payment_status', 'paid'))
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all());
    }
}
