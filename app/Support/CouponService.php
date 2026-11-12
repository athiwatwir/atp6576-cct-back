<?php

namespace App\Support;

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * @param  list<array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null,total_price?:float|int|string}>  $lines
     * @return array{coupon: Coupon, discount: float, gifts: list<array{type: string, id: int, name: string}>}
     */
    public function quote(User $user, string $code, array $lines): array
    {
        $coupon = $this->findCoupon($code);

        return [
            'coupon' => $coupon,
            'discount' => $this->calculate($user, $coupon, $lines),
            'gifts' => $this->giftsFor($coupon, $lines),
        ];
    }

    /**
     * คูปองที่ใส่ให้อัตโนมัติเมื่อลูกค้าไม่ได้กรอกโค้ด เลือกใบที่ผ่านเงื่อนไขทั้งหมดและลดมากที่สุด (เท่ากันเลือกใบที่มีของแถมมากกว่า)
     * - ประเภท "ต่ออายุเท่านั้น" (`purchase_type = renewal`)
     * - โปรโมชันของแถม "เมื่อซื้อวิชา" (`coupon_gift_courses`) ใช้เมื่อออเดอร์มีวิชานั้น
     * ออเดอร์หนึ่งใช้ได้คูปองเดียว
     *
     * @param  list<array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null,total_price?:float|int|string}>  $lines
     * @return array{coupon: Coupon, discount: float, gifts: list<array{type: string, id: int, name: string}>}|null
     */
    public function bestAutomatic(User $user, array $lines): ?array
    {
        $best = null;

        Coupon::query()
            ->where('status', 'active')
            ->where(fn ($query) => $query->where('purchase_type', 'renewal')->orWhereHas('giftCourses'))
            ->orderBy('id')
            ->get()
            ->each(function (Coupon $coupon) use ($user, $lines, &$best) {
                try {
                    $discount = $this->calculate($user, $coupon, $lines);
                } catch (ValidationException) {
                    return;
                }

                $gifts = $this->giftsFor($coupon, $lines);
                $better = $best === null
                    || $discount > $best['discount']
                    || ($discount === $best['discount'] && count($gifts) > count($best['gifts']));

                if ($better) {
                    $best = ['coupon' => $coupon, 'discount' => $discount, 'gifts' => $gifts];
                }
            });

        return $best;
    }

    /**
     * ของแถมที่ออเดอร์นี้จะได้หลังชำระเงิน ถ้าคูปองกำหนดวิชาที่ต้องซื้อ ออเดอร์ต้องมีวิชานั้นอย่างน้อย 1 วิชา
     *
     * @param  iterable<array{course_id?:int|null}|object>  $lines
     * @return list<array{type: string, id: int, name: string}>
     */
    public function giftsFor(Coupon $coupon, iterable $lines): array
    {
        $coupon->loadMissing(['giftCourses:id', 'giftAssessments:id,title', 'giftProducts:id,name']);

        if (! $coupon->hasGifts()) {
            return [];
        }

        $triggers = $coupon->giftCourses->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($triggers !== []) {
            $courseIds = collect($lines)->map(fn ($line) => (int) (is_array($line) ? ($line['course_id'] ?? 0) : ($line->course_id ?? 0)))->all();

            if (array_intersect($triggers, $courseIds) === []) {
                return [];
            }
        }

        return [
            ...$coupon->giftAssessments->map(fn ($exam) => ['type' => 'exam', 'id' => (int) $exam->id, 'name' => (string) $exam->title])->all(),
            ...$coupon->giftProducts->map(fn ($book) => ['type' => 'book', 'id' => (int) $book->id, 'name' => (string) $book->name])->all(),
        ];
    }

    /**
     * บันทึกการใช้คูปองเมื่อออเดอร์ชำระแล้ว ส่วนลดถูกล็อกไว้ตอนสร้างออเดอร์ จึงไม่ตรวจเงื่อนไขซ้ำ
     * (คูปองถูกปิด หมดอายุ แก้ค่า หรือใช้ครบระหว่างรอชำระ ต้องไม่ทำให้ยืนยันการชำระไม่สำเร็จ)
     */
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

            $coupon = Coupon::withTrashed()->whereKey($order->coupon_id)->lockForUpdate()->first();

            if (! $coupon) {
                Log::warning('คูปองของออเดอร์ที่ชำระแล้วไม่มีในระบบ', ['order_id' => $order->id, 'coupon_id' => $order->coupon_id]);

                return;
            }

            $used = CouponUsage::query()->where('coupon_id', $coupon->id)->count();
            if ($coupon->usage_limit !== null && $used >= $coupon->usage_limit) {
                Log::warning('คูปองถูกใช้เกินจำนวนที่กำหนด (ออเดอร์สร้างก่อนครบ แล้วชำระทีหลัง)', [
                    'coupon' => $coupon->code,
                    'order_id' => $order->id,
                    'usage_limit' => $coupon->usage_limit,
                ]);
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

        $this->assertStudentConditions($user, $coupon);

        $coupon->load(['courses', 'curriculums', 'assessments', 'products']);
        $allLines = $lines;
        $beforeType = $this->eligibleSubtotal($coupon, $lines);
        $lines = $this->filterByPurchaseType($user, $coupon, $lines);
        $eligible = $this->eligibleSubtotal($coupon, $lines);

        if ($eligible <= 0) {
            $this->fail(match (true) {
                $beforeType <= 0 => 'คูปองนี้ใช้กับรายการในออเดอร์ไม่ได้',
                $coupon->purchaseType() === 'renewal' => 'คูปองนี้ใช้ได้เฉพาะการต่ออายุวิชา หลักสูตร หรือข้อสอบที่เคยมีสิทธิ์เรียนมาก่อน',
                default => 'คูปองนี้ใช้ได้เฉพาะการซื้อใหม่ ไม่ใช้กับการต่ออายุ',
            });
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

        $discount = round(min(max(0, $discount), $eligible), 2);

        // คูปองแถมอย่างเดียว (ส่วนลด 0) ใช้ได้เมื่อออเดอร์นี้ได้ของแถมจริง
        if ($discount <= 0 && $this->giftsFor($coupon, $allLines) === []) {
            $this->fail($coupon->hasGifts() ? 'ออเดอร์นี้ยังไม่ได้รับของแถมจากคูปองนี้' : 'คูปองนี้ไม่มีส่วนลดสำหรับออเดอร์นี้');
        }

        return $discount;
    }

    /**
     * เงื่อนไขของผู้ใช้: กลุ่มนักเรียน ต้องเคยซื้อ (อย่างน้อย 1 รายการ) และต้องดูวิดีโอ (ทุกคลิปที่เลือก)
     * ผู้ใช้ที่ยังไม่มีบัญชี (สั่งซื้อแบบ guest ด้วยอีเมลใหม่) ถือเป็นนักเรียนใหม่ที่ยังไม่เคยซื้อและยังไม่เคยดู
     */
    private function assertStudentConditions(User $user, Coupon $coupon): void
    {
        $coupon->loadMissing(['requiredCourses:id,name', 'requiredProducts:id,name', 'requiredVideos:id,title,duration_seconds']);
        $userId = $user->exists ? (int) $user->id : null;

        $group = $coupon->studentGroup();
        if ($group !== 'all') {
            $hasPaid = $userId !== null && Order::query()
                ->where('user_id', $userId)
                ->where('payment_status', PaymentStatus::Paid->value)
                ->exists();

            if ($group === 'new' && $hasPaid) {
                $this->fail('คูปองนี้ใช้ได้เฉพาะนักเรียนใหม่ที่ยังไม่เคยซื้อ');
            }

            if ($group === 'returning' && ! $hasPaid) {
                $this->fail('คูปองนี้ใช้ได้เฉพาะนักเรียนที่เคยซื้อแล้ว');
            }
        }

        $courseIds = $coupon->requiredCourses->pluck('id')->all();
        $productIds = $coupon->requiredProducts->pluck('id')->all();

        if ($courseIds !== [] || $productIds !== []) {
            $bought = $userId !== null && (
                OrderItem::query()
                    ->whereHas('order', fn ($order) => $order->where('user_id', $userId)->where('payment_status', PaymentStatus::Paid->value))
                    ->where(fn ($items) => $items->whereIn('course_id', $courseIds ?: [0])->orWhereIn('product_id', $productIds ?: [0]))
                    ->exists()
                || ($courseIds !== [] && Enrollment::query()->where('user_id', $userId)->whereIn('course_id', $courseIds)->exists())
            );

            if (! $bought) {
                $names = $coupon->requiredCourses->pluck('name')->merge($coupon->requiredProducts->pluck('name'));
                $this->fail('คูปองนี้ใช้ได้เมื่อเคยซื้อ '.$names->join(' หรือ ').' มาก่อน');
            }
        }

        if ($coupon->requiredVideos->isNotEmpty()) {
            $seconds = (int) $coupon->required_video_seconds;
            $progress = $userId === null ? collect() : VideoProgress::query()
                ->where('user_id', $userId)
                ->whereIn('video_id', $coupon->requiredVideos->pluck('id'))
                ->get()
                ->keyBy('video_id');

            $missing = $coupon->requiredVideos->reject(function ($video) use ($progress, $seconds) {
                $record = $progress->get($video->id);

                if (! $record) {
                    return false;
                }

                if ($record->completed_at !== null) {
                    return true;
                }

                $duration = (int) $video->duration_seconds;
                $needed = $seconds > 0 ? ($duration > 0 ? min($seconds, $duration) : $seconds) : null;

                return $needed !== null && (int) $record->position_seconds >= $needed;
            });

            if ($missing->isNotEmpty()) {
                $amount = $seconds > 0 ? 'อย่างน้อย '.Video::secondsLabel($seconds) : 'จนจบ';
                $this->fail('ต้องดูวิดีโอ '.$missing->pluck('title')->join(', ').' '.$amount.' ก่อนใช้คูปองนี้');
            }
        }
    }

    /**
     * เก็บเฉพาะรายการที่ตรงประเภทการซื้อของคูปอง ต่ออายุ = ผู้ใช้เคยมีสิทธิ์เรียน (enrollments ทุกสถานะ) ของรายการนั้น
     * หนังสือไม่มีการต่ออายุ จึงนับเป็นการซื้อใหม่เสมอ
     *
     * @param  list<array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null,total_price?:float|int|string}>  $lines
     * @return list<array{course_id?:int|null,curriculum_id?:int|null,assessment_id?:int|null,product_id?:int|null,total_price?:float|int|string}>
     */
    private function filterByPurchaseType(User $user, Coupon $coupon, array $lines): array
    {
        $type = $coupon->purchaseType();

        if ($type === 'any') {
            return $lines;
        }

        $owned = ['course_id' => [], 'curriculum_id' => [], 'assessment_id' => []];

        if ($user->exists) {
            Enrollment::query()
                ->where('user_id', $user->id)
                ->get(['course_id', 'curriculum_id', 'assessment_id'])
                ->each(function ($enrollment) use (&$owned) {
                    foreach (array_keys($owned) as $column) {
                        if ($enrollment->{$column}) {
                            $owned[$column][(int) $enrollment->{$column}] = true;
                        }
                    }
                });
        }

        $isRenewal = function (array $line) use ($owned): bool {
            foreach ($owned as $column => $ids) {
                $id = (int) ($line[$column] ?? 0);

                if ($id > 0 && isset($ids[$id])) {
                    return true;
                }
            }

            return false;
        };

        return array_values(array_filter($lines, fn (array $line) => $type === 'renewal' ? $isRenewal($line) : ! $isRenewal($line)));
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
