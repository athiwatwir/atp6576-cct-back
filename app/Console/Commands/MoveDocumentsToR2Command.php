<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Support\DirectUpload;
use App\Support\MediaStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * ย้ายไฟล์เอกสารบทเรียนเดิมจาก disk public ของเซิร์ฟเวอร์ขึ้น R2 (path เดิม) เพื่อให้ลิงก์ CDN ใช้ได้
 * ต้องรันบนเซิร์ฟเวอร์ที่มีไฟล์อยู่จริง (storage/app/public/documents/...)
 */
class MoveDocumentsToR2Command extends Command
{
    protected $signature = 'media:documents-to-r2 {--dry-run : แสดงรายการโดยไม่อัปโหลด} {--delete-local : ลบไฟล์ในเครื่องหลังอัปโหลดสำเร็จ}';

    protected $description = 'ย้ายไฟล์เอกสารบทเรียนจาก storage/app/public ขึ้น R2';

    public function handle(): int
    {
        if (MediaStorage::disk() !== 'r2') {
            $this->error('ยังไม่ได้ตั้งค่า R2');

            return self::FAILURE;
        }

        $local = Storage::disk('public');
        $r2 = Storage::disk('r2');
        $moved = $missing = $skipped = 0;

        Document::withTrashed()->whereNotNull('file_path')->orderBy('id')->each(function (Document $document) use ($local, $r2, &$moved, &$missing, &$skipped) {
            $path = (string) $document->file_path;

            if ($r2->exists($path)) {
                $skipped++;

                return;
            }

            if (! $local->exists($path)) {
                $missing++;
                $this->warn("#{$document->id} ไม่พบไฟล์ทั้งในเครื่องและบน R2: {$path}");

                return;
            }

            if ($this->option('dry-run')) {
                $this->line("#{$document->id} จะอัปโหลด {$path}");

                return;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $stream = $local->readStream($path);
            $r2->writeStream($path, $stream, array_filter([
                'ContentType' => DirectUpload::DOCUMENT_TYPES[$extension] ?? $document->mime_type,
                'ContentDisposition' => MediaStorage::contentDisposition((string) ($document->file_name ?: basename($path))),
            ]));
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($this->option('delete-local')) {
                $local->delete($path);
            }

            $moved++;
            $this->info("#{$document->id} อัปโหลดแล้ว {$path}");
        });

        $this->newLine();
        $this->line("อัปโหลด {$moved} · อยู่บน R2 แล้ว {$skipped} · ไม่พบไฟล์ {$missing}");

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
