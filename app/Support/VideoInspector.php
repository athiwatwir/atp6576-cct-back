<?php

namespace App\Support;

/**
 * อ่านโครงสร้างไฟล์ MP4/MOV (ISO BMFF) เพื่อตรวจว่าเบราว์เซอร์เล่นได้หรือไม่ โดยอ่านเฉพาะส่วนหัวของแต่ละ atom และ moov
 */
class VideoInspector
{
    private const MAX_MOOV_BYTES = 64 * 1024 * 1024;

    private const H264_PROFILES = [66 => 'Baseline', 77 => 'Main', 88 => 'Extended', 100 => 'High', 110 => 'High 10', 122 => 'High 4:2:2', 244 => 'High 4:4:4', 44 => 'CAVLC 4:4:4'];

    private const H264_PLAYABLE = [66, 77, 88, 100];

    /**
     * @param  callable(int, int): string  $read  อ่าน $length ไบต์จากตำแหน่ง $offset
     * @return array{ok: bool, message: ?string, codec: ?string, profile: ?string, width: ?int, height: ?int, duration: ?int}
     */
    public static function inspect(callable $read, int $size, string $extension): array
    {
        $info = ['ok' => true, 'message' => null, 'codec' => null, 'profile' => null, 'width' => null, 'height' => null, 'duration' => null];

        if ($extension === 'webm') {
            if (! str_starts_with($read(0, 4), "\x1A\x45\xDF\xA3")) {
                return self::fail($info, 'ไฟล์นี้ไม่ใช่ WebM ที่ถูกต้อง');
            }

            return $info;
        }

        $moov = null;
        foreach (self::children(fn (int $o, int $l) => $read($o, $l), 0, $size) as $atom) {
            if ($atom['type'] === 'moov') {
                $moov = $atom;
                break;
            }
        }

        if ($moov === null) {
            return self::fail($info, 'ไฟล์วิดีโอไม่สมบูรณ์ (ไม่พบส่วน moov) มักเกิดจากการบันทึกหรือ export ที่ไม่จบ กรุณา export ไฟล์ใหม่');
        }

        if ($moov['size'] > self::MAX_MOOV_BYTES) {
            return $info;
        }

        $data = $read($moov['start'], $moov['size']);
        $local = fn (int $o, int $l) => substr($data, $o, $l);
        $body = $moov['header'];
        $end = strlen($data);

        $video = null;
        foreach (self::children($local, $body, $end) as $atom) {
            if ($atom['type'] === 'mvhd') {
                $info['duration'] = self::mvhdDuration(substr($data, $atom['start'] + $atom['header'], 32));
            }
            if ($atom['type'] === 'trak' && $video === null) {
                $video = self::videoTrack($data, $atom);
            }
        }

        if ($video === null) {
            return self::fail($info, 'ไฟล์นี้ไม่มีภาพวิดีโอ (มีแต่เสียงหรือไม่มี track ภาพ)');
        }

        $info = array_merge($info, $video);

        $message = match (true) {
            in_array($video['codec'], ['hvc1', 'hev1', 'dvh1', 'dvhe'], true) => 'วิดีโอใช้ codec H.265 (HEVC)',
            str_starts_with((string) $video['codec'], 'ap') => 'วิดีโอใช้ codec Apple ProRes',
            $video['codec'] === 'mp4v' => 'วิดีโอใช้ codec MPEG-4 Part 2',
            in_array($video['codec'], ['avc1', 'avc3'], true) && $video['profile_idc'] !== null && ! in_array($video['profile_idc'], self::H264_PLAYABLE, true) => 'วิดีโอเป็น H.264 แบบ '.$video['profile'],
            ! in_array($video['codec'], ['avc1', 'avc3', 'av01', 'vp09'], true) => 'วิดีโอใช้ codec '.$video['codec'],
            default => null,
        };

        unset($info['profile_idc']);

        if ($message !== null) {
            return self::fail($info, $message.' ซึ่งเบราว์เซอร์ส่วนใหญ่เล่นไม่ได้ (จะเห็นจอดำ) กรุณาแปลงเป็น MP4 แบบ H.264 (High/Main, 8-bit) + AAC ก่อนอัปโหลด');
        }

        return $info;
    }

    public static function inspectLocalFile(string $path, string $extension): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['ok' => true, 'message' => null, 'codec' => null, 'profile' => null, 'width' => null, 'height' => null, 'duration' => null];
        }

        try {
            return self::inspect(function (int $offset, int $length) use ($handle): string {
                fseek($handle, $offset);

                return (string) fread($handle, max(1, $length));
            }, (int) filesize($path), $extension);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{codec: ?string, profile: ?string, profile_idc: ?int, width: ?int, height: ?int}|null
     */
    private static function videoTrack(string $data, array $trak): ?array
    {
        $local = fn (int $o, int $l) => substr($data, $o, $l);
        $mdia = self::find($local, $trak, ['mdia']);
        if ($mdia === null) {
            return null;
        }

        $hdlr = self::find($local, $mdia, ['hdlr']);
        if ($hdlr === null || substr($data, $hdlr['start'] + $hdlr['header'] + 8, 4) !== 'vide') {
            return null;
        }

        $stsd = self::find($local, $mdia, ['minf', 'stbl', 'stsd']);
        if ($stsd === null) {
            return ['codec' => null, 'profile' => null, 'profile_idc' => null, 'width' => null, 'height' => null];
        }

        $entry = $stsd['start'] + $stsd['header'] + 8;
        $entrySize = unpack('N', substr($data, $entry, 4))[1] ?? 0;
        $codec = substr($data, $entry + 4, 4);
        $width = unpack('n', substr($data, $entry + 32, 2))[1] ?? null;
        $height = unpack('n', substr($data, $entry + 34, 2))[1] ?? null;

        $profileIdc = null;
        $profile = null;
        if (in_array($codec, ['avc1', 'avc3'], true)) {
            $entryEnd = min(strlen($data), $entry + $entrySize);
            foreach (self::children($local, $entry + 86, $entryEnd) as $child) {
                if ($child['type'] === 'avcC') {
                    $profileIdc = ord($data[$child['start'] + $child['header'] + 1]);
                    $profile = self::H264_PROFILES[$profileIdc] ?? (string) $profileIdc;
                    break;
                }
            }
        }

        return ['codec' => $codec, 'profile' => $profile, 'profile_idc' => $profileIdc, 'width' => $width, 'height' => $height];
    }

    /**
     * @param  list<string>  $path
     */
    private static function find(callable $read, array $parent, array $path): ?array
    {
        $current = $parent;
        foreach ($path as $type) {
            $next = null;
            foreach (self::children($read, $current['start'] + $current['header'], $current['start'] + $current['size']) as $atom) {
                if ($atom['type'] === $type) {
                    $next = $atom;
                    break;
                }
            }
            if ($next === null) {
                return null;
            }
            $current = $next;
        }

        return $current;
    }

    /**
     * @return \Generator<int, array{type: string, start: int, size: int, header: int}>
     */
    private static function children(callable $read, int $start, int $end): \Generator
    {
        $offset = $start;
        $count = 0;

        while ($offset + 8 <= $end && $count++ < 10000) {
            $hdr = $read($offset, 16);
            if (strlen($hdr) < 8) {
                return;
            }

            $size = unpack('N', substr($hdr, 0, 4))[1];
            $type = substr($hdr, 4, 4);
            $header = 8;

            if ($size === 1) {
                if (strlen($hdr) < 16) {
                    return;
                }
                $size = unpack('J', substr($hdr, 8, 8))[1];
                $header = 16;
            } elseif ($size === 0) {
                $size = $end - $offset;
            }

            if ($size < $header || ! preg_match('/^[\x20-\x7E]{4}$/', $type)) {
                return;
            }

            yield ['type' => $type, 'start' => $offset, 'size' => $size, 'header' => $header];
            $offset += $size;
        }
    }

    private static function mvhdDuration(string $body): ?int
    {
        if (strlen($body) < 20) {
            return null;
        }

        $version = ord($body[0]);
        if ($version === 1) {
            $scale = unpack('N', substr($body, 20, 4))[1] ?? 0;
            $duration = unpack('J', substr($body, 24, 8))[1] ?? 0;
        } else {
            $scale = unpack('N', substr($body, 12, 4))[1] ?? 0;
            $duration = unpack('N', substr($body, 16, 4))[1] ?? 0;
        }

        return $scale > 0 ? (int) round($duration / $scale) : null;
    }

    private static function fail(array $info, string $message): array
    {
        unset($info['profile_idc']);
        $info['ok'] = false;
        $info['message'] = $message;

        return $info;
    }
}
