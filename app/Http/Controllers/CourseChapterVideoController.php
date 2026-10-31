<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Video;
use App\Support\CourseVideoUpload;
use App\Support\MediaStorage;
use App\Support\DirectUpload;
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
            'upload_token' => ['nullable', 'string', 'size:40'],
        ], CourseVideoUpload::messages());

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? 0,
            'is_free' => (bool) ($data['is_free'] ?? false),
        ];

        $oldPath = $video->storage_key;
        $path = null;
        $detected = null;

        if (filled($data['upload_token'] ?? null)) {
            $upload = DirectUpload::consume($data['upload_token'], (int) $request->user()->id, 'video', [
                'scope' => 'course',
                'course_id' => $course->id,
                'chapter_id' => $chapter->id,
            ]);
            $path = $upload['key'];
            $detected = $upload['duration'];
        } elseif ($request->hasFile('video_file')) {
            $detected = CourseVideoUpload::assertPlayable($request->file('video_file'));
            $path = MediaStorage::storeCourseVideo(
                $request->file('video_file'),
                $course->id,
                $chapter->id,
                $video->id
            );
        }

        if ($path !== null) {
            $payload['storage_provider'] = MediaStorage::disk();
            $payload['storage_key'] = $path;

            if ($detected && (int) $payload['duration_seconds'] === (int) $video->duration_seconds) {
                $payload['duration_seconds'] = $detected;
            }
        }

        $video->update($payload);

        if ($path !== null && $oldPath && $oldPath !== $path) {
            MediaStorage::delete($oldPath);
        }

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
