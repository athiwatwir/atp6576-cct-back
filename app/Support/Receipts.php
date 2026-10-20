<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Mpdf\Mpdf;

/**
 * ใบเสร็จรับเงิน: ออกครั้งเดียวต่อออเดอร์ตอนชำระสำเร็จ เก็บสำเนาข้อมูล ณ วันชำระ แล้วสร้าง PDF จากสำเนานั้น
 */
class Receipts
{
    /**
     * ออกใบเสร็จถ้ายังไม่มี ใช้ซ้ำได้ (idempotent) คืน null ถ้าออเดอร์ยังไม่ชำระ
     */
    public function issue(Order $order): ?Receipt
    {
        if ($order->payment_status !== PaymentStatus::Paid->value) {
            return null;
        }

        $existing = Receipt::query()->where('order_id', $order->id)->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($order) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($receipt = Receipt::query()->where('order_id', $locked->id)->first()) {
                return $receipt;
            }

            $locked->load(['items', 'user']);
            $payment = $locked->payments()
                ->where('status', PaymentStatus::Paid->value)
                ->latest('paid_at')
                ->latest('id')
                ->first();

            $fee = round((float) ($payment?->fee_amount ?? 0), 2);
            $issuedAt = $locked->paid_at ?? now();

            return Receipt::query()->create([
                'receipt_no' => DocumentSequence::nextReceipt($issuedAt),
                'order_id' => $locked->id,
                'payment_id' => $payment?->id,
                'user_id' => $locked->user_id,
                'issued_at' => $issuedAt,
                'customer_name' => $locked->user?->name ?? $locked->shipping_name,
                'customer_email' => $locked->user?->email,
                'customer_phone' => $locked->user?->phone ?? $locked->shipping_phone,
                'customer_address' => $this->address($locked),
                'payment_method' => $payment?->payment_method,
                'payment_reference' => $payment?->metadata['charge_id'] ?? $payment?->reference ?? $payment?->transaction_id,
                'items' => $locked->items->map(fn ($item) => [
                    'name' => $item->item_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                ])->values()->all(),
                'subtotal' => $locked->subtotal,
                'discount_amount' => $locked->discount_amount,
                'shipping_amount' => $locked->shipping_amount,
                'fee_amount' => $fee,
                'total_amount' => round((float) $locked->total_amount + $fee, 2),
            ]);
        });
    }

    /**
     * ออกใบเสร็จให้ออเดอร์ที่ชำระแล้วถ้ายังไม่มี แล้วคืน PDF
     */
    public function pdfForOrder(Order $order): ?array
    {
        $receipt = $order->receipt ?? $this->issue($order);

        if (! $receipt) {
            return null;
        }

        return ['receipt' => $receipt, 'pdf' => $this->pdf($receipt)];
    }

    public function pdf(Receipt $receipt): string
    {
        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'garuda',
            'default_font_size' => 11,
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 16,
            'tempDir' => $tempDir,
        ]);

        $mpdf->SetTitle('ใบเสร็จรับเงิน '.$receipt->receipt_no);
        $mpdf->SetAuthor((string) config('receipt.company_name'));
        $mpdf->WriteHTML(view('pdf.receipt', $this->viewData($receipt))->render());

        return $mpdf->Output('', 'S');
    }

    public function downloadUrl(Receipt $receipt): string
    {
        return URL::temporarySignedRoute(
            'receipts.download',
            now()->addMinutes(max(5, (int) config('receipt.download_ttl_minutes', 60))),
            ['receipt' => $receipt->id],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function payload(?Receipt $receipt): ?array
    {
        if (! $receipt) {
            return null;
        }

        return [
            'receipt_no' => $receipt->receipt_no,
            'issued_at' => $receipt->issued_at?->toIso8601String(),
            'total' => (float) $receipt->total_amount,
            'download_url' => $this->downloadUrl($receipt),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Receipt $receipt): array
    {
        $logo = (string) config('receipt.logo');
        $logoPath = $logo !== '' ? public_path($logo) : null;

        return [
            'receipt' => $receipt,
            'order' => $receipt->order,
            'company' => [
                'name' => config('receipt.company_name'),
                'address' => config('receipt.company_address'),
                'tax_id' => config('receipt.tax_id'),
                'phone' => config('receipt.phone'),
                'email' => config('receipt.email'),
                'note' => config('receipt.note'),
                'logo' => $logoPath && is_file($logoPath) ? $logoPath : null,
            ],
            'methodLabel' => PaymentMethod::tryFrom((string) $receipt->payment_method)?->label() ?? $receipt->payment_method,
            'issuedAtText' => $this->thaiDate($receipt->issued_at),
            'amountText' => self::bahtText((float) $receipt->total_amount),
        ];
    }

    private function address(Order $order): ?string
    {
        $user = $order->user;
        $parts = $order->requires_shipping
            ? [$order->shipping_address_line1, $order->shipping_address_line2, $order->shipping_subdistrict, $order->shipping_district, $order->shipping_province, $order->shipping_postal_code]
            : [$user?->address_line1, $user?->subdistrict, $user?->district, $user?->province, $user?->postal_code];

        $text = trim(implode(' ', array_filter(array_map(fn ($part) => trim((string) $part), $parts))));

        return $text !== '' ? $text : null;
    }

    private function thaiDate(?\DateTimeInterface $date): string
    {
        if (! $date) {
            return '-';
        }

        $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $local = \Carbon\Carbon::instance($date)->timezone((string) config('receipt.timezone', 'Asia/Bangkok'));

        return $local->day.' '.$months[$local->month].' '.($local->year + 543).' '.$local->format('H:i').' น.';
    }

    /**
     * อ่านจำนวนเงินเป็นภาษาไทย เช่น 1,250.50 = หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์
     */
    public static function bahtText(float $amount): string
    {
        $amount = round(max(0, $amount), 2);
        $baht = (int) floor($amount);
        $satang = (int) round(($amount - $baht) * 100);

        if ($satang === 100) {
            $baht++;
            $satang = 0;
        }

        $text = ($baht > 0 ? self::thaiNumber($baht) : 'ศูนย์').'บาท';

        return $text.($satang > 0 ? self::thaiNumber($satang).'สตางค์' : 'ถ้วน');
    }

    private static function thaiNumber(int $number): string
    {
        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $rest = $number % 1000000;

            return self::thaiNumber($millions).'ล้าน'.($rest > 0 ? self::thaiNumber($rest) : '');
        }

        $digits = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $units = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน'];
        $chars = array_reverse(str_split((string) $number));
        $text = '';

        foreach ($chars as $position => $char) {
            $digit = (int) $char;

            if ($digit === 0) {
                continue;
            }

            $word = $digits[$digit];

            if ($position === 0 && $digit === 1 && $number > 9) {
                $word = 'เอ็ด';
            } elseif ($position === 1 && $digit === 1) {
                $word = '';
            } elseif ($position === 1 && $digit === 2) {
                $word = 'ยี่';
            }

            $text = $word.$units[$position].$text;
        }

        return $text;
    }
}
