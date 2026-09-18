<?php

namespace App\Http\Controllers;

use App\Http\Requests\Courses\StoreCourseRequest;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $subjectId = $request->integer('subject_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;

        $courses = Course::query()
            ->with(['subject', 'category', 'instructor'])
            ->withCount('chapters')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.courses.index', [
            'title' => 'คอร์ส',
            'courses' => $courses,
            'subjects' => $this->subjects(),
            'categories' => $this->categories(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'subject_id' => $subjectId,
                'category_id' => $categoryId,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.courses.create', [
            'title' => 'เพิ่มคอร์ส',
            'subjects' => $this->subjects(),
            'categories' => $this->categories(),
            'instructors' => $this->instructors(),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $data = $this->preparePayload($request->validated());
        $data['slug'] = $this->uniqueSlug($data['name']);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        $course = Course::query()->create($data);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'เพิ่มคอร์สเรียบร้อยแล้ว');
    }

    public function show(Request $request, Course $course): View
    {
        $course->load([
            'category',
            'subject',
            'instructor',
            'chapters' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'chapters.videos',
            'chapters.documents',
            'assessments' => fn ($query) => $query->orderBy('title'),
        ]);

        $course->loadCount(['chapters', 'enrollments', 'reviews']);

        $selectedChapterId = $request->integer('chapter') ?: $course->chapters->first()?->id;
        $selectedChapter = $course->chapters->firstWhere('id', $selectedChapterId);

        $videoCount = $course->chapters->sum(fn ($chapter) => $chapter->videos->count());
        $documentCount = $course->chapters->sum(fn ($chapter) => $chapter->documents->count());
        $quizCount = $course->assessments->whereIn('type', ['quiz', 'exercise'])->count();
        $examCount = $course->assessments->where('type', 'exam')->count();

        return view('pages.courses.show', [
            'title' => $course->name,
            'course' => $course,
            'selectedChapter' => $selectedChapter,
            'activeTab' => $request->string('tab')->toString() ?: 'chapters',
            'stats' => [
                'videos' => $videoCount,
                'documents' => $documentCount,
                'quizzes' => $quizCount,
                'exams' => $examCount,
                'students' => $course->enrollments_count,
                'reviews' => $course->reviews_count,
            ],
        ]);
    }

    public function edit(Course $course): View
    {
        return view('pages.courses.edit', [
            'title' => 'แก้ไขคอร์ส',
            'course' => $course,
            'subjects' => $this->subjects(),
            'categories' => $this->categories(),
            'instructors' => $this->instructors(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $data = $this->preparePayload($request->validated(), $course);

        if ($course->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $course->id);
        }

        if ($request->boolean('remove_thumbnail') && $course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
            $data['thumbnail'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        unset($data['remove_thumbnail']);

        $course->update($data);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'บันทึกข้อมูลคอร์สเรียบร้อยแล้ว');
    }

    public function updateStatus(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,published,inactive'],
        ]);

        if ($data['status'] === 'published') {
            $data['published_at'] = $course->published_at ?? now();
        } elseif ($data['status'] === 'draft') {
            $data['published_at'] = null;
        }

        $course->update($data);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'อัปเดตสถานะคอร์สเรียบร้อยแล้ว');
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->enrollments()->exists()) {
            return redirect()
                ->route('courses.index')
                ->with('error', 'ไม่สามารถลบคอร์สที่มีผู้เรียนลงทะเบียนอยู่ได้');
        }

        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'ลบคอร์สเรียบร้อยแล้ว');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, ?Course $course = null): array
    {
        $data['category_id'] = $data['category_id'] ?: null;
        $data['subject_id'] = $data['subject_id'] ?: null;
        $data['instructor_id'] = $data['instructor_id'] ?: null;
        $data['sale_price'] = $data['sale_price'] ?? null;
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_trial_available'] = (bool) ($data['is_trial_available'] ?? false);

        if (empty($data['category_id']) && ! empty($data['subject_id'])) {
            $data['category_id'] = Subject::query()->find($data['subject_id'])?->category_id;
        }

        if (($data['status'] ?? null) === 'published') {
            $data['published_at'] = $course?->published_at ?? now();
        } elseif (($data['status'] ?? null) === 'draft') {
            $data['published_at'] = null;
        }

        return $data;
    }

    /**
     * @return Collection<int, Category>
     */
    private function categories()
    {
        return Category::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Subject>
     */
    private function subjects()
    {
        return Subject::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Instructor>
     */
    private function instructors()
    {
        return Instructor::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'course';
        }

        $slug = $base;
        $counter = 1;

        while (
            Course::withTrashed()
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
