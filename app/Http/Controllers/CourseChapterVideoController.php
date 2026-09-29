<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Video;
use App\Support\CourseVideoUpload;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseChapterVideoController extends Controller
{
    public function store(Request $request, Course $course, Chapter $chapter): RedirectResponse|JsonResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);

        if ($response = CourseVideoUpload::guard($request)) {
            return $response;
        }

        $data = $request->validate(CourseVideoUpload::rules(), CourseVideoUpload::messages());

        CourseVideoUpload::store($course, $chapter, $data, $request->file('video_file'));

        return CourseVideoUpload::successResponse(
            $request,
            'เพิ่มวิดีโอบทเรียนเรียบร้อยแล้ว',
            route('courses.show', [
                'course' => $course,
                'tab' => 'chapters',
                'chapter' => $chapter->id,
            ]),
        );
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
            'video_file' => MediaType::CourseVideo->rules(),
        ]);

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? 0,
            'is_free' => (bool) ($data['is_free'] ?? false),
        ];

        if ($request->hasFile('video_file')) {
            $oldPath = $video->storage_key;

            $path = MediaStorage::storeCourseVideo(
                $request->file('video_file'),
                $course->id,
                $chapter->id,
                $video->id
            );

            if ($oldPath && $oldPath !== $path) {
                MediaStorage::delete($oldPath);
            }

            $payload['storage_provider'] = MediaStorage::disk();
            $payload['storage_key'] = $path;
        }

        $video->update($payload);

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'บันทึกวิดีโอบทเรียนเรียบร้อยแล้ว');
    }

    public function destroy(Course $course, Chapter $chapter, Video $video): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);
        $this->ensureVideoBelongsToChapter($chapter, $video);

        MediaStorage::delete($video->storage_key);

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

}
