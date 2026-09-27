<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Video;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseChapterVideoController extends Controller
{
    public function store(Request $request, Course $course, Chapter $chapter): RedirectResponse|JsonResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);

        if ($request->hasFile('video_file') && ! $request->file('video_file')->isValid()) {
            return $this->invalidVideoUploadResponse($request, $request->file('video_file'));
        }

        if (! $request->hasFile('video_file') && $request->header('Content-Length')
            && (int) $request->header('Content-Length') > $this->phpMaxUploadBytes()) {
            $message = 'ขนาดไฟล์เกินขีดจำกัดของเซิร์ฟเวอร์ (สูงสุด '.ini_get('upload_max_filesize').')';

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['video_file' => [$message]],
                ], 422);
            }

            return back()->withErrors(['video_file' => $message]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'video_file' => array_merge(['required'], array_slice(MediaType::CourseVideo->rules(), 1)),
        ], [
            'video_file.required' => 'กรุณาเลือกไฟล์วิดีโอ',
            'video_file.uploaded' => 'อัปโหลดไฟล์วิดีโอไม่สำเร็จ (ไฟล์อาจใหญ่เกินขีดจำกัดของเซิร์ฟเวอร์ หรือไฟล์เสียหาย)',
            'video_file.mimetypes' => 'รองรับเฉพาะไฟล์ mp4, webm, mov',
            'video_file.max' => 'ขนาดไฟล์วิดีโอต้องไม่เกิน 500MB',
        ]);

        $video = Video::query()->create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? 0,
            'is_free' => (bool) ($data['is_free'] ?? false),
            'storage_provider' => MediaStorage::disk(),
        ]);

        $path = MediaStorage::storeCourseVideo(
            $request->file('video_file'),
            $course->id,
            $chapter->id,
            $video->id
        );

        $video->update([
            'storage_key' => $path,
        ]);

        $sortOrder = ((int) $chapter->videos()->max('chapter_videos.sort_order')) + 1;

        $chapter->videos()->attach($video->id, [
            'sort_order' => $sortOrder,
            'is_required' => true,
        ]);

        $message = 'เพิ่มวิดีโอบทเรียนเรียบร้อยแล้ว';
        $redirectUrl = route('courses.show', [
            'course' => $course,
            'tab' => 'chapters',
            'chapter' => $chapter->id,
        ]);

        session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl);
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

    private function invalidVideoUploadResponse(Request $request, \Illuminate\Http\UploadedFile $file): RedirectResponse|JsonResponse
    {
        $limit = ini_get('upload_max_filesize');
        $message = match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "ขนาดไฟล์เกินขีดจำกัดของเซิร์ฟเวอร์ (สูงสุด {$limit})",
            UPLOAD_ERR_PARTIAL => 'อัปโหลดไฟล์ไม่ครบ กรุณาลองใหม่',
            UPLOAD_ERR_NO_FILE => 'กรุณาเลือกไฟล์วิดีโอ',
            UPLOAD_ERR_NO_TMP_DIR => 'เซิร์ฟเวอร์ไม่มีโฟลเดอร์ชั่วคราวสำหรับอัปโหลด',
            UPLOAD_ERR_CANT_WRITE => 'เซิร์ฟเวอร์เขียนไฟล์ชั่วคราวไม่สำเร็จ',
            UPLOAD_ERR_EXTENSION => 'PHP extension บล็อกการอัปโหลดไฟล์นี้',
            default => 'อัปโหลดไฟล์วิดีโอไม่สำเร็จ ('.$file->getErrorMessage().')',
        };

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['video_file' => [$message]],
                'upload_error' => $file->getError(),
                'php_upload_max_filesize' => $limit,
                'php_post_max_size' => ini_get('post_max_size'),
            ], 422);
        }

        return back()->withErrors(['video_file' => $message]);
    }

    private function phpMaxUploadBytes(): int
    {
        $upload = $this->iniSizeToBytes((string) ini_get('upload_max_filesize'));
        $post = $this->iniSizeToBytes((string) ini_get('post_max_size'));

        return min($upload, $post);
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
