<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $categories = Category::query()
            ->withCount(['subjects', 'courses', 'curriculums'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.categories.index', [
            'title' => 'ระดับชั้น',
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.categories.create', [
            'title' => 'เพิ่มระดับชั้น',
            'nextSortOrder' => (int) Category::withTrashed()->max('sort_order') + 1,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name']);

        Category::query()->create($data);

        return redirect()
            ->route('categories.index')
            ->with('success', 'เพิ่มระดับชั้นเรียบร้อยแล้ว');
    }

    public function edit(Category $category): View
    {
        return view('pages.categories.edit', [
            'title' => 'แก้ไขระดับชั้น',
            'category' => $category,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name'], $category->id);

        $category->update($data);

        return redirect()
            ->route('categories.index')
            ->with('success', 'บันทึกข้อมูลระดับชั้นเรียบร้อยแล้ว');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->courses()->exists() || $category->curriculums()->exists() || $category->subjects()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'ไม่สามารถลบระดับชั้นที่มีวิชา หลักสูตร หรือชนิดวิชาผูกอยู่ได้ ให้ปิดใช้งานแทน');
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'ลบระดับชั้นเรียบร้อยแล้ว');
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'level';
        }

        $slug = $base;
        $counter = 1;

        while (
            Category::withTrashed()
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
