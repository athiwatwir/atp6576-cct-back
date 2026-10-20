<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderKind;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Mail\MailService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentPurchase
{
    public function __construct(
        private readonly MailService $mailService,
        private readonly CouponService $coupons,
        private readonly Receipts $receipts,
    ) {}

    /**
     * @return array<string, list<array{id:int,name:string,price:float,plans:list<array<string,mixed>>}>>
     */
    public function catalog(): array
    {
        $card = fn (Course|Curriculum|Assessment $item, string $name) => [
            'id' => $item->id,
            'name' => $name,
            'price' => $item->effective_price,
            'plans' => PricePlans::payload($item),
        ];

        return [
            'course' => Course::query()
                ->with('activePricePlans')
                ->where('status', 'published')
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'sale_price'])
                ->map(fn (Course $course) => $card($course, $course->name))
                ->values()
                ->all(),
            'curriculum' => Curriculum::query()
                ->with('activePricePlans')
                ->where('status', 'published')
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'sale_price'])
                ->map(fn (Curriculum $curriculum) => $card($curriculum, $curriculum->name))
                ->values()
                ->all(),
            'exam' => Assessment::query()
                ->with('activePricePlans')
                ->where('type', 'primary_exam')
                ->where('status', 'published')
                ->orderBy('title')
                ->get(['id', 'title', 'price'])
                ->map(fn (Assessment $exam) => $card($exam, $exam->title))
                ->values()
                ->all(),
        ];
    }

    public function hasPaidOrder(User $student, ?Order $except = null): bool
    {
        return Order::query()
            ->where('user_id', $student->id)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->when($except, fn ($query) => $query->where('id', '!=', $except->id))
            ->exists();
    }

    /**
     * @param  list<array{type:string,id:int}>  $items
     */
    public function createOrder(User $student, array $items, ?string $notes, ?string $couponCode = null): Order
    {
        $lines = $this->resolveLines($items);

        return DB::transaction(function () use ($student, $lines, $notes, $couponCode) {
            $subtotal = round($lines->sum('total_price'), 2);
            $discount = 0.0;
            $couponId = null;

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
            }

            $order = Order::query()->create([
                'order_no' => DocumentSequence::nextOrder(),
                'kind' => OrderKind::Enrollment->value,
                'user_id' => $student->id,
                'coupon_id' => $couponId,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_amount' => 0,
                'total_amount' => max(0, round($subtotal - $discount, 2)),
                'status' => OrderStatus::Pending->value,
                'payment_status' => PaymentStatus::Pending->value,
                'notes' => $notes,
                'requires_shipping' => false,
                'shipping_status' => ShippingStatus::NotRequired->value,
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'payment_method' => PaymentMethod::BankTransfer->value,
                'amount' => max(0, round($subtotal - $discount, 2)),
                'status' => PaymentStatus::Pending->value,
                'metadata' => [
                    'created_by' => auth()->id(),
                    'channel' => 'student-admin',
                ],
            ]);

            return $order;
        });
    }

    /**
     * เปลี่ยนสถานะชำระ ใช้กับ QR / บัตร / แอดมินอนุมัติสลิป / ออเดอร์ยอด 0
     * ชำระสำเร็จครั้งแรกจะส่งอีเมลยืนยันการสั่งซื้อหลังตอบ request แล้ว
     */
    public function review(Payment $payment, PaymentStatus $status): void
    {
        $paidOrderId = null;

        DB::transaction(function () use ($payment, $status, &$paidOrderId) {
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $wasPaid = $order->payment_status === PaymentStatus::Paid->value;
            $isPaid = $status === PaymentStatus::Paid;

            $payment->update([
                'status' => $status->value,
                'paid_at' => $isPaid ? ($payment->paid_at ?? now()) : null,
            ]);

            $payload = [
                'payment_status' => $status->value,
                'paid_at' => $isPaid ? ($order->paid_at ?? now()) : null,
            ];

            if ($isPaid && $order->status === OrderStatus::Pending->value) {
                $payload['status'] = OrderStatus::Completed->value;
            }

            $order->update($payload);

            if ($isPaid && ! $wasPaid && $order->user) {
                if ($order->coupon_id) {
                    $this->coupons->commit($order->user, $order);
                }

                $this->grantAccess($order->user, $order);
                $this->receipts->issue($order);
                $paidOrderId = $order->id;
            }
        });

        if ($paidOrderId !== null) {
            dispatch(fn () => app(self::class)->sendPaidConfirmation($paidOrderId))->afterResponse();
        }
    }

    public function sendPaidConfirmation(int $orderId): bool
    {
        $order = Order::query()->with(['items', 'user', 'latestPayment'])->find($orderId);

        if (! $order?->user?->email || $order->payment_status !== PaymentStatus::Paid->value) {
            return false;
        }

        $paid = $order->payments()->where('status', PaymentStatus::Paid->value)->latest('id')->first();
        $receipt = $this->receiptPdf($order);

        try {
            $this->mailService->sendCustomerPurchase(
                to: $order->user,
                order: $order,
                queue: false,
                amount: (float) ($paid?->amount ?? $order->total_amount),
                actionUrl: $this->studentLoginUrl(),
                actionText: $order->isBook() ? 'ดูคำสั่งซื้อ' : 'เข้าสู่ระบบเพื่อเริ่มเรียน',
                receipt: $receipt['receipt'] ?? null,
                receiptPdf: $receipt['pdf'] ?? null,
            );

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * ถ้าสร้าง PDF ไม่ได้ ยังส่งอีเมลยืนยันการสั่งซื้อต่อโดยไม่แนบไฟล์
     *
     * @return array{receipt: \App\Models\Receipt, pdf: string}|null
     */
    private function receiptPdf(Order $order): ?array
    {
        try {
            return $this->receipts->pdfForOrder($order);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function studentLoginUrl(): string
    {
        $explicit = config('services.storefront.app_url');
        $source = is_string($explicit) && $explicit !== '' ? $explicit : (string) config('services.storefront.frontend_url');

        foreach (explode(',', $source) as $origin) {
            $origin = trim($origin);

            if ($origin !== '' && $origin !== '*') {
                return rtrim($origin, '/').'/login';
            }
        }

        return (string) config('app.url');
    }

    public function pay(User $student, Order $order, string $method): array
    {
        if ($order->payment_status === PaymentStatus::Paid->value) {
            return ['first_purchase' => false, 'mailed' => false, 'already_paid' => true];
        }

        $password = null;
        $firstPurchase = false;
        $paidNow = false;

        DB::transaction(function () use ($student, $order, $method, &$password, &$firstPurchase, &$paidNow) {
            $order->refresh();

            if ($order->payment_status === PaymentStatus::Paid->value) {
                return;
            }

            if ($order->coupon_id) {
                $this->coupons->commit($student, $order);
            }

            $firstPurchase = ! $this->hasPaidOrder($student, $order);
            $paidNow = true;

            if ($firstPurchase) {
                $password = Str::password(12);
                $student->update(['password' => $password]);
            }

            $order->update([
                'status' => OrderStatus::Completed->value,
                'payment_status' => PaymentStatus::Paid->value,
                'paid_at' => now(),
            ]);

            $payment = $order->payments()->latest('id')->first();
            $paymentPayload = [
                'payment_method' => $method,
                'status' => PaymentStatus::Paid->value,
                'amount' => $order->total_amount,
                'fee_amount' => 0,
                'paid_at' => now(),
            ];

            if ($payment) {
                $payment->update($paymentPayload);
            } else {
                Payment::query()->create([
                    'order_id' => $order->id,
                    ...$paymentPayload,
                    'metadata' => [
                        'created_by' => auth()->id(),
                        'channel' => 'student-admin',
                    ],
                ]);
            }

            $this->grantAccess($student, $order);
            $this->receipts->issue($order);

            Audit::log(
                'student.purchase_paid',
                $student,
                description: 'ชำระเงินออเดอร์ '.$order->order_no,
                new: [
                    'order_no' => $order->order_no,
                    'total' => (string) $order->total_amount,
                    'first_purchase' => $firstPurchase,
                ],
            );
        });

        if (! $paidNow) {
            return ['first_purchase' => false, 'mailed' => false, 'already_paid' => true];
        }

        $mailed = false;

        if ($order->payment_status === PaymentStatus::Paid->value && $student->email) {
            try {
                $receipt = $this->receiptPdf($order);

                $this->mailService->sendCustomerPurchase(
                    to: $student,
                    order: $order->load('items'),
                    password: $firstPurchase ? $password : null,
                    queue: false,
                    actionUrl: $this->studentLoginUrl(),
                    receipt: $receipt['receipt'] ?? null,
                    receiptPdf: $receipt['pdf'] ?? null,
                );
                $mailed = true;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return [
            'first_purchase' => $firstPurchase,
            'mailed' => $mailed,
            'already_paid' => false,
        ];
    }

    /**
     * @param  list<array{type:string,id:int|string,plan_id?:int|string|null}>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function resolveLines(array $items): Collection
    {
        $unique = collect($items)
            ->map(fn (array $item) => [
                'type' => $item['type'],
                'id' => (int) $item['id'],
                'plan_id' => filled($item['plan_id'] ?? null) ? (int) $item['plan_id'] : null,
            ])
            ->unique(fn (array $item) => $item['type'].':'.$item['id'])
            ->values();

        $lines = $unique->map(function (array $item) {
            $model = match ($item['type']) {
                'course' => Course::query()->with('activePricePlans')->where('status', 'published')->find($item['id']),
                'curriculum' => Curriculum::query()->with('activePricePlans')->where('status', 'published')->find($item['id']),
                'exam' => Assessment::query()->with('activePricePlans')->where('type', 'primary_exam')->where('status', 'published')->find($item['id']),
                default => null,
            };

            if (! $model) {
                throw ValidationException::withMessages([
                    'items' => 'มีรายการที่ยังไม่เผยแพร่หรือไม่พบในระบบ',
                ]);
            }

            $name = $model instanceof Assessment ? $model->title : $model->name;
            $plan = PricePlans::resolve($model, $item['plan_id']);

            return [
                'product_id' => null,
                'course_id' => $item['type'] === 'course' ? $model->id : null,
                'curriculum_id' => $item['type'] === 'curriculum' ? $model->id : null,
                'assessment_id' => $item['type'] === 'exam' ? $model->id : null,
                'price_plan_id' => $plan['plan_id'],
                'access_days' => $plan['duration_days'],
                'item_name' => PricePlans::itemName($name, $plan, $model->activePricePlans->count()),
                'quantity' => 1,
                'unit_price' => $plan['price'],
                'total_price' => $plan['price'],
                'metadata' => ['type' => $item['type'], 'plan_name' => $plan['label']],
            ];
        });

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'กรุณาเลือกอย่างน้อย 1 รายการ',
            ]);
        }

        return $lines;
    }

    /**
     * เปิดสิทธิ์ตามแพ็กเกจของแต่ละรายการ access_days ว่าง = ไม่จำกัดเวลา
     * สิทธิ์เดียวกันที่ได้จากหลายรายการในออเดอร์เดียว ใช้ระยะเวลาที่ยาวที่สุด ไม่บวกซ้อน
     */
    private function grantAccess(User $student, Order $order): void
    {
        $order->loadMissing('items');

        /** @var array<string, array{course:?int,curriculum:?int,assessment:?int,days:?int}> $grants */
        $grants = [];
        $add = function (?int $courseId, ?int $curriculumId, ?int $assessmentId, ?int $days) use (&$grants) {
            $key = ($courseId ?? 0).':'.($curriculumId ?? 0).':'.($assessmentId ?? 0);
            $current = $grants[$key]['days'] ?? false;

            $grants[$key] = [
                'course' => $courseId,
                'curriculum' => $curriculumId,
                'assessment' => $assessmentId,
                'days' => match (true) {
                    $current === false => $days,
                    $current === null || $days === null => null,
                    default => max($current, $days),
                },
            ];
        };

        foreach ($order->items as $item) {
            $days = $item->access_days !== null ? (int) $item->access_days : null;

            if ($item->course_id) {
                $add((int) $item->course_id, null, null, $days);
            }

            if ($item->curriculum_id) {
                $add(null, (int) $item->curriculum_id, null, $days);

                $curriculum = Curriculum::query()->find($item->curriculum_id);

                if ($curriculum) {
                    foreach ($curriculum->courses()->pluck('courses.id') as $courseId) {
                        $add((int) $courseId, null, null, $days);
                    }

                    $examIds = $curriculum->assessments()
                        ->whereIn('assessments.type', ['exam', 'primary_exam'])
                        ->pluck('assessments.id');

                    foreach ($examIds as $assessmentId) {
                        $add(null, null, (int) $assessmentId, $days);
                    }
                }
            }

            if ($item->assessment_id) {
                $add(null, null, (int) $item->assessment_id, $days);
            }
        }

        foreach ($grants as $grant) {
            $this->activateEnrollment(
                $student,
                $order,
                courseId: $grant['course'],
                curriculumId: $grant['curriculum'],
                assessmentId: $grant['assessment'],
                days: $grant['days'],
            );
        }
    }

    private function activateEnrollment(
        User $student,
        Order $order,
        ?int $courseId = null,
        ?int $curriculumId = null,
        ?int $assessmentId = null,
        ?int $days = null,
    ): void {
        $enrollment = Enrollment::query()
            ->where('user_id', $student->id)
            ->when(
                $courseId,
                fn ($query) => $query->where('course_id', $courseId),
                fn ($query) => $query->whereNull('course_id'),
            )
            ->when(
                $curriculumId,
                fn ($query) => $query->where('curriculum_id', $curriculumId),
                fn ($query) => $query->whereNull('curriculum_id'),
            )
            ->when(
                $assessmentId,
                fn ($query) => $query->where('assessment_id', $assessmentId),
                fn ($query) => $query->whereNull('assessment_id'),
            )
            ->first();

        $payload = [
            'order_id' => $order->id,
            'source' => 'purchase',
            'status' => EnrollmentStatus::Active->value,
            'started_at' => $enrollment?->started_at ?? now(),
            'expires_at' => $this->nextExpiry($enrollment, $days),
        ];

        if ($enrollment) {
            $enrollment->update($payload);

            return;
        }

        $student->enrollments()->create([
            'course_id' => $courseId,
            'curriculum_id' => $curriculumId,
            'assessment_id' => $assessmentId,
            ...$payload,
        ]);
    }

    /**
     * ต่ออายุจากวันหมดอายุเดิมถ้ายังใช้ได้อยู่ สิทธิ์ไม่จำกัดเวลาที่มีอยู่แล้วไม่ถูกลดลง
     */
    private function nextExpiry(?Enrollment $enrollment, ?int $days): ?CarbonInterface
    {
        $stillActive = $enrollment
            && $enrollment->status === EnrollmentStatus::Active->value
            && ($enrollment->expires_at === null || $enrollment->expires_at->isFuture());

        if ($days === null || ($stillActive && $enrollment->expires_at === null)) {
            return null;
        }

        $base = $stillActive ? $enrollment->expires_at : now();

        return $base->copy()->addDays($days);
    }
}
