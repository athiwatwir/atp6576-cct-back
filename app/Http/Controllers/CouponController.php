<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Http\Requests\Coupons\StoreCouponRequest;
use App\Http\Requests\Coupons\UpdateCouponRequest;
use App\Models\Assessment;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $coupons = Coupon::query()
            ->with([
                'courses:id,name',
                'curriculums:id,name',
                'assessments:id,title',
                'products:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.coupons.index', [
            'title' => 'คูปอง',
            'coupons' => $coupons,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.coupons.create', [
            'title' => 'เพิ่มคูปอง',
            ...$this->formData(),
        ]);
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $coupon = Coupon::query()->create([
            ...$this->attributes($data),
            'usage_count' => 0,
        ]);
        $this->syncTargets($coupon, $data);

        return redirect()
            ->route('coupons.show', $coupon)
            ->with('success', 'เพิ่มคูปองเรียบร้อยแล้ว');
    }

    public function show(Coupon $coupon): View
    {
        $coupon->load([
            'courses:id,name',
            'curriculums:id,name',
            'assessments:id,title',
            'products:id,name',
            'usages' => fn ($query) => $query->with(['user:id,name,email', 'order:id,order_no'])->latest('used_at'),
        ]);

        return view('pages.coupons.show', [
            'title' => $coupon->code,
            'coupon' => $coupon,
        ]);
    }

    public function edit(Coupon $coupon): View
    {
        $coupon->load(['courses:id', 'curriculums:id', 'assessments:id', 'products:id']);

        return view('pages.coupons.edit', [
            'title' => 'แก้ไขคูปอง',
            'coupon' => $coupon,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $data = $request->validated();
        $coupon->update($this->attributes($data));
        $this->syncTargets($coupon, $data);

        return redirect()
            ->route('coupons.show', $coupon)
            ->with('success', 'บันทึกคูปองเรียบร้อยแล้ว');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->usages()->exists()) {
            return redirect()
                ->route('coupons.show', $coupon)
                ->with('error', 'คูปองนี้ถูกใช้แล้ว ปิดใช้งานแทนการลบ');
        }

        $coupon->delete();

        return redirect()
            ->route('coupons.index')
            ->with('success', 'ลบคูปองเรียบร้อยแล้ว');
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
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_discount_amount' => $data['discount_type'] === DiscountType::Percentage->value
                ? ($data['max_discount_amount'] ?? null)
                : null,
            'min_purchase_amount' => $data['min_purchase_amount'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'per_user_limit' => $data['per_user_limit'] ?? null,
            'start_at' => $data['start_at'] ?? null,
            'end_at' => $data['end_at'] ?? null,
            'status' => $data['status'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncTargets(Coupon $coupon, array $data): void
    {
        $coupon->courses()->sync($data['course_ids'] ?? []);
        $coupon->curriculums()->sync($data['curriculum_ids'] ?? []);
        $coupon->assessments()->sync($data['assessment_ids'] ?? []);
        $coupon->products()->sync($data['product_ids'] ?? []);
    }
}
