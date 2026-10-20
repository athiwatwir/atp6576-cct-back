<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Receipt;
use App\Support\Receipts;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    public function __construct(private readonly Receipts $receipts) {}

    /**
     * แอดมิน: ดาวน์โหลดใบเสร็จของออเดอร์ที่ชำระแล้ว (ออกให้ถ้ายังไม่มี)
     */
    public function order(Request $request, Order $order): Response
    {
        return $this->respond($order, $request->boolean('inline'));
    }

    /**
     * ลิงก์ signed ที่ API ส่งให้หน้าเว็บนักเรียน เปิดได้โดยไม่ต้องแนบ token
     */
    public function signed(Request $request, Receipt $receipt): Response
    {
        return $this->stream($receipt, $this->receipts->pdf($receipt), $request->boolean('inline'));
    }

    public function respond(Order $order, bool $inline = false): Response
    {
        $result = $this->receipts->pdfForOrder($order);

        abort_unless($result !== null, 404, 'ออเดอร์นี้ยังไม่ได้ชำระเงิน จึงยังไม่มีใบเสร็จ');

        return $this->stream($result['receipt'], $result['pdf'], $inline);
    }

    private function stream(Receipt $receipt, string $pdf, bool $inline): Response
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$receipt->filename().'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
