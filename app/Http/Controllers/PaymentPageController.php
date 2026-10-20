<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Support\Payments\FakeGateway;
use App\Support\Payments\OnlinePayment;
use App\Support\Payments\PaymentFees;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentPageController extends Controller
{
    public function __construct(private readonly OnlinePayment $payments) {}

    public function show(string $token): View
    {
        $link = $this->payments->findLink($token);

        if (! $link) {
            return view('payment.unavailable', [
                'title' => 'ไม่พบลิงก์ชำระเงิน',
                'message' => 'ลิงก์นี้ไม่ถูกต้อง กรุณากลับไปที่หน้าออเดอร์แล้วกดชำระเงินอีกครั้ง',
                'returnUrl' => null,
            ]);
        }

        $order = $link->order;
        $paid = $order->payment_status === PaymentStatus::Paid->value;

        if (! $paid && ! $this->payments->linkUsable($link)) {
            return view('payment.unavailable', [
                'title' => 'ลิงก์หมดอายุ',
                'message' => 'ลิงก์ชำระเงินนี้หมดอายุแล้ว กรุณากลับไปที่หน้าออเดอร์แล้วกดชำระเงินอีกครั้ง',
                'returnUrl' => $this->payments->appReturnUrl($order),
            ]);
        }

        $latest = $order->payments()->latest('id')->first();
        $state = $this->payments->payload($this->payments->activeQr($order) ?? $latest, $order);

        return view('payment.show', [
            'title' => 'ชำระเงิน '.$order->order_no,
            'link' => $link,
            'order' => $order,
            'state' => $state,
            'methods' => $this->payments->enabledMethods(),
            'quotes' => PaymentFees::quote($order, $this->payments->enabledMethods()),
            'fakeQr' => $this->payments->isFake(PaymentMethod::ThaiQr),
            'fakeCard' => $this->payments->isFake(PaymentMethod::CreditCard),
            'omisePublicKey' => $this->payments->cardGateway()->publicKey(),
            'merchantName' => (string) config('payment.merchant_name'),
            'returnUrl' => $this->payments->appReturnUrl($order),
            'fakeTokens' => [
                'success' => FakeGateway::TOKEN_SUCCESS,
                'fail' => FakeGateway::TOKEN_FAIL,
                '3ds' => FakeGateway::TOKEN_3DS,
            ],
        ]);
    }

    public function thaiQr(string $token): JsonResponse
    {
        $link = $this->usableLink($token);
        $payment = $this->payments->startThaiQr($link->order);

        return response()->json($this->payments->payload($payment, $link->order));
    }

    public function card(Request $request, string $token): JsonResponse
    {
        $link = $this->usableLink($token);
        $data = $request->validate([
            'token' => ['required', 'string', 'max:100'],
        ]);

        $payment = $this->payments->chargeCard(
            $link->order,
            $data['token'],
            fn (Payment $payment) => $this->payments->returnUri($payment, route('payment.page', $link->token)),
        );

        return response()->json($this->payments->payload($payment, $link->order));
    }

    public function status(string $token): JsonResponse
    {
        $link = $this->payments->findLink($token);
        abort_unless($link !== null, 404);

        $order = $link->order;
        $payment = $order->payments()->latest('id')->first();

        if ($payment) {
            $payment = $this->payments->refresh($payment);
        }

        return response()->json($this->payments->payload($payment, $order));
    }

    public function simulate(Request $request, string $token): JsonResponse
    {
        $link = $this->payments->findLink($token);
        abort_unless($link !== null && $this->payments->isFake(), 404);

        $state = $request->validate([
            'state' => ['required', 'in:paid,failed'],
        ])['state'];

        $payment = $link->order->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->where('provider', 'fake')
            ->latest('id')
            ->firstOrFail();

        $payment = $this->payments->simulate($payment, $state);

        return response()->json($this->payments->payload($payment, $link->order));
    }

    public function returnFromGateway(Request $request, Payment $payment): RedirectResponse
    {
        $this->payments->refresh($payment);

        $next = (string) $request->query('next');

        return redirect()->away($next !== '' ? $next : $this->payments->appReturnUrl($payment->order));
    }

    public function fakeAuthorize(Request $request, Payment $payment): View|RedirectResponse
    {
        abort_unless($this->payments->isFake() && $payment->provider === 'fake', 404);

        $return = (string) $request->query('return');

        if ($request->isMethod('post')) {
            $state = $request->input('result') === 'approve' ? 'paid' : 'failed';
            $this->payments->simulate($payment, $state);

            return redirect()->away($return !== '' ? $return : $this->payments->appReturnUrl($payment->order));
        }

        return view('payment.fake-authorize', [
            'title' => 'ยืนยันตัวตน 3-D Secure (ทดสอบ)',
            'payment' => $payment,
            'action' => $request->fullUrl(),
        ]);
    }

    private function usableLink(string $token): PaymentLink
    {
        $link = $this->payments->findLink($token);
        abort_unless($link !== null, 404);

        if (! $this->payments->linkUsable($link)) {
            throw ValidationException::withMessages(['link' => 'ลิงก์ชำระเงินหมดอายุแล้ว']);
        }

        return $link;
    }
}
