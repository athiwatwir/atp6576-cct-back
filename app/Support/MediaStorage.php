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

        return $file->storeAs($directory, $filename, [
            'disk' => self::disk(),
            'visibility' => 'public',
        ]);
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
            [
                'disk' => self::disk(),
                'visibility' => 'public',
            ]
        );

        return $path;
    }

    /**
     * Convert image to WebP and upload to a fixed path.
     */
    public static function storeWebp(UploadedFile $file, string $path, int $quality = 82): string
    {
        $webp = self::convertImageToWebp($file, $quality);

        Storage::disk(self::disk())->put($path, $webp, [
            'visibility' => 'public',
            'ContentType' => 'image/webp',
        ]);

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

    /**
     * Convert image to WebP, then upload as books/{id}/{id}.webp
     */
    public static function storeBookThumbnail(UploadedFile $file, int $bookId): string
    {
        return self::storeWebp($file, self::bookThumbnailPath($bookId));
    }

    public static function storeCurriculumThumbnail(UploadedFile $file, int $curriculumId): string
    {
        return self::storeWebp($file, self::curriculumThumbnailPath($curriculumId));
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

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = self::disk();

        if ($disk === 'r2') {
            return Storage::disk('r2')->url($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return Storage::disk('r2')->url($path);
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
