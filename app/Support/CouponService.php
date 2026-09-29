<?php

namespace App\Support;

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * @param  list<array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null,total_price?:float|int|string}>  $lines
     * @return array{coupon: Coupon, discount: float}
     */
    public function quote(User $user, string $code, array $lines): array
    {
        $coupon = $this->findCoupon($code);

        return [
            'coupon' => $coupon,
            'discount' => $this->calculate($user, $coupon, $lines),
        ];
    }

    public function commit(User $user, Order $order): void
    {
        if (! $order->coupon_id) {
            return;
        }

        DB::transaction(function () use ($user, $order) {
            $existing = CouponUsage::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return;
            }

            $coupon = Coupon::query()->whereKey($order->coupon_id)->lockForUpdate()->first();

            if (! $coupon) {
                $this->fail('ไม่พบรหัสคูปองนี้');
            }

            $order->loadMissing('items');
            $discount = $this->calculate($user, $coupon, $this->linesFromOrder($order));

            if (round((float) $order->discount_amount, 2) - $discount > 0.009) {
                $this->fail('ส่วนลดของคูปองเปลี่ยนไปแล้ว ให้สร้างออเดอร์ใหม่');
            }

            CouponUsage::query()->create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'discount_amount' => $order->discount_amount,
                'used_at' => now(),
            ]);

            $this->syncUsageCount($coupon);
        });
    }

    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $usage = CouponUsage::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $usage) {
                return;
            }

            $coupon = Coupon::query()->whereKey($usage->coupon_id)->lockForUpdate()->first();
            $usage->delete();

            if ($coupon) {
                $this->syncUsageCount($coupon);
            }
        });
    }

    /**
     * @param  list<array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null,total_price?:float|int|string}>  $lines
     */
    private function calculate(User $user, Coupon $coupon, array $lines): float
    {
        if ($coupon->status !== 'active') {
            $this->fail('คูปองนี้ปิดใช้งาน');
        }

        $now = now();

        if (($coupon->start_at && $now->lt($coupon->start_at)) || ($coupon->end_at && $now->gt($coupon->end_at))) {
            $this->fail('คูปองนี้ไม่อยู่ในช่วงเวลาที่ใช้ได้');
        }

        $used = CouponUsage::query()->where('coupon_id', $coupon->id)->count();

        if ($coupon->usage_limit !== null && $used >= $coupon->usage_limit) {
            $this->fail('คูปองนี้ถูกใช้ครบจำนวนแล้ว');
        }

        if ($coupon->per_user_limit !== null) {
            $userUsed = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($userUsed >= $coupon->per_user_limit) {
                $this->fail('นักเรียนคนนี้ใช้คูปองนี้ครบจำนวนแล้ว');
            }
        }

        $coupon->load(['courses', 'curriculums', 'assessments', 'products']);
        $eligible = $this->eligibleSubtotal($coupon, $lines);

        if ($eligible <= 0) {
            $this->fail('คูปองนี้ใช้กับรายการในออเดอร์ไม่ได้');
        }

        $minimum = $coupon->min_purchase_amount !== null ? (float) $coupon->min_purchase_amount : 0;

        if ($minimum > 0 && $eligible + 0.009 < $minimum) {
            $this->fail('ยอดสินค้าที่ใช้คูปองได้ต้องอย่างน้อย ฿'.number_format($minimum, 2));
        }

        $discount = $coupon->discount_type === DiscountType::Percentage->value
            ? round($eligible * ((float) $coupon->discount_value) / 100, 2)
            : round((float) $coupon->discount_value, 2);

        if ($coupon->discount_type === DiscountType::Percentage->value && $coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        $discount = round(min($discount, $eligible), 2);

        if ($discount <= 0) {
            $this->fail('คูปองนี้ไม่มีส่วนลดสำหรับออเดอร์นี้');
        }

        return $discount;
    }

    /**
     * @param  list<array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null,total_price?:float|int|string}>  $lines
     */
    private function eligibleSubtotal(Coupon $coupon, array $lines): float
    {
        $courseIds = $coupon->courses->pluck('id')->map(fn ($id) => (int) $id)->all();
        $curriculumIds = $coupon->curriculums->pluck('id')->map(fn ($id) => (int) $id)->all();
        $assessmentIds = $coupon->assessments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $productIds = $coupon->products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $restricted = $courseIds !== [] || $curriculumIds !== [] || $assessmentIds !== [] || $productIds !== [];
        $sum = 0.0;

        foreach ($lines as $line) {
            $total = round((float) ($line['total_price'] ?? 0), 2);

            if ($total <= 0) {
                continue;
            }

            if (! $restricted || $this->lineMatches($line, $courseIds, $curriculumIds, $assessmentIds, $productIds)) {
                $sum += $total;
            }
        }

        return round($sum, 2);
    }

    /**
     * @param  array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null}  $line
     * @param  list<int>  $courseIds
     * @param  list<int>  $curriculumIds
     * @param  list<int>  $assessmentIds
     * @param  list<int>  $productIds
     */
    private function lineMatches(array $line, array $courseIds, array $curriculumIds, array $assessmentIds, array $productIds): bool
    {
        $courseId = (int) ($line['course_id'] ?? 0);
        $curriculumId = (int) ($line['curriculum_id'] ?? 0);
        $assessmentId = (int) ($line['assessment_id'] ?? 0);
        $productId = (int) ($line['product_id'] ?? 0);

        return ($courseId > 0 && in_array($courseId, $courseIds, true))
            || ($curriculumId > 0 && in_array($curriculumId, $curriculumIds, true))
            || ($assessmentId > 0 && in_array($assessmentId, $assessmentIds, true))
            || ($productId > 0 && in_array($productId, $productIds, true));
    }

    /**
     * @return list<array{course_id:int|null,curriculum_id:int|null,assessment_id:int|null,product_id:int|null,total_price:float}>
     */
    private function linesFromOrder(Order $order): array
    {
        return $order->items->map(fn ($item) => [
            'course_id' => $item->course_id,
            'curriculum_id' => $item->curriculum_id,
            'assessment_id' => $item->assessment_id,
            'product_id' => $item->product_id,
            'total_price' => (float) $item->total_price,
        ])->all();
    }

    private function findCoupon(string $code): Coupon
    {
        $normalized = strtoupper(trim($code));
        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [$normalized])->first();

        if (! $coupon) {
            $this->fail('ไม่พบรหัสคูปองนี้');
        }

        return $coupon;
    }

    private function syncUsageCount(Coupon $coupon): void
    {
        $coupon->update([
            'usage_count' => CouponUsage::query()->where('coupon_id', $coupon->id)->count(),
        ]);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'coupon_code' => $message,
        ]);
    }
}
