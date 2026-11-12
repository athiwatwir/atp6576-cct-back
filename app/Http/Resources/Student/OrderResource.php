<?php

namespace App\Http\Resources\Student;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Models\PricePlan;
use App\Support\CouponService;
use App\Support\MediaStorage;
use App\Support\Receipts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    /** ของที่ซื้อในแต่ละรายการ ใช้ดึงรูปปกและ slug */
    public const ITEM_SOURCES = ['course', 'curriculum', 'product', 'assessment'];

    /** คูปองของออเดอร์ และออเดอร์หนังสือแถมที่เกี่ยวข้อง */
    public const COUPON_RELATIONS = ['coupon', 'giftFor:id,order_no', 'giftOrders:id,order_no,gift_for_order_id'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->latestPayment;
        $slip = $payment?->slips?->sortByDesc('id')->first();
        $method = PaymentMethod::tryFrom((string) ($payment?->payment_method ?? ''));
        $this->items->loadMissing(self::ITEM_SOURCES);
        $this->resource->loadMissing(self::COUPON_RELATIONS);
        $isGift = $this->gift_for_order_id !== null;

        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'kind' => $this->kind,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'total' => (float) $this->total_amount,
            'status' => $this->status,
            'status_label' => OrderStatus::tryFrom((string) $this->status)?->label() ?? $this->status,
            'payment_status' => $this->payment_status,
            'payment_status_label' => PaymentStatus::tryFrom((string) $this->payment_status)?->label() ?? $this->payment_status,
            'notes' => $this->notes,
            'requires_shipping' => (bool) $this->requires_shipping,
            'shipping' => $this->requires_shipping ? [
                'name' => $this->shipping_name,
                'phone' => $this->shipping_phone,
                'address_line1' => $this->shipping_address_line1,
                'address_line2' => $this->shipping_address_line2,
                'subdistrict' => $this->shipping_subdistrict,
                'district' => $this->shipping_district,
                'province' => $this->shipping_province,
                'postal_code' => $this->shipping_postal_code,
                'status' => $this->shipping_status,
                'status_label' => ShippingStatus::tryFrom((string) $this->shipping_status)?->label() ?? $this->shipping_status,
                'tracking_number' => $this->tracking_number,
            ] : null,
            'payment' => [
                'method' => $payment?->payment_method ?? PaymentMethod::BankTransfer->value,
                'method_label' => $method?->label() ?? PaymentMethod::BankTransfer->label(),
                'status' => $payment?->status ?? $this->payment_status,
                'status_label' => PaymentStatus::tryFrom((string) ($payment?->status ?? $this->payment_status))?->label(),
                'amount' => (float) ($payment?->amount ?? $this->total_amount),
                'fee_amount' => (float) ($payment?->fee_amount ?? 0),
                'paid_at' => $payment?->paid_at?->toIso8601String(),
                'slip_url' => MediaStorage::url($slip?->file_path),
                'provider' => $payment?->provider,
                'reference' => $payment?->reference,
                'expires_at' => $payment?->expires_at?->toIso8601String(),
                'failure_message' => $payment?->status === PaymentStatus::Failed->value ? $payment?->failure_message : null,
            ],
            'items' => $this->items->map(fn ($item) => [
                'type' => $item->metadata['type'] ?? $this->itemType($item),
                'id' => $item->course_id ?? $item->curriculum_id ?? $item->product_id ?? $item->assessment_id,
                'name' => $item->item_name,
                'slug' => $this->source($item)?->slug,
                'thumbnail_url' => $this->source($item)?->thumbnail_url,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'plan' => $item->product_id === null ? [
                    'id' => $item->price_plan_id,
                    'name' => $item->metadata['plan_name'] ?? PricePlan::durationLabel($item->access_days),
                    'duration_days' => $item->access_days,
                ] : null,
            ])->values(),
            'coupon' => $this->couponPayload(),
            'is_gift' => $isGift,
            'gift_for_order' => $isGift ? [
                'id' => (int) $this->gift_for_order_id,
                'order_no' => $this->giftFor?->order_no,
            ] : null,
            'gift_orders' => $this->giftOrders
                ->map(fn ($gift) => ['id' => (int) $gift->id, 'order_no' => $gift->order_no])
                ->values(),
            'receipt' => $this->payment_status === PaymentStatus::Paid->value && ! $isGift
                ? app(Receipts::class)->payload($this->receipt ?? app(Receipts::class)->issue($this->resource))
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }

    /**
     * รหัสคูปองที่ใช้ และของแถมที่ออเดอร์นี้ได้ (หรือจะได้หลังชำระ) ตามเงื่อนไขปัจจุบันของคูปอง
     *
     * @return array{code: string, description: ?string, gifts: list<array{type: string, id: int, name: string}>}|null
     */
    private function couponPayload(): ?array
    {
        $coupon = $this->coupon;

        if (! $coupon) {
            return null;
        }

        return [
            'code' => (string) $coupon->code,
            'description' => $coupon->description,
            'gifts' => app(CouponService::class)->giftsFor($coupon, $this->items),
        ];
    }

    private function source(object $item): ?object
    {
        return $item->course ?? $item->curriculum ?? $item->product ?? $item->assessment;
    }

    private function itemType(object $item): ?string
    {
        return match (true) {
            $item->course_id !== null => 'course',
            $item->curriculum_id !== null => 'curriculum',
            $item->product_id !== null => 'book',
            $item->assessment_id !== null => 'exam',
            default => null,
        };
    }
}
