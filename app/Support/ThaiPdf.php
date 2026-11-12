<?php

namespace App\Support;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * mPDF ที่ตั้งฟอนต์ Noto Sans Thai ไว้แล้ว ใช้ร่วมกันระหว่างใบเสร็จและใบปะหน้าพัสดุ
 */
class ThaiPdf
{
    /**
     * @param  array<string, mixed>  $options  ตัวเลือก mPDF เพิ่มเติม เช่น format, margin_*
     */
    public static function make(array $options = []): Mpdf
    {
        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $config = (new ConfigVariables)->getDefaults();
        $fonts = (new FontVariables)->getDefaults();

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            // Noto Sans Thai จาก Google Fonts ที่ตัด MarkGlyphSets ออกแล้ว (mPDF ไม่รองรับ) ดู resources/fonts/README.md
            'fontDir' => [...$config['fontDir'], resource_path('fonts')],
            'fontdata' => $fonts['fontdata'] + [
                'notosansthai' => [
                    'R' => 'NotoSansThai-Regular.ttf',
                    'B' => 'NotoSansThai-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'notosansthai',
            'default_font_size' => 11,
            'tempDir' => $tempDir,
            ...$options,
        ]);
    }
}
