<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;

/**
 * ใบปะหน้ากล่องพัสดุขนาด A6 (105 x 148 มม.) หนึ่งออเดอร์ต่อหนึ่งหน้า
 */
class ShippingLabels
{
    /**
     * @param  iterable<Order>  $orders
     */
    public function pdf(iterable $orders): string
    {
        $mpdf = ThaiPdf::make([
            'format' => 'A6',
            'default_font_size' => 9,
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        $mpdf->SetTitle('ใบปะหน้าพัสดุ');
        $mpdf->SetAuthor((string) config('shipping_label.sender_name'));

        $first = true;

        foreach ($orders as $order) {
            if (! $first) {
                $mpdf->AddPage();
            }

            $mpdf->WriteHTML(view('pdf.shipping-label', $this->viewData($order))->render());
            $first = false;
        }

        return $mpdf->Output('', 'S');
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Order $order): array
    {
        $order->loadMissing(['items', 'latestPayment', 'user']);

        $max = max(1, (int) config('shipping_label.max_items', 4));
        $payment = $order->latestPayment;
        $isCod = $payment?->payment_method === PaymentMethod::Cod->value
            && $order->payment_status !== PaymentStatus::Paid->value;
        $logo = (string) config('receipt.logo');
        $logoPath = $logo !== '' ? public_path($logo) : null;

        return [
            'order' => $order,
            'sender' => [
                'name' => config('shipping_label.sender_name'),
                'phone' => config('shipping_label.sender_phone'),
                'address' => config('shipping_label.sender_address'),
                'logo' => $logoPath && is_file($logoPath) ? $logoPath : null,
            ],
            'recipientAddress' => collect([
                $order->shipping_address_line1,
                $order->shipping_address_line2,
                $order->shipping_subdistrict ? $this->prefix($order->shipping_province, 'subdistrict').$order->shipping_subdistrict : null,
                $order->shipping_district ? $this->prefix($order->shipping_province, 'district').$order->shipping_district : null,
                $order->shipping_province ? $this->prefix($order->shipping_province, 'province').$order->shipping_province : null,
            ])->map(fn ($part) => trim((string) $part))->filter()->implode(' '),
            'items' => $order->items->take($max),
            'moreItems' => max(0, $order->items->count() - $max),
            'totalQuantity' => (int) $order->items->sum('quantity'),
            'isCod' => $isCod,
            'codAmount' => (float) $order->total_amount,
            'printedAt' => now()->timezone((string) config('receipt.timezone', 'Asia/Bangkok'))->format('d/m/Y H:i'),
        ];
    }

    /**
     * คำนำหน้าที่อยู่ไทย กรุงเทพฯ ใช้ แขวง / เขต จังหวัดอื่นใช้ ต. / อ. / จ.
     */
    private function prefix(?string $province, string $part): string
    {
        $bangkok = in_array(trim((string) $province), ['กรุงเทพมหานคร', 'กรุงเทพฯ', 'กรุงเทพ'], true);

        return match ($part) {
            'subdistrict' => $bangkok ? 'แขวง' : 'ต.',
            'district' => $bangkok ? 'เขต' : 'อ.',
            default => $bangkok ? '' : 'จ.',
        };
    }
}
