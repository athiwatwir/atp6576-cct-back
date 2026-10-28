<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaStorage
{
    public static function disk(): string
    {
        $configured = filled(config('filesystems.disks.r2.key'))
            && filled(config('filesystems.disks.r2.secret'))
            && filled(config('filesystems.disks.r2.bucket'))
            && filled(config('filesystems.disks.r2.endpoint'));

        return $configured ? 'r2' : 'public';
    }

    /**
     * Options for Storage::put on the active media disk.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function putOptions(array $extra = []): array
    {
        $options = $extra;

        // R2 does not use S3 object ACLs; public files are served via R2_URL.
        if (self::disk() !== 'r2') {
            $options['visibility'] = 'public';
        }

        return $options;
    }

    /**
     * Options for UploadedFile::storeAs on the active media disk.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function writeOptions(array $extra = []): array
    {
        return [
            'disk' => self::disk(),
            ...self::putOptions($extra),
        ];
    }

    public static function courseThumbnailDirectory(int $courseId): string
    {
        return "courses/{$courseId}/thumbnails";
    }

    public static function courseThumbnailPath(int $courseId): string
    {
        return self::courseThumbnailDirectory($courseId)."/{$courseId}.webp";
    }

    public static function instructorImagePath(int $instructorId): string
    {
        return "instructors/{$instructorId}/{$instructorId}.webp";
    }

    public static function examThumbnailPath(int $examId): string
    {
        return "exams/{$examId}/{$examId}.webp";
    }

    public static function bookThumbnailPath(int $bookId): string
    {
        return "books/{$bookId}/{$bookId}.webp";
    }

    public static function curriculumThumbnailPath(int $curriculumId): string
    {
        return "curriculums/{$curriculumId}/{$curriculumId}.webp";
    }

    public static function standaloneVideoDirectory(): string
    {
        return 'videos';
    }

    public static function standaloneVideoPath(int $videoId, string $extension): string
    {
        $ext = strtolower(ltrim($extension, '.')) ?: 'mp4';

        return self::standaloneVideoDirectory()."/{$videoId}.{$ext}";
    }

    public static function courseVideoDirectory(int $courseId, int $chapterId): string
    {
        return "courses/{$courseId}/chapters/{$chapterId}/videos";
    }

    public static function courseVideoPath(int $courseId, int $chapterId, int $videoId, string $extension): string
    {
        $ext = strtolower(ltrim($extension, '.')) ?: 'mp4';

        return self::courseVideoDirectory($courseId, $chapterId)."/{$videoId}.{$ext}";
    }

    public static function store(UploadedFile $file, string $directory): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.strtolower($extension);

        return $file->storeAs($directory, $filename, self::writeOptions());
    }

    /**
     * Upload course chapter video as:
     * courses/{course_id}/chapters/{chapter_id}/videos/{video_id}.{ext}
     */
    public static function storeCourseVideo(
        UploadedFile $file,
        int $courseId,
        int $chapterId,
        int $videoId
    ): string {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4';
        $path = self::courseVideoPath($courseId, $chapterId, $videoId, $extension);

        $file->storeAs(
            self::courseVideoDirectory($courseId, $chapterId),
            basename($path),
            self::writeOptions()
        );

        return $path;
    }

    /**
     * Upload a video that is not attached to a course or chapter:
     * videos/{video_id}.{ext}
     */
    public static function storeStandaloneVideo(UploadedFile $file, int $videoId): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4';
        $path = self::standaloneVideoPath($videoId, $extension);

        $file->storeAs(
            self::standaloneVideoDirectory(),
            basename($path),
            self::writeOptions()
        );

        return $path;
    }

    /**
     * Convert image to WebP and upload to a fixed path.
     */
    public static function storeWebp(UploadedFile $file, string $path, int $quality = 82): string
    {
        $webp = self::convertImageToWebp($file, $quality);

        $written = Storage::disk(self::disk())->put(
            $path,
            $webp,
            self::putOptions(['ContentType' => 'image/webp'])
        );

        if ($written === false) {
            throw new RuntimeException('Unable to store image at '.$path);
        }

        return $path;
    }

    /**
     * Convert image to WebP, then upload as courses/{course_id}/thumbnails/{course_id}.webp
     */
    public static function storeCourseThumbnail(UploadedFile $file, int $courseId): string
    {
        return self::storeWebp($file, self::courseThumbnailPath($courseId));
    }

    /**
     * Convert image to WebP, then upload as instructors/{id}/{id}.webp
     */
    public static function storeInstructorImage(UploadedFile $file, int $instructorId): string
    {
        return self::storeWebp($file, self::instructorImagePath($instructorId));
    }

    /**
     * Convert image to WebP, then upload as exams/{id}/{id}.webp
     */
    public static function storeExamThumbnail(UploadedFile $file, int $examId): string
    {
        return self::storeWebp($file, self::examThumbnailPath($examId));
    }

    public static function questionImagePath(int $examId, int $questionId): string
    {
        return "exams/{$examId}/questions/{$questionId}.webp";
    }

    public static function storeQuestionImage(UploadedFile $file, int $examId, int $questionId): string
    {
        return self::storeWebp($file, self::questionImagePath($examId, $questionId));
    }

    /**
     * Convert image to WebP, then upload as books/{id}/{id}.webp
     */
    public static function storeBookThumbnail(UploadedFile $file, int $bookId): string
    {
        return self::storeWebp($file, self::bookThumbnailPath($bookId));
    }

    public static function bookGalleryDirectory(int $bookId): string
    {
        return "books/{$bookId}/gallery";
    }

    public static function storeBookImage(UploadedFile $file, int $bookId): string
    {
        return self::storeWebp($file, self::bookGalleryDirectory($bookId).'/'.Str::uuid()->toString().'.webp');
    }

    public static function bookVideoDirectory(int $bookId): string
    {
        return "books/{$bookId}/video";
    }

    public static function storeBookVideo(UploadedFile $file, int $bookId): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4');
        $filename = $bookId.'.'.$extension;

        $file->storeAs(self::bookVideoDirectory($bookId), $filename, self::writeOptions());

        return self::bookVideoDirectory($bookId).'/'.$filename;
    }

    public static function storeCurriculumThumbnail(UploadedFile $file, int $curriculumId): string
    {
        return self::storeWebp($file, self::curriculumThumbnailPath($curriculumId));
    }

    /**
     * รูปของแบนเนอร์ / บทความ: banners/{id}/{id}.webp, articles/{id}/{id}.webp
     */
    public static function contentImagePath(string $type, int $contentId): string
    {
        return Str::plural($type)."/{$contentId}/{$contentId}.webp";
    }

    public static function storeContentImage(UploadedFile $file, string $type, int $contentId): string
    {
        return self::storeWebp($file, self::contentImagePath($type, $contentId));
    }

    public static function avatarPath(int $userId): string
    {
        return "avatars/{$userId}/{$userId}.webp";
    }

    public static function storeAvatar(UploadedFile $file, int $userId): string
    {
        return self::storeWebp($file, self::avatarPath($userId));
    }

    /**
     * รูปทั่วไปที่ตั้งชื่อเป็น UUID แปลงเป็น WebP ยกเว้น GIF ที่เก็บไฟล์เดิมเพื่อให้ภาพเคลื่อนไหวยังเล่นได้
     */
    public static function storeImage(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());

        if ($extension === 'gif' || $file->getMimeType() === 'image/gif') {
            return self::store($file, $directory);
        }

        return self::storeWebp($file, rtrim($directory, '/').'/'.Str::uuid()->toString().'.webp');
    }

    public static function testimonialCoverPath(int $testimonialId): string
    {
        return "testimonials/{$testimonialId}/{$testimonialId}.webp";
    }

    public static function storeTestimonialCover(UploadedFile $file, int $testimonialId): string
    {
        return self::storeWebp($file, self::testimonialCoverPath($testimonialId));
    }

    public static function convertImageToWebp(UploadedFile $file, int $quality = 82): string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('PHP GD with WebP support is required.');
        }

        $binary = file_get_contents($file->getRealPath());
        if ($binary === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new RuntimeException('Unable to process uploaded image.');
        }

        if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        $ok = imagewebp($image, null, $quality);
        $webp = ob_get_clean();
        imagedestroy($image);

        if (! $ok || $webp === false || $webp === '') {
            throw new RuntimeException('Unable to convert image to WebP.');
        }

        return $webp;
    }

    public static function url(?string $path, int|string|null $version = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::withVersion($path, $version);
        }

        $disk = self::disk();

        if ($disk === 'r2') {
            return self::withVersion(Storage::disk('r2')->url($path), $version);
        }

        if (Storage::disk('public')->exists($path)) {
            return self::withVersion(Storage::disk('public')->url($path), $version);
        }

        return self::withVersion(Storage::disk('r2')->url($path), $version);
    }

    /**
     * Short-lived signed URL for private files such as lesson videos.
     * Falls back to the public URL when the file is not on R2 (local public disk or external URL).
     */
    public static function temporaryUrl(?string $path, int $minutes = 240): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (self::disk() !== 'r2' && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        try {
            return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\Throwable) {
            return self::url($path);
        }
    }

    private static function withVersion(?string $url, int|string|null $version): ?string
    {
        if (blank($url) || $version === null || $version === '') {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.$version;
    }

    public static function delete(?string $path): void
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        foreach (['r2', 'public'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable) {
                // Ignore missing credentials / missing objects on unused disks.
            }
        }
    }
}
