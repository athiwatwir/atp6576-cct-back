<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseChapterVideoController extends Controller
{
    public function store(Request $request, Course $course, Chapter $chapter): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
        ]);

        $video = Video::query()->create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? 0,
            'is_free' => (bool) ($data['is_free'] ?? false),
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        $sortOrder = ((int) $chapter->videos()->max('chapter_videos.sort_order')) + 1;

        $chapter->videos()->attach($video->id, [
            'sort_order' => $sortOrder,
            'is_required' => true,
        ]);

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'เพิ่มวิดีโอบทเรียนเรียบร้อยแล้ว');
    }

    public function update(Request $request, Course $course, Chapter $chapter, Video $video): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);
        $this->ensureVideoBelongsToChapter($chapter, $video);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
        ]);

        $video->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? 0,
            'is_free' => (bool) ($data['is_free'] ?? false),
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published'
                ? ($video->published_at ?? now())
                : ($data['status'] === 'draft' ? null : $video->published_at),
        ]);

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'บันทึกวิดีโอบทเรียนเรียบร้อยแล้ว');
    }

    public function destroy(Course $course, Chapter $chapter, Video $video): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);
        $this->ensureVideoBelongsToChapter($chapter, $video);

        $chapter->videos()->detach($video->id);
        $video->delete();

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'ลบวิดีโอบทเรียนเรียบร้อยแล้ว');
    }

    private function ensureChapterBelongsToCourse(Course $course, Chapter $chapter): void
    {
        abort_unless($chapter->course_id === $course->id, 404);
    }

    private function ensureVideoBelongsToChapter(Chapter $chapter, Video $video): void
    {
        abort_unless($chapter->videos()->where('videos.id', $video->id)->exists(), 404);
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'video';
        $slug = $base;
        $counter = 1;

        while (Video::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
