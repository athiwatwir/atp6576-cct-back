<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReceiptController;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Api\GuestOrderRequest;
use App\Http\Requests\Api\SubmitPaymentSlipRequest;
use App\Http\Resources\Student\OrderResource;
use App\Models\Order;
use App\Support\GuestCheckout;
use App\Support\Payments\OnlinePayment;
use App\Support\Payments\PaymentFees;
use App\Support\StorefrontCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GuestCheckoutController extends Controller
{
    public function __construct(
        private readonly GuestCheckout $guests,
        private readonly StorefrontCheckout $checkout,
        private readonly OnlinePayment $payments,
    ) {}

    public function quote(GuestOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'data' => $this->checkout->quote(
                $this->guests->quoteCustomer($data['email']),
                $data['items'],
                $data['coupon_code'] ?? null,
            ),
        ]);
    }

    public function store(GuestOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->guests->place(
            ['email' => $data['email'], 'name' => $data['name'] ?? null, 'phone' => $data['phone'] ?? null],
            $data['items'],
            $data['coupon_code'] ?? null,
            $data['notes'] ?? null,
            $data['shipping'] ?? [],
        );

        $order = $result['order'];

        return response()->json([
            'data' => [
                'order' => OrderResource::make($order)->resolve($request),
                'guest_token' => $order->guest_token,
                'account_created' => $result['account_created'],
                'account_email_sent' => $result['email_sent'],
                'login_url' => $this->guests->loginUrl(),
                ...$this->paymentLink($order),
            ],
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        $order = $this->order($token);

        $latest = $order->latestPayment;
        if ($latest) {
            $this->payments->refresh($latest);
            $order->refresh()->load(['items', 'latestPayment.slips']);
        }

        return response()->json([
            'data' => [
                'order' => OrderResource::make($order)->resolve(request()),
                'payment_options' => array_values(PaymentFees::quote($order, array_values(array_unique([
                    ...$this->payments->enabledMethods(),
                    PaymentMethod::BankTransfer->value,
                ])))),
                ...$this->paymentLink($order),
            ],
        ]);
    }

    public function slip(SubmitPaymentSlipRequest $request, string $token): JsonResponse
    {
        $order = $this->order($token);

        $order = $this->checkout->submitSlip(
            $order->user,
            $order,
            $request->file('slip'),
            $request->input('note'),
        );

        return response()->json([
            'data' => [
                'order' => OrderResource::make($order)->resolve($request),
            ],
        ]);
    }

    public function thaiQr(string $token): JsonResponse
    {
        $order = $this->order($token);
        $payment = $this->payments->startThaiQr($order);

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    public function card(Request $request, string $token): JsonResponse
    {
        $order = $this->order($token);
        $data = $request->validate(['token' => ['required', 'string', 'max:100']]);

        $payment = $this->payments->chargeCard(
            $order,
            $data['token'],
            fn () => $this->payments->cardReturnUrl($order),
        );

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    public function paymentStatus(string $token): JsonResponse
    {
        $order = $this->order($token);
        $payment = $order->payments()->latest('id')->first();

        if ($payment) {
            $payment = $this->payments->refresh($payment);
        }

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    public function simulate(Request $request, string $token): JsonResponse
    {
        $order = $this->order($token);
        $state = $request->validate(['state' => ['required', 'in:paid,failed']])['state'];
        $payment = $this->payments->simulateLatest($order, $state);

        return response()->json(['data' => $this->payments->payload($payment, $order)]);
    }

    public function receipt(string $token, ReceiptController $receipts): Response
    {
        return $receipts->respond($this->order($token), request()->boolean('inline'));
    }

    private function order(string $token): Order
    {
        $order = $this->guests->findByToken($token);

        if (! $order || ! $order->user) {
            abort(404, 'ไม่พบคำสั่งซื้อนี้');
        }

        return $order;
    }

    /**
     * @return array{payment_url: ?string, payment_url_expires_at: ?string}
     */
    private function paymentLink(Order $order): array
    {
        try {
            $link = $this->payments->linkFor($order);
        } catch (ValidationException) {
            return ['payment_url' => null, 'payment_url_expires_at' => null];
        }

        return [
            'payment_url' => route('payment.page', $link->token),
            'payment_url_expires_at' => $link->expires_at?->toIso8601String(),
        ];
    }
}
