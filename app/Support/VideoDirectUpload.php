<?php

namespace App\Support;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * อัปโหลดวิดีโอจากเบราว์เซอร์ขึ้น R2 โดยตรงแบบ multipart
 * เซิร์ฟเวอร์ออก presigned URL ให้ทีละ part แล้วรวมไฟล์เมื่อครบ ไฟล์ไม่ผ่าน PHP
 */
class VideoDirectUpload
{
    public const MIME_TYPES = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
    ];

    private const SIGN_TTL = '+60 minutes';

    public static function available(): bool
    {
        return MediaStorage::disk() === 'r2';
    }

    public static function maxBytes(): int
    {
        return max(1, (int) config('media.video_max_mb')) * 1024 * 1024;
    }

    public static function maxLabel(): string
    {
        $mb = (int) config('media.video_max_mb');

        return $mb % 1024 === 0 ? ($mb / 1024).'GB' : $mb.'MB';
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
    public static function start(int $userId, array $target, string $filename, int $size): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (! isset(self::MIME_TYPES[$extension])) {
            throw ValidationException::withMessages(['video_file' => 'รองรับเฉพาะไฟล์ mp4, webm, mov']);
        }

        if ($size < 1 || $size > self::maxBytes()) {
            throw ValidationException::withMessages(['video_file' => 'ขนาดไฟล์วิดีโอต้องไม่เกิน '.self::maxLabel()]);
        }

        $directory = $target['scope'] === 'course'
            ? MediaStorage::courseVideoDirectory((int) $target['course_id'], (int) $target['chapter_id'])
            : MediaStorage::standaloneVideoDirectory();
        $key = $directory.'/'.Str::uuid()->toString().'.'.$extension;

        $result = self::client()->createMultipartUpload([
            'Bucket' => self::bucket(),
            'Key' => $key,
            'ContentType' => self::MIME_TYPES[$extension],
        ]);

        $partSize = self::partSize($size);
        $token = Str::random(40);

        self::put($token, [
            'user_id' => $userId,
            'target' => $target,
            'key' => $key,
            'upload_id' => (string) $result['UploadId'],
            'size' => $size,
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

        $list = collect($parts)
            ->map(fn (array $part) => ['PartNumber' => (int) $part['part_number'], 'ETag' => (string) $part['etag']])
            ->unique('PartNumber')
            ->sortBy('PartNumber')
            ->values();

        if ($list->count() !== $session['part_count'] || $list->last()['PartNumber'] !== $session['part_count']) {
            throw ValidationException::withMessages(['video_file' => 'อัปโหลดไฟล์ไม่ครบ กรุณาลองใหม่']);
        }

        $client = self::client();
        $client->completeMultipartUpload([
            'Bucket' => self::bucket(),
            'Key' => $session['key'],
            'UploadId' => $session['upload_id'],
            'MultipartUpload' => ['Parts' => $list->all()],
        ]);

        $head = $client->headObject(['Bucket' => self::bucket(), 'Key' => $session['key']]);
        $info = (int) $head['ContentLength'] === $session['size']
            ? self::inspect($session['key'], $session['size'], $session['extension'])
            : ['ok' => false, 'message' => 'ขนาดไฟล์บน R2 ไม่ตรงกับไฟล์ต้นฉบับ กรุณาอัปโหลดใหม่'];

        if (! $info['ok']) {
            self::deleteObject($session['key']);
            Cache::forget(self::cacheKey($token));

            throw ValidationException::withMessages(['video_file' => $info['message']]);
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
     * ใช้ไฟล์ที่อัปโหลดเสร็จแล้วกับวิดีโอ ใช้ได้ครั้งเดียว
     *
     * @param  array{scope: string, course_id?: int|null, chapter_id?: int|null}  $target
     * @return array{key: string, duration: ?int}
     */
    public static function consume(string $token, int $userId, array $target): array
    {
        $session = Cache::get(self::cacheKey($token));

        if (! is_array($session) || $session['user_id'] !== $userId || ! $session['completed']) {
            throw ValidationException::withMessages(['video_file' => 'ไม่พบไฟล์วิดีโอที่อัปโหลด หรืออัปโหลดยังไม่เสร็จ กรุณาอัปโหลดใหม่']);
        }

        $sameTarget = $session['target']['scope'] === $target['scope']
            && (int) ($session['target']['course_id'] ?? 0) === (int) ($target['course_id'] ?? 0)
            && (int) ($session['target']['chapter_id'] ?? 0) === (int) ($target['chapter_id'] ?? 0);

        if (! $sameTarget) {
            throw ValidationException::withMessages(['video_file' => 'ไฟล์วิดีโอนี้อัปโหลดไว้สำหรับรายการอื่น']);
        }

        Cache::forget(self::cacheKey($token));

        return ['key' => $session['key'], 'duration' => $session['duration'] ?? null];
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
