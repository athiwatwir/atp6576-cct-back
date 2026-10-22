<?php

namespace App\Support;

use App\Enums\OrderKind;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSlip;
use App\Models\Product;
use App\Models\User;
use App\Support\Payments\PaymentFees;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorefrontCheckout
{
    public function __construct(
        private readonly CouponService $coupons,
        private readonly StudentPurchase $purchases,
    ) {}

    /**
     * @param  list<array{type:string,id:int|string,quantity?:int|string|null}>  $items
     * @return array{items:list<array<string,mixed>>,subtotal:float,discount:float,shipping:float,total:float,requires_shipping:bool,coupon:?array{code:string,description:?string}}
     */
    public function quote(User $student, array $items, ?string $couponCode = null): array
    {
        $lines = $this->resolveLines($items, lockBooks: false);
        $priced = $this->price($student, $lines, $couponCode);

        return [
            'items' => $lines->map(fn (array $line) => $this->publicLine($line))->values()->all(),
            'subtotal' => $priced['subtotal'],
            'discount' => $priced['discount'],
            'shipping' => 0.0,
            'total' => $priced['total'],
            'requires_shipping' => $this->requiresShipping($lines),
            'coupon' => $priced['coupon'],
        ];
    }

    /**
     * @param  list<array{type:string,id:int|string,quantity?:int|string|null}>  $items
     * @param  array<string, mixed>  $shipping
     */
    public function place(User $student, array $items, ?string $couponCode, ?string $notes, array $shipping): Order
    {
        $order = DB::transaction(function () use ($student, $items, $couponCode, $notes, $shipping) {
            $lines = $this->resolveLines($items, lockBooks: true);
            $needsShipping = $this->requiresShipping($lines);
            $this->assertShipping($needsShipping, $shipping);

            $priced = $this->price($student, $lines, $couponCode);

            $order = Order::query()->create([
                'order_no' => DocumentSequence::nextOrder(),
                'kind' => $lines->contains(fn (array $line) => $line['product_id'] !== null)
                    ? OrderKind::Book->value
                    : OrderKind::Enrollment->value,
                'user_id' => $student->id,
                'coupon_id' => $priced['coupon_id'],
                'subtotal' => $priced['subtotal'],
                'discount_amount' => $priced['discount'],
                'shipping_amount' => 0,
                'total_amount' => $priced['total'],
                'status' => OrderStatus::Pending->value,
                'payment_status' => PaymentStatus::Pending->value,
                'notes' => $notes,
                'requires_shipping' => $needsShipping,
                'shipping_name' => $needsShipping ? ($shipping['name'] ?? null) : null,
                'shipping_phone' => $needsShipping ? ($shipping['phone'] ?? null) : null,
                'shipping_address_line1' => $needsShipping ? ($shipping['address_line1'] ?? null) : null,
                'shipping_address_line2' => $needsShipping ? ($shipping['address_line2'] ?? null) : null,
                'shipping_subdistrict' => $needsShipping ? ($shipping['subdistrict'] ?? null) : null,
                'shipping_district' => $needsShipping ? ($shipping['district'] ?? null) : null,
                'shipping_province' => $needsShipping ? ($shipping['province'] ?? null) : null,
                'shipping_postal_code' => $needsShipping ? ($shipping['postal_code'] ?? null) : null,
                'shipping_status' => $needsShipping ? ShippingStatus::Pending->value : ShippingStatus::NotRequired->value,
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => PaymentMethod::BankTransfer->value,
                ...$this->transferAmounts($priced['total']),
                'status' => PaymentStatus::Pending->value,
                'metadata' => [
                    'channel' => 'storefront',
                    'created_by' => $student->id,
                ],
            ]);

            return $order;
        });

        $order->load(['items', 'latestPayment.slips']);

        if ((float) $order->total_amount <= 0 && $order->latestPayment) {
            $this->purchases->review($order->latestPayment, PaymentStatus::Paid);
            $order->refresh()->load(['items', 'latestPayment.slips']);
        }

        return $order;
    }

    public function submitSlip(User $student, Order $order, UploadedFile $slip, ?string $note): Order
    {
        if ((int) $order->user_id !== (int) $student->id) {
            abort(404);
        }

        if ($order->payment_status === PaymentStatus::Paid->value) {
            throw ValidationException::withMessages([
                'order' => 'ออเดอร์นี้ชำระเงินแล้ว',
            ]);
        }

        if (in_array($order->payment_status, [PaymentStatus::Refunded->value], true)) {
            throw ValidationException::withMessages([
                'order' => 'ออเดอร์นี้ไม่สามารถส่งสลิปได้',
            ]);
        }

        if ((float) $order->total_amount <= 0) {
            throw ValidationException::withMessages([
                'order' => 'ออเดอร์นี้ไม่มียอดที่ต้องชำระ',
            ]);
        }

        DB::transaction(function () use ($student, $order, $slip, $note) {
            $payment = $order->payments()->latest('id')->lockForUpdate()->first();

            if (! $payment) {
                $payment = Payment::query()->create([
                    'order_id' => $order->id,
                    'payment_method' => PaymentMethod::BankTransfer->value,
                    ...$this->transferAmounts((float) $order->total_amount),
                    'status' => PaymentStatus::Pending->value,
                    'metadata' => [
                        'channel' => 'storefront',
                        'created_by' => $student->id,
                    ],
                ]);
            }

            $path = MediaStorage::store($slip, 'payments/'.$order->id.'/slips');

            PaymentSlip::query()->create([
                'payment_id' => $payment->id,
                'file_path' => $path,
                'uploaded_by' => $student->id,
                'status' => 'pending',
                'note' => $note,
            ]);

            $payment->update([
                'payment_method' => PaymentMethod::BankTransfer->value,
                ...$this->transferAmounts((float) $order->total_amount),
                'status' => PaymentStatus::AwaitingVerification->value,
            ]);

            $order->update([
                'payment_status' => PaymentStatus::AwaitingVerification->value,
            ]);
        });

        return $order->refresh()->load(['items', 'latestPayment.slips']);
    }

    /**
     * @param  list<array{type:string,id:int|string,quantity?:int|string|null,plan_id?:int|string|null}>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function resolveLines(array $items, bool $lockBooks): Collection
    {
        $normalized = collect($items)
            ->map(fn (array $item) => [
                'type' => (string) ($item['type'] ?? ''),
                'id' => (int) ($item['id'] ?? 0),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'plan_id' => filled($item['plan_id'] ?? null) ? (int) $item['plan_id'] : null,
            ])
            ->filter(fn (array $item) => $item['id'] > 0 && in_array($item['type'], ['course', 'curriculum', 'exam', 'book'], true))
            ->groupBy(fn (array $item) => $item['type'].':'.$item['id'])
            ->map(function (Collection $group) {
                $first = $group->first();
                $quantity = $first['type'] === 'book' ? (int) $group->sum('quantity') : 1;

                return [
                    'type' => $first['type'],
                    'id' => $first['id'],
                    'quantity' => min(99, max(1, $quantity)),
                    'plan_id' => $first['plan_id'],
                ];
            })
            ->values();

        $hasBook = $normalized->contains(fn (array $item) => $item['type'] === 'book');
        $hasLearning = $normalized->contains(fn (array $item) => in_array($item['type'], ['course', 'curriculum', 'exam'], true));

        if ($hasBook && $hasLearning) {
            throw ValidationException::withMessages([
                'items' => 'แยกคำสั่งซื้อหนังสือกับสมัครเรียนคนละออเดอร์',
            ]);
        }

        if ($normalized->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'กรุณาเลือกอย่างน้อย 1 รายการ',
            ]);
        }

        $lines = $normalized->map(function (array $item) use ($lockBooks) {
            if ($item['type'] === 'course') {
                $course = Course::query()->with('activePricePlans')->where('status', 'published')->find($item['id']);
                if (! $course) {
                    throw ValidationException::withMessages([
                        'items' => 'มีวิชาที่ยังไม่เปิดขายหรือไม่พบในระบบ',
                    ]);
                }

                return $this->learningLine('course', $course, $course->name, $item['plan_id'], ['course_id' => $course->id]);
            }

            if ($item['type'] === 'curriculum') {
                $curriculum = Curriculum::query()->with('activePricePlans')->where('status', 'published')->find($item['id']);
                if (! $curriculum) {
                    throw ValidationException::withMessages([
                        'items' => 'มีหลักสูตรที่ยังไม่เปิดขายหรือไม่พบในระบบ',
                    ]);
                }

                return $this->learningLine('curriculum', $curriculum, $curriculum->name, $item['plan_id'], ['curriculum_id' => $curriculum->id]);
            }

            if ($item['type'] === 'exam') {
                $exam = Assessment::query()->with('activePricePlans')->forSale()->find($item['id']);
                if (! $exam) {
                    throw ValidationException::withMessages([
                        'items' => 'มีข้อสอบที่ยังไม่เปิดขายหรือไม่พบในระบบ',
                    ]);
                }

                return $this->learningLine('exam', $exam, $exam->title, $item['plan_id'], ['assessment_id' => $exam->id]);
            }

            $bookQuery = Product::query()->forSale();
            if ($lockBooks) {
                $bookQuery->lockForUpdate();
            }
            $book = $bookQuery->find($item['id']);
            if (! $book) {
                throw ValidationException::withMessages([
                    'items' => 'มีหนังสือที่ยังไม่เปิดขายหรือไม่พบในระบบ',
                ]);
            }

            if ($book->stock !== null && (int) $book->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => 'หนังสือ '.$book->name.' มีจำนวนไม่พอ',
                ]);
            }

            $price = round((float) $book->effective_price, 2);

            return $this->line($item['type'], $book->name, $price, $item['quantity'], [
                'product_id' => $book->id,
            ]);
        });

        return $lines->values();
    }

    /**
     * @return array{amount: float, fee_amount: float}
     */
    private function transferAmounts(float $total): array
    {
        $fee = PaymentFees::calculate(PaymentMethod::BankTransfer, round($total, 2));

        return [
            'amount' => round($total + $fee, 2),
            'fee_amount' => $fee,
        ];
    }

    /**
     * @param  array<string, int|null>  $foreign
     * @return array<string, mixed>
     */
    private function learningLine(string $type, Course|Curriculum|Assessment $owner, string $name, ?int $planId, array $foreign): array
    {
        $plan = PricePlans::resolve($owner, $planId);
        $line = $this->line(
            $type,
            PricePlans::itemName($name, $plan, $owner->activePricePlans->count()),
            $plan['price'],
            1,
            $foreign,
        );

        return [
            ...$line,
            'price_plan_id' => $plan['plan_id'],
            'access_days' => $plan['duration_days'],
            'metadata' => [
                ...$line['metadata'],
                'plan_name' => $plan['label'],
            ],
        ];
    }

    /**
     * @param  array<string, int|null>  $foreign
     * @return array<string, mixed>
     */
    private function line(string $type, string $name, float $unitPrice, int $quantity, array $foreign): array
    {
        return [
            'product_id' => $foreign['product_id'] ?? null,
            'course_id' => $foreign['course_id'] ?? null,
            'curriculum_id' => $foreign['curriculum_id'] ?? null,
            'assessment_id' => $foreign['assessment_id'] ?? null,
            'price_plan_id' => null,
            'access_days' => null,
            'item_name' => $name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $quantity, 2),
            'metadata' => ['type' => $type],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     * @return array{subtotal:float,discount:float,total:float,coupon_id:?int,coupon:?array{code:string,description:?string}}
     */
    private function price(User $student, Collection $lines, ?string $couponCode): array
    {
        $subtotal = round((float) $lines->sum('total_price'), 2);
        $discount = 0.0;
        $couponId = null;
        $coupon = null;

        if (filled($couponCode)) {
            $applied = $this->coupons->quote($student, (string) $couponCode, $lines->map(fn (array $line) => [
                'course_id' => $line['course_id'],
                'curriculum_id' => $line['curriculum_id'],
                'assessment_id' => $line['assessment_id'],
                'product_id' => $line['product_id'],
                'total_price' => $line['total_price'],
            ])->all());
            $discount = $applied['discount'];
            $couponId = $applied['coupon']->id;
            $coupon = [
                'code' => $applied['coupon']->code,
                'description' => $applied['coupon']->description,
            ];
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, round($subtotal - $discount, 2)),
            'coupon_id' => $couponId,
            'coupon' => $coupon,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     */
    private function requiresShipping(Collection $lines): bool
    {
        return $lines->contains(fn (array $line) => $line['product_id'] !== null);
    }

    /**
     * @param  array<string, mixed>  $shipping
     */
    private function assertShipping(bool $needsShipping, array $shipping): void
    {
        if (! $needsShipping) {
            return;
        }

        $required = [
            'name' => 'ชื่อผู้รับ',
            'phone' => 'เบอร์โทร',
            'address_line1' => 'ที่อยู่',
            'district' => 'อำเภอ/เขต',
            'province' => 'จังหวัด',
            'postal_code' => 'รหัสไปรษณีย์',
        ];

        $errors = [];

        foreach ($required as $field => $label) {
            if (blank($shipping[$field] ?? null)) {
                $errors['shipping.'.$field] = 'กรุณากรอก'.$label;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function publicLine(array $line): array
    {
        return [
            'type' => $line['metadata']['type'] ?? null,
            'id' => $line['course_id'] ?? $line['curriculum_id'] ?? $line['assessment_id'] ?? $line['product_id'],
            'name' => $line['item_name'],
            'quantity' => (int) $line['quantity'],
            'unit_price' => (float) $line['unit_price'],
            'total_price' => (float) $line['total_price'],
            'plan' => $line['product_id'] === null ? [
                'id' => $line['price_plan_id'],
                'name' => $line['metadata']['plan_name'] ?? null,
                'duration_days' => $line['access_days'],
            ] : null,
        ];
    }
}
