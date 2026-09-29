<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentPurchase
{
    public function __construct(
        private readonly MailService $mailService,
        private readonly CouponService $coupons,
    ) {}

    /**
     * @return array{course: list<array{id:int,name:string,price:float}>, curriculum: list<array{id:int,name:string,price:float}>, exam: list<array{id:int,name:string,price:float}>}
     */
    public function catalog(): array
    {
        return [
            'course' => Course::query()
                ->where('status', 'published')
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'sale_price'])
                ->map(fn (Course $course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'price' => $course->effective_price,
                ])
                ->values()
                ->all(),
            'curriculum' => Curriculum::query()
                ->where('status', 'published')
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'sale_price'])
                ->map(fn (Curriculum $curriculum) => [
                    'id' => $curriculum->id,
                    'name' => $curriculum->name,
                    'price' => $curriculum->effective_price,
                ])
                ->values()
                ->all(),
            'exam' => Assessment::query()
                ->where('type', 'primary_exam')
                ->where('status', 'published')
                ->orderBy('title')
                ->get(['id', 'title', 'price'])
                ->map(fn (Assessment $exam) => [
                    'id' => $exam->id,
                    'name' => $exam->title,
                    'price' => $exam->effective_price,
                ])
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
     * @return array{first_purchase: bool, mailed: bool}
     */
    public function review(Payment $payment, PaymentStatus $status): void
    {
        DB::transaction(function () use ($payment, $status) {
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
            }
        });
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
                $this->mailService->sendCustomerPurchase(
                    to: $student,
                    order: $order->load('items'),
                    password: $firstPurchase ? $password : null,
                    queue: false,
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
     * @param  list<array{type:string,id:int|string}>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function resolveLines(array $items): Collection
    {
        $unique = collect($items)
            ->map(fn (array $item) => ['type' => $item['type'], 'id' => (int) $item['id']])
            ->unique(fn (array $item) => $item['type'].':'.$item['id'])
            ->values();

        $lines = $unique->map(function (array $item) {
            $model = match ($item['type']) {
                'course' => Course::query()->where('status', 'published')->find($item['id']),
                'curriculum' => Curriculum::query()->where('status', 'published')->find($item['id']),
                'exam' => Assessment::query()->where('type', 'primary_exam')->where('status', 'published')->find($item['id']),
                default => null,
            };

            if (! $model) {
                throw ValidationException::withMessages([
                    'items' => 'มีรายการที่ยังไม่เผยแพร่หรือไม่พบในระบบ',
                ]);
            }

            $name = $model instanceof Assessment ? $model->title : $model->name;
            $price = round((float) $model->effective_price, 2);

            return [
                'product_id' => null,
                'course_id' => $item['type'] === 'course' ? $model->id : null,
                'curriculum_id' => $item['type'] === 'curriculum' ? $model->id : null,
                'assessment_id' => $item['type'] === 'exam' ? $model->id : null,
                'item_name' => $name,
                'quantity' => 1,
                'unit_price' => $price,
                'total_price' => $price,
                'metadata' => ['type' => $item['type']],
            ];
        });

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'กรุณาเลือกอย่างน้อย 1 รายการ',
            ]);
        }

        return $lines;
    }

    private function grantAccess(User $student, Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->course_id) {
                $this->activateEnrollment($student, $order, courseId: $item->course_id);
            }

            if ($item->curriculum_id) {
                $this->activateEnrollment($student, $order, curriculumId: $item->curriculum_id);
                $this->grantCurriculumContents($student, $order, (int) $item->curriculum_id);
            }

            if ($item->assessment_id) {
                $this->activateEnrollment($student, $order, assessmentId: $item->assessment_id);
            }
        }
    }

    private function grantCurriculumContents(User $student, Order $order, int $curriculumId): void
    {
        $curriculum = Curriculum::query()->find($curriculumId);

        if (! $curriculum) {
            return;
        }

        foreach ($curriculum->courses()->pluck('courses.id') as $courseId) {
            $this->activateEnrollment($student, $order, courseId: (int) $courseId);
        }

        $examIds = $curriculum->assessments()
            ->whereIn('assessments.type', ['exam', 'primary_exam'])
            ->pluck('assessments.id');

        foreach ($examIds as $assessmentId) {
            $this->activateEnrollment($student, $order, assessmentId: (int) $assessmentId);
        }
    }

    private function activateEnrollment(
        User $student,
        Order $order,
        ?int $courseId = null,
        ?int $curriculumId = null,
        ?int $assessmentId = null,
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
}
