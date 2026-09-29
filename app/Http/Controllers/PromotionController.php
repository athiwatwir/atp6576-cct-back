<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Http\Requests\Promotions\StorePromotionRequest;
use App\Http\Requests\Promotions\UpdatePromotionRequest;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $promotions = Promotion::query()
            ->with([
                'requiredCourses:id,name',
                'requiredProducts:id,name',
                'requiredVideos:id,title',
                'giftCourses:id,name',
                'giftAssessments:id,title',
                'giftProducts:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.promotions.index', [
            'title' => 'โปรโมชัน',
            'promotions' => $promotions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.promotions.create', [
            'title' => 'เพิ่มโปรโมชัน',
            ...$this->formData(),
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $promotion = Promotion::query()->create([
            ...$this->attributes($data),
            'slug' => $this->uniqueSlug($data['name']),
            'usage_count' => 0,
        ]);
        $this->syncTargets($promotion, $data);

        return redirect()
            ->route('promotions.show', $promotion)
            ->with('success', 'เพิ่มโปรโมชันเรียบร้อยแล้ว');
    }

    public function show(Promotion $promotion): View
    {
        $promotion->load([
            'courses:id,name',
            'curriculums:id,name',
            'assessments:id,title',
            'products:id,name',
            'requiredCourses:id,name',
            'requiredProducts:id,name',
            'requiredVideos:id,title',
            'giftCourses:id,name',
            'giftAssessments:id,title',
            'giftProducts:id,name',
        ]);

        return view('pages.promotions.show', [
            'title' => $promotion->name,
            'promotion' => $promotion,
        ]);
    }

    public function edit(Promotion $promotion): View
    {
        $promotion->load([
            'courses:id',
            'curriculums:id',
            'assessments:id',
            'products:id',
            'requiredCourses:id',
            'requiredProducts:id',
            'requiredVideos:id',
            'giftCourses:id',
            'giftAssessments:id',
            'giftProducts:id',
        ]);

        return view('pages.promotions.edit', [
            'title' => 'แก้ไขโปรโมชัน',
            'promotion' => $promotion,
            ...$this->formData(),
        ]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $data = $request->validated();
        $payload = $this->attributes($data);

        if ($promotion->name !== $data['name']) {
            $payload['slug'] = $this->uniqueSlug($data['name'], $promotion->id);
        }

        $promotion->update($payload);
        $this->syncTargets($promotion, $data);

        return redirect()
            ->route('promotions.show', $promotion)
            ->with('success', 'บันทึกโปรโมชันเรียบร้อยแล้ว');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()
            ->route('promotions.index')
            ->with('success', 'ลบโปรโมชันเรียบร้อยแล้ว');
    }

    /**
     * @return array{courses: \Illuminate\Database\Eloquent\Collection<int, Course>, curriculums: \Illuminate\Database\Eloquent\Collection<int, Curriculum>, exams: \Illuminate\Database\Eloquent\Collection<int, Assessment>, books: \Illuminate\Database\Eloquent\Collection<int, Product>}
     */
    private function formData(): array
    {
        return [
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'curriculums' => Curriculum::query()->orderBy('name')->get(['id', 'name']),
            'exams' => Assessment::query()->where('type', 'primary_exam')->orderBy('title')->get(['id', 'title']),
            'books' => Product::query()->books()->orderBy('name')->get(['id', 'name']),
            'videos' => Video::query()->orderBy('title')->get(['id', 'title']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $selected = ($data['course_ids'] ?? []) !== []
            || ($data['curriculum_ids'] ?? []) !== []
            || ($data['assessment_ids'] ?? []) !== []
            || ($data['product_ids'] ?? []) !== [];

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_discount_amount' => $data['discount_type'] === DiscountType::Percentage->value
                ? ($data['max_discount_amount'] ?? null)
                : null,
            'min_purchase_amount' => $data['min_purchase_amount'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'end_at' => $data['end_at'] ?? null,
            'status' => $data['status'],
            'rules' => [
                'applies_to' => $selected ? 'selected' : 'all',
                'student_group' => $data['student_group'] ?? 'all',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTargets(Promotion $promotion, array $data): void
    {
        $promotion->courses()->sync($data['course_ids'] ?? []);
        $promotion->curriculums()->sync($data['curriculum_ids'] ?? []);
        $promotion->assessments()->sync($data['assessment_ids'] ?? []);
        $promotion->products()->sync($data['product_ids'] ?? []);
        $promotion->requiredCourses()->sync($data['required_course_ids'] ?? []);
        $promotion->requiredProducts()->sync($data['required_product_ids'] ?? []);
        $promotion->requiredVideos()->sync($data['required_video_ids'] ?? []);
        $promotion->giftCourses()->sync($data['gift_course_ids'] ?? []);
        $promotion->giftAssessments()->sync($data['gift_assessment_ids'] ?? []);
        $promotion->giftProducts()->sync($data['gift_product_ids'] ?? []);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'promotion';
        }

        $slug = $base;
        $counter = 1;

        while (
            Promotion::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
