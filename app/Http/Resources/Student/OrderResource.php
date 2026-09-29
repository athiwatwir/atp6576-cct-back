<?php

namespace App\Http\Resources\Student;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingStatus;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->latestPayment;
        $slip = $payment?->slips?->sortByDesc('id')->first();
        $method = PaymentMethod::tryFrom((string) ($payment?->payment_method ?? ''));

        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
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
                'paid_at' => $payment?->paid_at?->toIso8601String(),
                'slip_url' => MediaStorage::url($slip?->file_path),
            ],
            'items' => $this->items->map(fn ($item) => [
                'type' => $item->metadata['type'] ?? $this->itemType($item),
                'id' => $item->course_id ?? $item->curriculum_id ?? $item->product_id ?? $item->assessment_id,
                'name' => $item->item_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->values(),
            'created_at' => $this->created_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
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
