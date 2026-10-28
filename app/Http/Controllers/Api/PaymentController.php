<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\OnlinePayment;
use App\Support\Payments\PaymentFees;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly OnlinePayment $payments) {}

    public function methods(): JsonResponse
    {
        return response()->json([
            'data' => [
                'methods' => $this->payments->enabledMethods(),
                'test_mode' => [
                    'thai_qr' => $this->payments->isFake(PaymentMethod::ThaiQr),
                    'credit_card' => $this->payments->isFake(PaymentMethod::CreditCard),
                ],
                'omise_public_key' => $this->payments->cardGateway()->publicKey(),
                'merchant_name' => (string) config('payment.merchant_name'),
                'fees' => PaymentFees::rules(),
            ],
        ]);
    }

    public function options(Request $request, Order $order): JsonResponse
    {
        $this->owned($request, $order);

        $methods = [...$this->payments->enabledMethods(), PaymentMethod::BankTransfer->value];

        return response()->json([
            'data' => array_values(PaymentFees::quote($order, array_values(array_unique($methods)))),
        ]);
    }

    public function link(Request $request, Order $order): JsonResponse
    {
        $this->owned($request, $order);

        $link = $this->payments->linkFor($order, $request->user()->id);

        return response()->json([
            'data' => [
                'url' => route('payment.page', $link->token),
                'expires_at' => $link->expires_at?->toIso8601String(),
            ],
        ]);
    }

    public function thaiQr(Request $request, Order $order): JsonResponse
    {
        $this->owned($request, $order);

        $payment = $this->payments->startThaiQr($order);

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    public function card(Request $request, Order $order): JsonResponse
    {
        $this->owned($request, $order);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:100'],
        ]);

        $payment = $this->payments->chargeCard(
            $order,
            $data['token'],
            fn (Payment $payment) => $this->payments->cardReturnUrl($order),
        );

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    public function simulate(Request $request, Order $order): JsonResponse
    {
        $this->owned($request, $order);

        $state = $request->validate(['state' => ['required', 'in:paid,failed']])['state'];
        $payment = $this->payments->simulateLatest($order, $state);

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        $this->owned($request, $order);

        $payment = $order->payments()->latest('id')->first();

        if ($payment) {
            $payment = $this->payments->refresh($payment);
        }

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    private function owned(Request $request, Order $order): void
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(404, 'ไม่พบออเดอร์นี้');
        }
    }
}
