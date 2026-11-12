<?php

namespace App\Support;

use App\Enums\DocumentType;
use App\Enums\OrderKind;
use App\Models\DocumentSequence as DocumentSequenceModel;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DocumentSequence
{
    /**
     * รหัสวิชา: C{dayOfYear}{seq2} เช่น C26901
     */
    public static function nextCourse(?CarbonInterface $at = null): string
    {
        $at ??= now();
        $dayOfYear = sprintf('%03d', $at->dayOfYear);
        $seq = self::nextNumber(DocumentType::Course, $dayOfYear);

        return DocumentType::Course->prefix().$dayOfYear.sprintf('%02d', $seq);
    }

    /**
     * รหัสข้อสอบ: E{dayOfYear}{seq2} เช่น E26901
     */
    public static function nextExam(?CarbonInterface $at = null): string
    {
        $at ??= now();
        $dayOfYear = sprintf('%03d', $at->dayOfYear);
        $seq = self::nextNumber(DocumentType::Exam, $dayOfYear);

        return DocumentType::Exam->prefix().$dayOfYear.sprintf('%02d', $seq);
    }

    /**
     * รหัสหนังสือ: B{seq3} เช่น B001
     */
    public static function nextBook(): string
    {
        $seq = self::nextNumber(DocumentType::Book, 'global');

        return DocumentType::Book->prefix().sprintf('%03d', $seq);
    }

    /**
     * หมายเลขออเดอร์: {C|B}{ymd}{seq3} เช่น C261112001 (สมัครเรียน), B261112001 (หนังสือ)
     * แต่ละประเภทรันเลขแยกกันและรีเซ็ตทุกวัน
     */
    public static function nextOrder(OrderKind $kind, ?CarbonInterface $at = null): string
    {
        $at ??= now();
        $prefix = $kind->orderPrefix().$at->format('ymd');
        $seq = self::nextNumber(DocumentType::Order, $prefix);

        return $prefix.sprintf('%03d', $seq);
    }

    /**
     * เลขใบเสร็จรับเงิน: RC{ym}{seq4} เช่น RC26100001 เรียงต่อกันทั้งเดือน
     */
    public static function nextReceipt(?CarbonInterface $at = null): string
    {
        $at ??= now();
        $ym = $at->format('ym');
        $seq = self::nextNumber(DocumentType::Receipt, $ym);

        return DocumentType::Receipt->prefix().$ym.sprintf('%04d', $seq);
    }

    private static function nextNumber(DocumentType $type, string $periodKey): int
    {
        return (int) DB::transaction(function () use ($type, $periodKey) {
            DocumentSequenceModel::query()->firstOrCreate(
                [
                    'doc_type' => $type->value,
                    'period_key' => $periodKey,
                ],
                ['last_number' => 0]
            );

            $row = DocumentSequenceModel::query()
                ->where('doc_type', $type->value)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->firstOrFail();

            $row->increment('last_number');

            return $row->last_number;
        });
    }
}
