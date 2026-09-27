<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseChapterController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $seq = ((int) $course->chapters()->max('seq')) + 1;

        $chapter = $course->chapters()->create([
            ...$data,
            'seq' => $seq,
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
        ]);

        $chapter->update($data);

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'บันทึกบทเรียนเรียบร้อยแล้ว');
    }

    public function reorder(Request $request, Course $course): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer'],
        ]);

        $chapterIds = $course->chapters()->pluck('id')->all();
        $order = array_map('intval', $data['order']);

        abort_unless(
            count($order) === count($chapterIds)
            && empty(array_diff($order, $chapterIds)),
            422
        );

        DB::transaction(function () use ($order) {
            foreach ($order as $index => $chapterId) {
                Chapter::whereKey($chapterId)->update(['seq' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'เรียงลำดับบทเรียนเรียบร้อยแล้ว',
        ]);
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
