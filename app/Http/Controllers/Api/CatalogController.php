<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\BookResource;
use App\Http\Resources\Student\CourseResource;
use App\Http\Resources\Student\CurriculumResource;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function courses(Request $request): AnonymousResourceCollection
    {
        $this->rememberPurchases($request);

        $courses = Course::query()
            ->with(['category:id,name,slug', 'subject:id,name,slug', 'instructor:id,name,slug'])
            ->withCount('chapters')
            ->where('status', 'published')
            ->tap(fn ($query) => $this->applyCatalogFilters($query, $request))
            ->paginate($this->perPage($request))
            ->withQueryString();

        return CourseResource::collection($courses);
    }

    public function showCourse(Request $request, string $slug): CourseResource
    {
        $this->rememberPurchases($request);

        $course = Course::query()
            ->with(['category:id,name,slug', 'subject:id,name,slug', 'instructor:id,name,slug'])
            ->withCount('chapters')
            ->where('status', 'published')
            ->where('slug', $slug)
            ->first();

        if (! $course) {
            abort(404, 'ไม่พบคอร์สนี้');
        }

        return CourseResource::make($course);
    }

    public function curriculums(Request $request): AnonymousResourceCollection
    {
        $this->rememberPurchases($request);

        $curriculums = Curriculum::query()
            ->with(['category:id,name,slug'])
            ->withCount('courses')
            ->where('status', 'published')
            ->tap(fn ($query) => $this->applyCatalogFilters($query, $request))
            ->paginate($this->perPage($request))
            ->withQueryString();

        return CurriculumResource::collection($curriculums);
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

        $book = Product::query()->forSale()->where('slug', $slug)->first();

        if (! $book) {
            abort(404, 'ไม่พบหนังสือนี้');
        }

        return BookResource::make($book);
    }

    private function applyCatalogFilters(mixed $query, Request $request): void
    {
        $search = $request->string('search')->trim()->toString();

        $query
            ->when($request->boolean('featured'), fn ($inner) => $inner->where('is_featured', true))
            ->when($search !== '', fn ($inner) => $inner->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name');
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

        $request->attributes->set('purchased_course_ids', Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('course_id')
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all());

        $request->attributes->set('purchased_curriculum_ids', Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('curriculum_id')
            ->pluck('curriculum_id')
            ->map(fn ($id) => (int) $id)
            ->all());

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
