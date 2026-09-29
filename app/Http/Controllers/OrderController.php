<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingCarrier;
use App\Enums\ShippingStatus;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use App\Support\CouponService;
use App\Support\DocumentSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly CouponService $coupons,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $paymentStatus = $request->string('payment_status')->toString();
        $shippingStatus = $request->string('shipping_status')->toString();

        $orders = Order::query()
            ->with(['user', 'latestPayment', 'items'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('order_no', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhere('shipping_name', 'like', "%{$search}%")
                        ->orWhere('shipping_phone', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($paymentStatus !== '', fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($shippingStatus !== '', fn ($query) => $query->where('shipping_status', $shippingStatus))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.orders.index', [
            'title' => 'ออเดอร์',
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'shipping_status' => $shippingStatus,
            ],
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'shippingStatuses' => ShippingStatus::options(),
        ]);
    }

    public function create(): View
    {
        $customers = $this->customers();

        return view('pages.orders.create', [
            'title' => 'สร้างออเดอร์',
            'customers' => $customers,
            'customersPayload' => $customers->map(fn (User $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ])->values(),
            'books' => $this->booksForSale(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $order = DB::transaction(function () use ($data) {
            $user = $this->resolveCustomer($data);

            if (! empty($data['copy_customer_to_shipping'])) {
                $data['shipping_name'] = $user->name;
                $data['shipping_phone'] = $user->phone ?: $data['shipping_phone'];
            }

            $productIds = collect($data['items'])->pluck('product_id')->unique()->all();
            $products = Product::query()
                ->books()
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                abort(422, 'มีหนังสือบางรายการไม่ถูกต้อง');
            }

            $subtotal = 0;
            $lineItems = [];

            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = $products[$item['product_id']];
                $quantity = (int) $item['quantity'];
                $unitPrice = $product->effective_price;
                $totalPrice = round($unitPrice * $quantity, 2);
                $subtotal += $totalPrice;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'metadata' => [
                        'type' => 'book',
                        'slug' => $product->slug,
                    ],
                ];
            }

            $shippingAmount = (float) ($data['shipping_amount'] ?? 0);
            $discountAmount = (float) ($data['discount_amount'] ?? 0);
            $couponId = null;

            if (! empty($data['coupon_code'])) {
                $applied = $this->coupons->quote($user, $data['coupon_code'], array_map(fn (array $line) => [
                    'product_id' => $line['product_id'],
                    'total_price' => $line['total_price'],
                ], $lineItems));
                $discountAmount = $applied['discount'];
                $couponId = $applied['coupon']->id;
            }

            $totalAmount = max(0, round($subtotal + $shippingAmount - $discountAmount, 2));

            $paymentMethod = PaymentMethod::from($data['payment_method']);
            $paymentStatus = $paymentMethod === PaymentMethod::Cod
                ? PaymentStatus::Pending
                : PaymentStatus::AwaitingVerification;

            $order = Order::query()->create([
                'order_no' => $this->generateOrderNo(),
                'user_id' => $user->id,
                'coupon_id' => $couponId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $totalAmount,
                'status' => OrderStatus::Pending->value,
                'payment_status' => $paymentStatus->value,
                'notes' => $data['notes'] ?? null,
                'requires_shipping' => true,
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address_line1' => $data['shipping_address_line1'],
                'shipping_address_line2' => null,
                'shipping_subdistrict' => $data['shipping_subdistrict'] ?? null,
                'shipping_district' => $data['shipping_district'],
                'shipping_province' => $data['shipping_province'],
                'shipping_postal_code' => $data['shipping_postal_code'],
                'shipping_status' => ShippingStatus::Pending->value,
            ]);

            foreach ($lineItems as $lineItem) {
                $order->items()->create($lineItem);
            }

            if ($couponId) {
                $this->coupons->commit($user, $order);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod->value,
                'amount' => $totalAmount,
                'status' => $paymentStatus->value,
                'metadata' => [
                    'created_by' => auth()->id(),
                    'channel' => 'admin',
                    'customer_mode' => $data['customer_mode'],
                ],
            ]);

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'สร้างออเดอร์เรียบร้อยแล้ว');
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product', 'payments', 'coupon']);

        return view('pages.orders.show', [
            'title' => $order->order_no,
            'order' => $order,
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'shippingStatuses' => ShippingStatus::options(),
            'shippingCarriers' => ShippingCarrier::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function edit(Order $order): View
    {
        $order->load(['user', 'items.product', 'latestPayment']);

        return view('pages.orders.edit', [
            'title' => 'แก้ไขออเดอร์',
            'order' => $order,
            'orderStatuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'shippingStatuses' => ShippingStatus::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $order) {
            $shippingStatus = ShippingStatus::from($data['shipping_status']);
            $paymentStatus = PaymentStatus::from($data['payment_status']);
            $orderStatus = OrderStatus::from($data['status']);

            $payload = [
                'status' => $orderStatus->value,
                'payment_status' => $paymentStatus->value,
                'shipping_status' => $shippingStatus->value,
                'shipping_amount' => (float) ($data['shipping_amount'] ?? 0),
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address_line1' => $data['shipping_address_line1'],
                'shipping_address_line2' => null,
                'shipping_subdistrict' => $data['shipping_subdistrict'] ?? null,
                'shipping_district' => $data['shipping_district'],
                'shipping_province' => $data['shipping_province'],
                'shipping_postal_code' => $data['shipping_postal_code'],
                'shipping_carrier' => $data['shipping_carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
            ];

            $payload['total_amount'] = max(
                0,
                round((float) $order->subtotal + $payload['shipping_amount'] - $payload['discount_amount'], 2)
            );

            if ($paymentStatus === PaymentStatus::Paid) {
                $payload['paid_at'] = $order->paid_at ?? now();
            } else {
                $payload['paid_at'] = null;
            }

            if ($orderStatus === OrderStatus::Cancelled) {
                $payload['cancelled_at'] = $order->cancelled_at ?? now();
            } else {
                $payload['cancelled_at'] = null;
            }

            $this->applyShippingSideEffects($payload, $shippingStatus, $order);

            if ($orderStatus === OrderStatus::Cancelled && $paymentStatus !== PaymentStatus::Paid) {
                $this->coupons->release($order);
            }

            $order->update($payload);

            $payment = $order->latestPayment ?? $order->payments()->latest('id')->first();

            if ($payment) {
                $payment->update([
                    'payment_method' => $data['payment_method'],
                    'status' => $paymentStatus->value,
                    'amount' => $payload['total_amount'],
                    'paid_at' => $paymentStatus === PaymentStatus::Paid
                        ? ($payment->paid_at ?? now())
                        : null,
                ]);
            } else {
                Payment::query()->create([
                    'order_id' => $order->id,
                    'payment_method' => $data['payment_method'],
                    'amount' => $payload['total_amount'],
                    'status' => $paymentStatus->value,
                    'paid_at' => $paymentStatus === PaymentStatus::Paid ? now() : null,
                    'metadata' => [
                        'created_by' => auth()->id(),
                        'channel' => 'admin',
                    ],
                ]);
            }
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'บันทึกออเดอร์เรียบร้อยแล้ว');
    }

    public function destroy(Order $order): RedirectResponse
    {
        if ($order->payment_status === PaymentStatus::Paid->value) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'ไม่สามารถลบออเดอร์ที่ชำระเงินแล้วได้');
        }

        DB::transaction(function () use ($order) {
            $order->payments()->delete();
            $order->items()->delete();
            $order->delete();
        });

        return redirect()
            ->route('orders.index')
            ->with('success', 'ลบออเดอร์เรียบร้อยแล้ว');
    }

    private function resolveCustomer(array $data): User
    {
        if (($data['customer_mode'] ?? 'existing') === 'existing') {
            return User::query()->findOrFail($data['user_id']);
        }

        $email = filled($data['customer_email'] ?? null)
            ? Str::lower(trim((string) $data['customer_email']))
            : null;
        $phone = filled($data['customer_phone'] ?? null)
            ? trim((string) $data['customer_phone'])
            : null;

        $user = null;

        if ($email !== null) {
            $user = User::withTrashed()->where('email', $email)->first();
        }

        if (! $user && $phone !== null) {
            $user = User::withTrashed()
                ->where('phone', $phone)
                ->when($email === null, fn ($query) => $query->whereNull('email'))
                ->latest('id')
                ->first();
        }

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }

            $user->update([
                'name' => $data['customer_name'],
                'email' => $email ?? $user->email,
                'phone' => $phone ?? $user->phone,
                'status' => $user->status === 'inactive' ? 'active' : $user->status,
            ]);
        } else {
            $user = User::query()->create([
                'name' => $data['customer_name'],
                'email' => $email,
                'phone' => $phone,
                'password' => Str::password(12),
                'status' => 'active',
                'email_verified_at' => $email ? now() : null,
            ]);
        }

        $studentRole = Role::query()->firstOrCreate(
            ['name' => 'student'],
            [
                'display_name' => 'นักเรียน',
                'description' => 'ลูกค้า / ผู้เรียน',
            ]
        );

        if (! $user->roles()->where('name', 'student')->exists()) {
            $user->roles()->attach($studentRole->id);
        }

        return $user->fresh();
    }

    public function updateShippingStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'shipping_status' => ['required', 'string', Rule::in(array_keys(ShippingStatus::options()))],
        ], [], [
            'shipping_status' => 'สถานะจัดส่ง',
        ]);

        $shippingStatus = ShippingStatus::from($data['shipping_status']);
        $oldStatus = (string) $order->shipping_status;

        $payload = [
            'shipping_status' => $shippingStatus->value,
            'status' => $order->status,
        ];

        $this->applyShippingSideEffects($payload, $shippingStatus, $order);

        $order->update($payload);

        Audit::log(
            action: 'order.shipping_status_updated',
            entity: $order,
            old: ['shipping_status' => $oldStatus],
            new: [
                'shipping_status' => $shippingStatus->value,
                'status' => $order->status,
            ],
            description: 'อัปเดตสถานะจัดส่งออเดอร์ '.$order->order_no.' เป็น '.$shippingStatus->label(),
        );

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'อัปเดตสถานะจัดส่งเรียบร้อยแล้ว');
    }

    public function updateTracking(Request $request, Order $order): RedirectResponse
    {
        $carrierValues = array_keys(ShippingCarrier::options());

        $data = $request->validate([
            'shipping_carrier' => ['required', 'string', Rule::in($carrierValues)],
            'shipping_carrier_custom' => [
                Rule::requiredIf(fn () => $request->input('shipping_carrier') === ShippingCarrier::Other->value),
                'nullable',
                'string',
                'max:100',
            ],
            'tracking_number' => ['required', 'string', 'max:100'],
        ], [], [
            'shipping_carrier' => 'บริษัทขนส่ง',
            'shipping_carrier_custom' => 'ชื่อบริษัทขนส่ง',
            'tracking_number' => 'หมายเลขพัสดุ',
        ]);

        $carrier = $data['shipping_carrier'] === ShippingCarrier::Other->value
            ? trim((string) ($data['shipping_carrier_custom'] ?? ''))
            : $data['shipping_carrier'];

        $old = [
            'shipping_carrier' => $order->shipping_carrier,
            'tracking_number' => $order->tracking_number,
            'shipping_status' => $order->shipping_status,
        ];

        $payload = [
            'shipping_carrier' => $carrier,
            'tracking_number' => trim($data['tracking_number']),
            'status' => $order->status,
        ];

        $currentShipping = ShippingStatus::tryFrom((string) $order->shipping_status) ?? ShippingStatus::Pending;

        if (in_array($currentShipping, [ShippingStatus::Pending, ShippingStatus::Ready], true)) {
            $shippingStatus = ShippingStatus::Shipped;
            $payload['shipping_status'] = $shippingStatus->value;
            $this->applyShippingSideEffects($payload, $shippingStatus, $order);
        } elseif ($currentShipping === ShippingStatus::Shipped && ! $order->shipped_at) {
            $payload['shipped_at'] = now();
        }

        $order->update($payload);

        Audit::log(
            action: 'order.tracking_updated',
            entity: $order,
            old: $old,
            new: [
                'shipping_carrier' => $order->shipping_carrier,
                'tracking_number' => $order->tracking_number,
                'shipping_status' => $order->shipping_status,
            ],
            description: 'บันทึกพัสดุออเดอร์ '.$order->order_no.' ('.$carrier.' / '.$order->tracking_number.')',
        );

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'บันทึกข้อมูลพัสดุเรียบร้อยแล้ว');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyShippingSideEffects(array &$payload, ShippingStatus $shippingStatus, Order $order): void
    {
        $orderStatus = OrderStatus::tryFrom((string) ($payload['status'] ?? $order->status))
            ?? OrderStatus::Pending;

        if (in_array($shippingStatus, [ShippingStatus::Shipped, ShippingStatus::Delivered], true)) {
            $payload['shipped_at'] = $order->shipped_at ?? now();
        } elseif ($shippingStatus === ShippingStatus::Pending || $shippingStatus === ShippingStatus::Ready) {
            $payload['shipped_at'] = null;
        }

        if ($shippingStatus === ShippingStatus::Delivered) {
            $payload['delivered_at'] = $order->delivered_at ?? now();
            if (in_array($orderStatus, [OrderStatus::Shipped, OrderStatus::Processing, OrderStatus::Pending], true)) {
                $payload['status'] = OrderStatus::Completed->value;
            }
        } else {
            $payload['delivered_at'] = null;
        }

        if ($shippingStatus === ShippingStatus::Shipped
            && in_array($orderStatus, [OrderStatus::Pending, OrderStatus::Processing], true)
        ) {
            $payload['status'] = OrderStatus::Shipped->value;
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function customers()
    {
        return User::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function booksForSale()
    {
        return Product::query()
            ->books()
            ->whereIn('status', ['active', 'draft'])
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'sale_price', 'stock', 'status', 'thumbnail']);
    }

    private function generateOrderNo(): string
    {
        return DocumentSequence::nextOrder();
    }
}
