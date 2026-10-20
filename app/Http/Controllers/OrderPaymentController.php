<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\OnlinePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderPaymentController extends Controller
{
    public function __construct(private readonly OnlinePayment $payments) {}

    public function link(Request $request, Order $order): RedirectResponse
    {
        try {
            $link = $this->payments->linkFor($order, $request->user()?->id);
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return back()
            ->with('success', 'สร้างลิงก์ชำระเงินแล้ว ส่งให้ลูกค้าได้เลย')
            ->with('payment_link', route('payment.page', $link->token));
    }

    public function refresh(Order $order, Payment $payment): RedirectResponse
    {
        $this->ensureBelongs($order, $payment);

        $payment = $this->payments->refresh($payment);

        return back()->with('success', 'ตรวจสถานะแล้ว: '.(PaymentStatus::tryFrom((string) $payment->status)?->label() ?? $payment->status));
    }

    public function simulate(Request $request, Order $order, Payment $payment): RedirectResponse
    {
        $this->ensureBelongs($order, $payment);
        abort_unless($this->payments->isFake() && $payment->provider === 'fake' && $payment->status === PaymentStatus::Pending->value, 404);

        $state = $request->input('state') === 'failed' ? 'failed' : 'paid';
        $this->payments->simulate($payment, $state);

        return back()->with('success', $state === 'paid' ? 'จำลองชำระสำเร็จแล้ว' : 'จำลองชำระไม่สำเร็จแล้ว');
    }

    private function ensureBelongs(Order $order, Payment $payment): void
    {
        abort_unless((int) $payment->order_id === (int) $order->id, 404);
    }
}
