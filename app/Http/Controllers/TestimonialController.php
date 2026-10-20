<?php

namespace App\Http\Controllers;

use App\Http\Requests\Testimonials\TestimonialRequest;
use App\Models\Testimonial;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $reviews = Testimonial::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('pages.reviews.index', [
            'title' => 'รีวิว',
            'reviews' => $reviews,
            'filters' => compact('search', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('pages.reviews.create', [
            'title' => 'เพิ่มรีวิว',
            'review' => new Testimonial([
                'status' => 'draft',
                'sort_order' => ((int) Testimonial::query()->max('sort_order')) + 1,
            ]),
        ]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        $review = Testimonial::query()->create([
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        if ($request->file('image')) {
            $review->update([
                'image' => MediaStorage::storeTestimonialCover($request->file('image'), $review->id),
            ]);
        }

        return redirect()
            ->route('reviews.show', $review)
            ->with('success', 'เพิ่มรีวิวเรียบร้อยแล้ว');
    }

    public function show(Testimonial $testimonial): View
    {
        return view('pages.reviews.show', [
            'title' => $testimonial->name,
            'review' => $testimonial,
        ]);
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('pages.reviews.edit', [
            'title' => 'แก้ไขรีวิว',
            'review' => $testimonial,
        ]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $payload = [
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? $testimonial->sort_order),
        ];

        if ($request->boolean('remove_image') && $testimonial->image) {
            MediaStorage::delete($testimonial->image);
            $payload['image'] = null;
        }

        if ($request->file('image')) {
            if ($testimonial->image) {
                MediaStorage::delete($testimonial->image);
            }

            $payload['image'] = MediaStorage::storeTestimonialCover($request->file('image'), $testimonial->id);
        }

        $testimonial->update($payload);

        return redirect()
            ->route('reviews.show', $testimonial)
            ->with('success', 'บันทึกรีวิวเรียบร้อยแล้ว');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        MediaStorage::delete($testimonial->image);
        $testimonial->delete();

        return redirect()
            ->route('reviews.index')
            ->with('success', 'ลบรีวิวเรียบร้อยแล้ว');
    }

    public function move(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $direction = $request->string('direction')->toString();

        $items = Testimonial::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $items->search(fn (Testimonial $item) => $item->id === $testimonial->id);

        if ($index !== false) {
            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

            if (isset($items[$swapWith])) {
                $current = $items->get($index);
                $items->put($index, $items->get($swapWith));
                $items->put($swapWith, $current);

                foreach ($items->values() as $position => $item) {
                    $item->update(['sort_order' => $position + 1]);
                }
            }
        }

        return redirect()
            ->route('reviews.index', $request->only(['search', 'status']))
            ->with('success', 'จัดลำดับรีวิวเรียบร้อยแล้ว');
    }
}
