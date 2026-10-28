<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Video;
use App\Support\MediaStorage;
use App\Support\VideoAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ส่งวิดีโอบทเรียนผ่าน Laravel แทนลิงก์ R2 ตรง
 * - ลิงก์ลงลายเซ็นและหมดอายุ (middleware `signed`)
 * - ตรวจสิทธิ์ดูทุกคำขอ
 * - รับเฉพาะคำขอจากแท็ก <video> (Sec-Fetch-Dest: video) เปิดลิงก์ในแท็บหรือดาวน์โหลดตรงจะถูกปฏิเสธ
 * - ส่งเป็นช่วงไม่เกิน CHUNK_BYTES ต่อคำขอ ไม่ส่งไฟล์ทั้งก้อน
 */
class VideoStreamController extends Controller
{
    public const CHUNK_BYTES = 2 * 1024 * 1024;

    public function show(Request $request, Video $video): Response
    {
        $this->assertPlayerRequest($request);

        $course = Course::query()
            ->where('status', 'published')
            ->find((int) $request->query('c'));

        if (! $course || ! VideoAccess::belongsToCourse($course, $video)) {
            abort(404);
        }

        $userId = (int) $request->query('u') ?: null;
        if (! VideoAccess::canWatch($userId, $course, $video)) {
            abort(403);
        }

        $key = (string) $video->storage_key;
        if ($key === '' || str_starts_with($key, 'http://') || str_starts_with($key, 'https://')) {
            abort(404);
        }

        if (MediaStorage::disk() !== 'r2' && Storage::disk('public')->exists($key)) {
            return $this->localResponse($request, Storage::disk('public')->path($key));
        }

        return $this->r2Response($request, $key);
    }

    private function assertPlayerRequest(Request $request): void
    {
        $dest = strtolower((string) $request->headers->get('Sec-Fetch-Dest', ''));

        if ($dest !== '') {
            if ($dest !== 'video' && $dest !== 'audio') {
                abort(403);
            }

            return;
        }

        // เบราว์เซอร์รุ่นเก่าที่ไม่ส่ง Sec-Fetch-Dest ต้องมาจากหน้าเว็บนักเรียน
        $referer = (string) $request->headers->get('Referer', '');
        $allowed = array_filter([
            config('services.storefront.app_url'),
            config('services.storefront.frontend_url'),
        ]);

        foreach ($allowed as $origin) {
            if (str_starts_with($referer, rtrim((string) $origin, '/').'/')) {
                return;
            }
        }

        abort(403);
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function range(Request $request, int $size): ?array
    {
        $header = (string) $request->headers->get('Range', '');
        if (! preg_match('/^bytes=(\d*)-(\d*)$/', trim($header), $match)) {
            return null;
        }

        if ($match[1] === '' && $match[2] === '') {
            return null;
        }

        if ($match[1] === '') {
            $start = max(0, $size - (int) $match[2]);
            $end = $size - 1;
        } else {
            $start = (int) $match[1];
            $end = $match[2] === '' ? $size - 1 : min((int) $match[2], $size - 1);
        }

        if ($start >= $size || $start > $end) {
            return null;
        }

        return [$start, min($end, $start + self::CHUNK_BYTES - 1)];
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $type, int $start, int $end, int $size): array
    {
        return [
            'Content-Type' => $type,
            'Content-Length' => (string) ($end - $start + 1),
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
        ];
    }

    private function r2Response(Request $request, string $key): Response
    {
        /** @var \Illuminate\Filesystem\AwsS3V3Adapter $disk */
        $disk = Storage::disk('r2');
        $client = $disk->getClient();
        $bucket = (string) config('filesystems.disks.r2.bucket');

        try {
            $head = $client->headObject(['Bucket' => $bucket, 'Key' => $key]);
        } catch (\Throwable) {
            abort(404);
        }

        $size = (int) $head['ContentLength'];
        $type = (string) ($head['ContentType'] ?: 'video/mp4');
        $range = $this->range($request, $size);

        if ($range === null) {
            return response('', 416, ['Content-Range' => "bytes */{$size}", 'Accept-Ranges' => 'bytes']);
        }

        [$start, $end] = $range;
        $object = $client->getObject(['Bucket' => $bucket, 'Key' => $key, 'Range' => "bytes={$start}-{$end}"]);
        $body = $object['Body'];

        return new StreamedResponse(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(64 * 1024);
                flush();
            }
        }, 206, $this->headers($type, $start, $end, $size));
    }

    private function localResponse(Request $request, string $path): Response
    {
        $size = (int) filesize($path);
        $range = $this->range($request, $size);

        if ($range === null) {
            return response('', 416, ['Content-Range' => "bytes */{$size}", 'Accept-Ranges' => 'bytes']);
        }

        [$start, $end] = $range;
        $type = mime_content_type($path) ?: 'video/mp4';

        return new StreamedResponse(function () use ($path, $start, $end) {
            $handle = fopen($path, 'rb');
            fseek($handle, $start);
            $left = $end - $start + 1;
            while ($left > 0 && ! feof($handle)) {
                $chunk = fread($handle, min(64 * 1024, $left));
                $left -= strlen($chunk);
                echo $chunk;
                flush();
            }
            fclose($handle);
        }, 206, $this->headers($type, $start, $end, $size));
    }
}
