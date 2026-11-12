<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\ShippingLabels;
use Symfony\Component\HttpFoundation\Response;

class ShippingLabelController extends Controller
{
    public function __construct(private readonly ShippingLabels $labels) {}

    /**
     * ใบปะหน้าพัสดุ A6 ของออเดอร์หนังสือ เปิดในเบราว์เซอร์เพื่อพิมพ์
     */
    public function show(Order $order): Response
    {
        abort_unless($order->isBook() && $order->requires_shipping, 404, 'ออเดอร์นี้ไม่มีการจัดส่ง');

        return response($this->labels->pdf([$order]), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="shipping-label-'.$order->order_no.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
