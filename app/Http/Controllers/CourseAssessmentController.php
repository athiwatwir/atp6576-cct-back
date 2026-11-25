<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Question;
use App\Enums\MediaType;
use App\Support\MediaStorage;
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
            'type' => ['required', Rule::in(['quiz', 'exam'])],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'chapter_id' => ['nullable', 'integer', Rule::exists('chapters', 'id')->where('course_id', $course->id)],
            'description_image' => MediaType::ExamDescriptionImage->rules(),
        ]);

        $assessment = Assessment::query()->create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => filled(trim((string) ($data['description'] ?? ''))) ? trim((string) $data['description']) : null,
            'type' => $data['type'],
            'status' => $data['status'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        if ($request->hasFile('description_image')) {
            $assessment->update([
                'description_image' => MediaStorage::storeExamDescriptionImage(
                    $request->file('description_image'),
                    $assessment->id
                ),
            ]);
        }

        $course->assessments()->attach($assessment->id, [
            'chapter_id' => $data['chapter_id'] ?? null,
            'video_id' => null,
        ]);

        $tab = $data['type'] === 'exam' ? 'exams' : 'quizzes';

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => $tab, 'assessment' => $assessment->id])
            ->with('success', 'เพิ่มแบบทดสอบเรียบร้อยแล้ว');
    }

    public function update(Request $request, Course $course, Assessment $assessment): RedirectResponse
    {
        abort_unless($course->assessments()->where('assessments.id', $assessment->id)->exists(), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['quiz', 'exam'])],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'chapter_id' => ['nullable', 'integer', Rule::exists('chapters', 'id')->where('course_id', $course->id)],
            'description_image' => MediaType::ExamDescriptionImage->rules(),
            'remove_description_image' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'title' => $data['title'],
            'description' => filled(trim((string) ($data['description'] ?? ''))) ? trim((string) $data['description']) : null,
            'type' => $data['type'],
            'status' => $data['status'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'published_at' => $data['status'] === 'published'
                ? ($assessment->published_at ?? now())
                : ($data['status'] === 'draft' ? null : $assessment->published_at),
        ];

        if ($assessment->title !== $data['title']) {
            $payload['slug'] = $this->uniqueSlug($data['title'], $assessment->id);
        }

        if ($request->boolean('remove_description_image') && $assessment->description_image) {
            MediaStorage::delete($assessment->description_image);
            $payload['description_image'] = null;
        }

        if ($request->hasFile('description_image')) {
            $newPath = MediaStorage::examDescriptionImagePath($assessment->id);

            if ($assessment->description_image && $assessment->description_image !== $newPath) {
                MediaStorage::delete($assessment->description_image);
            }

            $payload['description_image'] = MediaStorage::storeExamDescriptionImage(
                $request->file('description_image'),
                $assessment->id
            );
        }

        $assessment->update($payload);

        $course->assessments()->updateExistingPivot($assessment->id, [
            'chapter_id' => $data['chapter_id'] ?? null,
        ]);

        $tab = $data['type'] === 'exam' ? 'exams' : 'quizzes';

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => $tab, 'assessment' => $assessment->id])
            ->with('success', 'บันทึกแบบทดสอบเรียบร้อยแล้ว');
    }

    public function destroy(Course $course, Assessment $assessment): RedirectResponse
    {
        abort_unless($course->assessments()->where('assessments.id', $assessment->id)->exists(), 404);

        $type = $assessment->type;
        MediaStorage::delete($assessment->description_image);
        $assessment->questions()->each(function (Question $question) {
            MediaStorage::delete($question->image);
            MediaStorage::delete($question->explanation_image);
            $question->delete();
        });
        $course->assessments()->detach($assessment->id);
        $assessment->delete();

        $tab = $type === 'exam' ? 'exams' : 'quizzes';

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => $tab])
            ->with('success', 'ลบแบบทดสอบเรียบร้อยแล้ว');
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'assessment';
        $slug = $base;
        $counter = 1;

        while (
            Assessment::withTrashed()
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
