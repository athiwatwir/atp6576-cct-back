<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderKind;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReceiptController;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rule;
use App\Http\Requests\Api\StorefrontOrderRequest;
use App\Http\Requests\Api\SubmitPaymentSlipRequest;
use App\Http\Resources\Student\OrderResource;
use App\Models\Order;
use App\Support\StorefrontCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly StorefrontCheckout $checkout) {}

    public function quote(StorefrontOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'data' => $this->checkout->quote(
                $request->user(),
                $data['items'],
                $data['coupon_code'] ?? null,
            ),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $kind = (string) ($request->validate([
            'kind' => ['nullable', Rule::in([OrderKind::Book->value, OrderKind::Enrollment->value])],
        ])['kind'] ?? '');

        $orders = Order::query()
            ->with(['items', 'latestPayment.slips', 'receipt'])
            ->where('user_id', $request->user()->id)
            ->when($kind !== '', fn ($query) => $query->where('kind', $kind))
            ->latest('id')
            ->paginate(min(50, max(1, (int) $request->integer('per_page', 12))))
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function store(StorefrontOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $order = $this->checkout->place(
            $request->user(),
            $data['items'],
            $data['coupon_code'] ?? null,
            $data['notes'] ?? null,
            $data['shipping'] ?? [],
        );

        return OrderResource::make($order)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->owned($request, $order);

        $order->load(['items', 'latestPayment.slips', 'receipt']);

        return OrderResource::make($order);
    }

    public function receipt(Request $request, Order $order, ReceiptController $receipts): Response
    {
        $this->owned($request, $order);

        return $receipts->respond($order, $request->boolean('inline'));
    }

    public function pay(SubmitPaymentSlipRequest $request, Order $order): OrderResource
    {
        $this->owned($request, $order);

        $order = $this->checkout->submitSlip(
            $request->user(),
            $order,
            $request->file('slip'),
            $request->input('note'),
        );

        return OrderResource::make($order);
    }

    private function owned(Request $request, Order $order): void
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(404, 'ไม่พบออเดอร์นี้');
        }
    }
}
