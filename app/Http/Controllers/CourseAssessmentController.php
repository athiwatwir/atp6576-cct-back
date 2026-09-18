<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseAssessmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['exercise', 'quiz', 'exam'])],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'chapter_id' => ['nullable', 'integer', Rule::exists('chapters', 'id')->where('course_id', $course->id)],
        ]);

        $assessment = Assessment::query()->create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        $course->assessments()->attach($assessment->id, [
            'chapter_id' => $data['chapter_id'] ?? null,
            'video_id' => null,
        ]);

        $tab = $data['type'] === 'exam' ? 'exams' : 'quizzes';

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => $tab])
            ->with('success', 'เพิ่มแบบทดสอบเรียบร้อยแล้ว');
    }

    public function destroy(Course $course, Assessment $assessment): RedirectResponse
    {
        abort_unless($course->assessments()->where('assessments.id', $assessment->id)->exists(), 404);

        $type = $assessment->type;
        $course->assessments()->detach($assessment->id);
        $assessment->delete();

        $tab = $type === 'exam' ? 'exams' : 'quizzes';

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => $tab])
            ->with('success', 'ลบแบบทดสอบเรียบร้อยแล้ว');
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'assessment';
        $slug = $base;
        $counter = 1;

        while (Assessment::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
