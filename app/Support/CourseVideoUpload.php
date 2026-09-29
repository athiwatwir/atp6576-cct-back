<?php

namespace App\Support;

use App\Enums\MediaType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CourseVideoUpload
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'video_file' => array_merge(['required'], array_slice(MediaType::CourseVideo->rules(), 1)),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'video_file.required' => 'กรุณาเลือกไฟล์วิดีโอ',
            'video_file.uploaded' => 'อัปโหลดไฟล์วิดีโอไม่สำเร็จ (ไฟล์อาจใหญ่เกินขีดจำกัดของเซิร์ฟเวอร์ หรือไฟล์เสียหาย)',
            'video_file.mimetypes' => 'รองรับเฉพาะไฟล์ mp4, webm, mov',
            'video_file.max' => 'ขนาดไฟล์วิดีโอต้องไม่เกิน 500MB',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function store(Course $course, Chapter $chapter, array $data, UploadedFile $file): Video
    {
        $video = self::create($data);
        $video->update([
            'storage_key' => MediaStorage::storeCourseVideo($file, $course->id, $chapter->id, $video->id),
        ]);

        $sortOrder = ((int) $chapter->videos()->max('chapter_videos.sort_order')) + 1;
        $chapter->videos()->attach($video->id, [
            'sort_order' => $sortOrder,
            'is_required' => true,
        ]);

        return $video;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function storeStandalone(array $data, UploadedFile $file): Video
    {
        $video = self::create($data);
        $video->update([
            'storage_key' => MediaStorage::storeStandaloneVideo($file, $video->id),
        ]);

        return $video;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function create(array $data): Video
    {
        return Video::query()->create([
            'title' => $data['title'],
            'slug' => self::uniqueSlug((string) $data['title']),
            'description' => $data['description'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? 0,
            'is_free' => (bool) ($data['is_free'] ?? false),
            'storage_provider' => MediaStorage::disk(),
        ]);
    }

    public static function guard(Request $request): JsonResponse|RedirectResponse|null
    {
        if ($request->hasFile('video_file') && ! $request->file('video_file')->isValid()) {
            return self::errorResponse($request, self::invalidFileMessage($request->file('video_file')), $request->file('video_file'));
        }

        if (! $request->hasFile('video_file') && $request->header('Content-Length')
            && (int) $request->header('Content-Length') > self::phpMaxUploadBytes()) {
            return self::errorResponse($request, 'ขนาดไฟล์เกินขีดจำกัดของเซิร์ฟเวอร์ (สูงสุด '.ini_get('upload_max_filesize').')');
        }

        return null;
    }

    public static function errorResponse(Request $request, string $message, ?UploadedFile $file = null): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['video_file' => [$message]],
                'upload_error' => $file?->getError(),
                'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
            ], 422);
        }

        return back()->withErrors(['video_file' => $message]);
    }

    public static function successResponse(Request $request, string $message, string $redirectUrl): JsonResponse|RedirectResponse
    {
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

    private static function invalidFileMessage(UploadedFile $file): string
    {
        $limit = ini_get('upload_max_filesize');

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "ขนาดไฟล์เกินขีดจำกัดของเซิร์ฟเวอร์ (สูงสุด {$limit})",
            UPLOAD_ERR_PARTIAL => 'อัปโหลดไฟล์ไม่ครบ กรุณาลองใหม่',
            UPLOAD_ERR_NO_FILE => 'กรุณาเลือกไฟล์วิดีโอ',
            UPLOAD_ERR_NO_TMP_DIR => 'เซิร์ฟเวอร์ไม่มีโฟลเดอร์ชั่วคราวสำหรับอัปโหลด',
            UPLOAD_ERR_CANT_WRITE => 'เซิร์ฟเวอร์เขียนไฟล์ชั่วคราวไม่สำเร็จ',
            UPLOAD_ERR_EXTENSION => 'PHP extension บล็อกการอัปโหลดไฟล์นี้',
            default => 'อัปโหลดไฟล์วิดีโอไม่สำเร็จ ('.$file->getErrorMessage().')',
        };
    }

    private static function phpMaxUploadBytes(): int
    {
        $upload = self::iniSizeToBytes((string) ini_get('upload_max_filesize'));
        $post = self::iniSizeToBytes((string) ini_get('post_max_size'));

        return min($upload, $post);
    }

    private static function iniSizeToBytes(string $value): int
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

    private static function uniqueSlug(string $value): string
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
