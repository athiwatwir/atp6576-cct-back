<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * ข้อความช่วงสิทธิ์เรียนของรายการในออเดอร์ (order_items.metadata.access จาก StudentPurchase::grantAccess())
 */
class AccessPeriod
{
    /**
     * เช่น "เรียนได้ 30 วัน · 31 ต.ค. 2569 – 30 พ.ย. 2569" หรือ "เรียนได้ไม่จำกัดเวลา · เริ่ม 31 ต.ค. 2569"
     *
     * @param  array{starts_at?: ?string, expires_at?: ?string}|null  $access
     */
    public static function summary(?array $access, ?int $days = null): ?string
    {
        if (empty($access['starts_at'])) {
            return null;
        }

        $start = Carbon::parse($access['starts_at']);

        if (empty($access['expires_at'])) {
            return 'เรียนได้ไม่จำกัดเวลา · เริ่ม '.self::shortDate($start);
        }

        $end = Carbon::parse($access['expires_at']);
        $days ??= (int) round($start->diffInDays($end));

        return 'เรียนได้ '.number_format($days).' วัน · '.self::shortDate($start).' – '.self::shortDate($end);
    }

    /**
     * วันที่แบบย่อ พ.ศ. ตามเวลาไทย เช่น 31 ต.ค. 2569
     */
    public static function shortDate(DateTimeInterface $date): string
    {
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $local = Carbon::instance($date)->timezone((string) config('receipt.timezone', 'Asia/Bangkok'));

        return $local->day.' '.$months[$local->month].' '.($local->year + 543);
    }
}
