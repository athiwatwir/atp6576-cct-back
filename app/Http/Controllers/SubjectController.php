<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subjects\StoreSubjectRequest;
use App\Http\Requests\Subjects\UpdateSubjectRequest;
use App\Models\Category;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $subjects = Subject::query()
            ->with('category:id,name')
            ->withCount('courses')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.subjects.index', [
            'title' => 'ชนิดวิชา',
            'subjects' => $subjects,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.subjects.create', [
            'title' => 'เพิ่มชนิดวิชา',
            'categories' => $this->categories(),
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        Subject::query()->create($data);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'เพิ่มชนิดวิชาเรียบร้อยแล้ว');
    }

    public function edit(Subject $subject): View
    {
        return view('pages.subjects.edit', [
            'title' => 'แก้ไขชนิดวิชา',
            'subject' => $subject,
            'categories' => $this->categories($subject->category_id),
        ]);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $data = $request->validated();

        if ($subject->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $subject->id);
        }

        $subject->update($data);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'บันทึกข้อมูลชนิดวิชาเรียบร้อยแล้ว');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->courses()->exists()) {
            return redirect()
                ->route('subjects.index')
                ->with('error', 'ไม่สามารถลบชนิดวิชาที่มีวิชาผูกอยู่ได้');
        }

        $subject->delete();

        return redirect()
            ->route('subjects.index')
            ->with('success', 'ลบชนิดวิชาเรียบร้อยแล้ว');
    }

    /**
     * @return Collection<int, Category>
     */
    private function categories(?int $currentId = null): Collection
    {
        return Category::query()
            ->where(fn ($query) => $query
                ->where('status', 'active')
                ->when($currentId, fn ($inner) => $inner->orWhere('id', $currentId)))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'status']);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'subject';
        }

        $slug = $base;
        $counter = 1;

        while (
            Subject::withTrashed()
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
