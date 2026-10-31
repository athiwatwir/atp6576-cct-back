<?php

namespace App\Support;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * อัปโหลดไฟล์ใหญ่ (วิดีโอ เอกสาร) จากเบราว์เซอร์ขึ้น R2 โดยตรงแบบ multipart
 * เซิร์ฟเวอร์ออก presigned URL ให้ทีละ part แล้วรวมไฟล์เมื่อครบ ไฟล์ไม่ผ่าน PHP
 */
class DirectUpload
{
    public const VIDEO_TYPES = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
    ];

    public const DOCUMENT_TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'zip' => 'application/zip',
        'rar' => 'application/vnd.rar',
        '7z' => 'application/x-7z-compressed',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'mp3' => 'audio/mpeg',
        'm4a' => 'audio/mp4',
        'wav' => 'audio/wav',
    ];

    private const SIGN_TTL = '+60 minutes';

    public static function available(): bool
    {
        return MediaStorage::disk() === 'r2';
    }

    /**
     * @return array<string, string>
     */
    public static function types(string $kind): array
    {
        return $kind === 'document' ? self::DOCUMENT_TYPES : self::VIDEO_TYPES;
    }

    public static function maxBytes(string $kind = 'video'): int
    {
        return max(1, self::maxMb($kind)) * 1024 * 1024;
    }

    public static function maxLabel(string $kind = 'video'): string
    {
        $mb = self::maxMb($kind);

        return $mb % 1024 === 0 ? ($mb / 1024).'GB' : $mb.'MB';
    }

    public static function extensionsLabel(string $kind): string
    {
        return implode(', ', array_keys(self::types($kind)));
    }

    public static function partSize(int $size): int
    {
        $part = max(5, (int) config('media.video_part_mb')) * 1024 * 1024;

        return max($part, (int) ceil($size / 10000));
    }

    /**
     * @param  array{scope: string, course_id?: int|null, chapter_id?: int|null}  $target
     * @return array{token: string, part_size: int, part_count: int}
     */
    public static function start(int $userId, string $kind, array $target, string $filename, int $size): array
    {
        $field = self::field($kind);
        $types = self::types($kind);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! isset($types[$extension])) {
            throw ValidationException::withMessages([$field => 'รองรับเฉพาะไฟล์ '.self::extensionsLabel($kind)]);
        }

        if ($size < 1 || $size > self::maxBytes($kind)) {
            throw ValidationException::withMessages([$field => 'ขนาดไฟล์ต้องไม่เกิน '.self::maxLabel($kind)]);
        }

        $directory = match (true) {
            $kind === 'document' => MediaStorage::documentDirectory((int) $target['course_id'], (int) $target['chapter_id']),
            $target['scope'] === 'course' => MediaStorage::courseVideoDirectory((int) $target['course_id'], (int) $target['chapter_id']),
            default => MediaStorage::standaloneVideoDirectory(),
        };
        $key = $directory.'/'.Str::uuid()->toString().'.'.$extension;

        $options = [
            'Bucket' => self::bucket(),
            'Key' => $key,
            'ContentType' => $types[$extension],
        ];
        if ($kind === 'document') {
            $options['ContentDisposition'] = MediaStorage::contentDisposition($filename);
        }

        $result = self::client()->createMultipartUpload($options);

        $partSize = self::partSize($size);
        $token = Str::random(40);

        self::put($token, [
            'user_id' => $userId,
            'kind' => $kind,
            'target' => $target,
            'key' => $key,
            'upload_id' => (string) $result['UploadId'],
            'size' => $size,
            'filename' => Str::limit(basename($filename), 250, ''),
            'mime' => $types[$extension],
            'extension' => $extension,
            'part_size' => $partSize,
            'part_count' => (int) ceil($size / $partSize),
            'completed' => false,
        ]);

        return [
            'token' => $token,
            'part_size' => $partSize,
            'part_count' => (int) ceil($size / $partSize),
        ];
    }

    /**
     * @param  list<int>  $parts
     * @return array<int, string>
     */
    public static function sign(string $token, int $userId, array $parts): array
    {
        $session = self::session($token, $userId);
        abort_if($session['completed'], 409, 'อัปโหลดไฟล์นี้เสร็จแล้ว');

        $client = self::client();
        $urls = [];

        foreach (array_unique($parts) as $part) {
            abort_if($part < 1 || $part > $session['part_count'], 422, 'หมายเลข part ไม่ถูกต้อง');

            $command = $client->getCommand('UploadPart', [
                'Bucket' => self::bucket(),
                'Key' => $session['key'],
                'UploadId' => $session['upload_id'],
                'PartNumber' => $part,
            ]);

            $urls[$part] = (string) $client->createPresignedRequest($command, self::SIGN_TTL)->getUri();
        }

        return $urls;
    }

    /**
     * @param  array<int, array{part_number: int, etag: string}>  $parts
     */
    public static function complete(string $token, int $userId, array $parts): void
    {
        $session = self::session($token, $userId);
        if ($session['completed']) {
            return;
        }

        $field = self::field($session['kind'] ?? 'video');
        $list = collect($parts)
            ->map(fn (array $part) => ['PartNumber' => (int) $part['part_number'], 'ETag' => (string) $part['etag']])
            ->unique('PartNumber')
            ->sortBy('PartNumber')
            ->values();

        if ($list->count() !== $session['part_count'] || $list->last()['PartNumber'] !== $session['part_count']) {
            throw ValidationException::withMessages([$field => 'อัปโหลดไฟล์ไม่ครบ กรุณาลองใหม่']);
        }

        $client = self::client();
        $client->completeMultipartUpload([
            'Bucket' => self::bucket(),
            'Key' => $session['key'],
            'UploadId' => $session['upload_id'],
            'MultipartUpload' => ['Parts' => $list->all()],
        ]);

        $head = $client->headObject(['Bucket' => self::bucket(), 'Key' => $session['key']]);
        $info = match (true) {
            (int) $head['ContentLength'] !== $session['size'] => ['ok' => false, 'message' => 'ขนาดไฟล์บน R2 ไม่ตรงกับไฟล์ต้นฉบับ กรุณาอัปโหลดใหม่'],
            ($session['kind'] ?? 'video') === 'video' => self::inspect($session['key'], $session['size'], $session['extension']),
            default => ['ok' => true, 'message' => null],
        };

        if (! $info['ok']) {
            self::deleteObject($session['key']);
            Cache::forget(self::cacheKey($token));

            throw ValidationException::withMessages([$field => $info['message']]);
        }

        $session['completed'] = true;
        $session['duration'] = $info['duration'] ?? null;
        self::put($token, $session);
    }

    public static function abort(string $token, int $userId): void
    {
        $session = Cache::get(self::cacheKey($token));
        if (! is_array($session) || $session['user_id'] !== $userId) {
            return;
        }

        try {
            if ($session['completed']) {
                self::deleteObject($session['key']);
            } else {
                self::client()->abortMultipartUpload([
                    'Bucket' => self::bucket(),
                    'Key' => $session['key'],
                    'UploadId' => $session['upload_id'],
                ]);
            }
        } catch (Throwable $e) {
            report($e);
        }

        Cache::forget(self::cacheKey($token));
    }

    /**
     * ใช้ไฟล์ที่อัปโหลดเสร็จแล้ว ใช้ได้ครั้งเดียว
     *
     * @param  array{scope: string, course_id?: int|null, chapter_id?: int|null}  $target
     * @return array{key: string, duration: ?int, size: int, filename: string, mime: string}
     */
    public static function consume(string $token, int $userId, string $kind, array $target): array
    {
        $field = self::field($kind);
        $session = Cache::get(self::cacheKey($token));

        if (! is_array($session) || $session['user_id'] !== $userId || ! $session['completed'] || ($session['kind'] ?? 'video') !== $kind) {
            throw ValidationException::withMessages([$field => 'ไม่พบไฟล์ที่อัปโหลด หรืออัปโหลดยังไม่เสร็จ กรุณาอัปโหลดใหม่']);
        }

        $sameTarget = $session['target']['scope'] === $target['scope']
            && (int) ($session['target']['course_id'] ?? 0) === (int) ($target['course_id'] ?? 0)
            && (int) ($session['target']['chapter_id'] ?? 0) === (int) ($target['chapter_id'] ?? 0);

        if (! $sameTarget) {
            throw ValidationException::withMessages([$field => 'ไฟล์นี้อัปโหลดไว้สำหรับรายการอื่น']);
        }

        Cache::forget(self::cacheKey($token));

        return [
            'key' => $session['key'],
            'duration' => $session['duration'] ?? null,
            'size' => (int) $session['size'],
            'filename' => (string) ($session['filename'] ?? basename($session['key'])),
            'mime' => (string) ($session['mime'] ?? 'application/octet-stream'),
        ];
    }

    private static function field(string $kind): string
    {
        return $kind === 'document' ? 'file' : 'video_file';
    }

    private static function maxMb(string $kind): int
    {
        return (int) config($kind === 'document' ? 'media.document_max_mb' : 'media.video_max_mb');
    }

    /**
     * @return array{ok: bool, message: ?string, duration?: ?int}
     */
    private static function inspect(string $key, int $size, string $extension): array
    {
        $client = self::client();

        try {
            return VideoInspector::inspect(function (int $offset, int $length) use ($client, $key, $size): string {
                if ($offset >= $size) {
                    return '';
                }
                $end = min($size - 1, $offset + $length - 1);

                return (string) $client->getObject([
                    'Bucket' => self::bucket(),
                    'Key' => $key,
                    'Range' => "bytes={$offset}-{$end}",
                ])['Body'];
            }, $size, $extension);
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'ตรวจสอบไฟล์วิดีโอไม่สำเร็จ กรุณาลองใหม่'];
        }
    }

    private static function deleteObject(string $key): void
    {
        try {
            self::client()->deleteObject(['Bucket' => self::bucket(), 'Key' => $key]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function session(string $token, int $userId): array
    {
        $session = Cache::get(self::cacheKey($token));
        abort_unless(is_array($session) && $session['user_id'] === $userId, 404, 'ไม่พบรายการอัปโหลดนี้ หรือหมดอายุแล้ว');

        return $session;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private static function put(string $token, array $session): void
    {
        Cache::put(self::cacheKey($token), $session, now()->addHours(max(1, (int) config('media.video_upload_ttl_hours'))));
    }

    private static function cacheKey(string $token): string
    {
        return 'video-upload:'.$token;
    }

    private static function client(): S3Client
    {
        return Storage::disk('r2')->getClient();
    }

    private static function bucket(): string
    {
        return (string) config('filesystems.disks.r2.bucket');
    }
}
