<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subjects\StoreSubjectRequest;
use App\Http\Requests\Subjects\UpdateSubjectRequest;
use App\Models\Subject;
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
            'title' => 'วิชา',
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
            'title' => 'เพิ่มวิชา',
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        Subject::query()->create($data);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'เพิ่มวิชาเรียบร้อยแล้ว');
    }

    public function edit(Subject $subject): View
    {
        return view('pages.subjects.edit', [
            'title' => 'แก้ไขวิชา',
            'subject' => $subject,
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
            ->with('success', 'บันทึกข้อมูลวิชาเรียบร้อยแล้ว');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->courses()->exists()) {
            return redirect()
                ->route('subjects.index')
                ->with('error', 'ไม่สามารถลบวิชาที่มีคอร์สผูกอยู่ได้');
        }

        $subject->delete();

        return redirect()
            ->route('subjects.index')
            ->with('success', 'ลบวิชาเรียบร้อยแล้ว');
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
