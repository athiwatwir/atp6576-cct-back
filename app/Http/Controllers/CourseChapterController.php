<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseChapterController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $sortOrder = ((int) $course->chapters()->max('sort_order')) + 1;

        $chapter = $course->chapters()->create([
            ...$data,
            'sort_order' => $sortOrder,
        ]);

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'เพิ่มบทเรียนเรียบร้อยแล้ว');
    }

    public function update(Request $request, Course $course, Chapter $chapter): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $chapter->update([
            ...$data,
            'sort_order' => $data['sort_order'] ?? $chapter->sort_order,
        ]);

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'บันทึกบทเรียนเรียบร้อยแล้ว');
    }

    public function destroy(Course $course, Chapter $chapter): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);

        $chapter->delete();

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters'])
            ->with('success', 'ลบบทเรียนเรียบร้อยแล้ว');
    }

    private function ensureChapterBelongsToCourse(Course $course, Chapter $chapter): void
    {
        abort_unless($chapter->course_id === $course->id, 404);
    }
}
