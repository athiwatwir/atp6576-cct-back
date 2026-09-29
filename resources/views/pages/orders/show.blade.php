@extends('layouts.app')

@section('content')
@php
$latestPayment = $order->latestPayment ?? $order->payments->first();

$carrierValues = array_keys($shippingCarriers);
$currentCarrier = old('shipping_carrier', $order->shipping_carrier);
$carrierInList = in_array($currentCarrier, $carrierValues, true);
$selectedCarrier = old(
    'shipping_carrier',
    $carrierInList ? $currentCarrier : ($currentCarrier ? \App\Enums\ShippingCarrier::Other->value : '')
);
$customCarrier = old(
    'shipping_carrier_custom',
    (! $carrierInList && $currentCarrier) ? $currentCarrier : ''
);
$otherCarrierValue = \App\Enums\ShippingCarrier::Other->value;
@endphp

<x-common.page-breadcrumb pageTitle="{{ $order->order_no }}" />

@if (session('success'))
<div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

@if (session('error'))
<div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
    {{ session('error') }}
</div>
@endif

@if ($errors->any())
<div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
    <ul class="list-disc space-y-1 pl-4">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $order->order_no }}</h2>
            <x-common.status-badge :status="\App\Enums\OrderStatus::tryFrom($order->status)" />
            <x-common.status-badge :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status)" />
            <x-common.status-badge :status="\App\Enums\ShippingStatus::tryFrom($order->shipping_status)" />
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สร้างเมื่อ {{ $order->created_at?->format('d/m/Y H:i') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            กลับ
        </a>
        <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            แก้ไข
        </a>
        <button type="button" data-no-loading @click="$dispatch('open-shipping-status-modal')"
            class="inline-flex items-center justify-center rounded-lg border border-warning-300 bg-warning-50 px-4 py-2.5 text-sm font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
            อัปเดตสถานะจัดส่ง
        </button>
        <button type="button" data-no-loading @click="$dispatch('open-tracking-modal')"
            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs">
            ติดตามพัสดุ
        </button>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-8">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการสินค้า</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">สินค้า</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ราคา/หน่วย</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">จำนวน</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">รวม</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($order->items as $item)
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ $item->item_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">฿{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item->quantity }}</td>
                            <td class="px-5 py-4 text-right text-sm font-medium text-gray-800 dark:text-white/90">฿{{ number_format((float) $item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="space-y-2 border-t border-gray-100 px-5 py-4 text-sm dark:border-gray-800">
                <div class="flex justify-between"><span class="text-gray-500">ยอดสินค้า</span><span>฿{{ number_format((float) $order->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">ค่าจัดส่ง</span><span>฿{{ number_format((float) $order->shipping_amount, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">ส่วนลด{{ $order->coupon ? ' ('.$order->coupon->code.')' : '' }}</span><span>-฿{{ number_format((float) $order->discount_amount, 2) }}</span></div>
                <div class="flex justify-between border-t border-gray-100 pt-2 font-semibold dark:border-gray-800">
                    <span>ยอดรวม</span>
                    <span class="text-brand-600 dark:text-brand-400">฿{{ number_format((float) $order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ข้อมูลลูกค้าและที่อยู่จัดส่ง</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <div class="space-y-2 text-gray-600 dark:text-gray-400">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400">บัญชีลูกค้า</div>
                    <div class="font-medium text-gray-800 dark:text-white/90">{{ $order->user?->name ?? '-' }}</div>
                    <div>{{ $order->user?->email ?: '-' }}</div>
                    <div>{{ $order->user?->phone ?: '-' }}</div>
                </div>
                <div class="space-y-2 text-gray-600 dark:text-gray-400">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400">จัดส่งถึง</div>
                    <div><span class="text-gray-500">ผู้รับ:</span> <span class="font-medium text-gray-800 dark:text-white/90">{{ $order->shipping_name }}</span></div>
                    <div><span class="text-gray-500">โทร:</span> {{ $order->shipping_phone }}</div>
                    <div><span class="text-gray-500">ที่อยู่:</span> {{ $order->shipping_full_address }}</div>
                </div>
            </div>
            @if ($order->notes)
            <div class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-white/[0.03]">
                <span class="text-gray-500">หมายเหตุ:</span> {{ $order->notes }}
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6 xl:col-span-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">การชำระเงิน</h3>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">วิธีชำระ</span>
                    <span class="font-medium text-gray-800 dark:text-white/90">
                        {{ \App\Enums\PaymentMethod::tryFrom($latestPayment?->payment_method ?? '')?->label() ?? '-' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">สถานะ</span>
                    <x-common.status-badge :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status)" />
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ชำระเมื่อ</span>
                    <span>{{ $order->paid_at?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-start justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ติดตามพัสดุ</h3>
                <button type="button" data-no-loading @click="$dispatch('open-tracking-modal')"
                    class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                    {{ $order->tracking_number ? 'แก้ไข' : 'ระบุพัสดุ' }}
                </button>
            </div>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500">สถานะจัดส่ง</span>
                    <div class="flex items-center gap-2">
                        <x-common.status-badge :status="\App\Enums\ShippingStatus::tryFrom($order->shipping_status)" />
                        <button type="button" data-no-loading @click="$dispatch('open-shipping-status-modal')"
                            class="text-xs font-medium text-warning-600 hover:underline dark:text-warning-400">
                            เปลี่ยน
                        </button>
                    </div>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="shrink-0 text-gray-500">บริษัทขนส่ง</span>
                    <span class="text-right font-medium text-gray-800 dark:text-white/90">{{ $order->shipping_carrier ?: '-' }}</span>
                </div>
                <div class="rounded-lg bg-brand-50 px-3 py-2.5 dark:bg-brand-500/10">
                    <div class="text-xs text-brand-600 dark:text-brand-400">หมายเลขพัสดุ</div>
                    <div class="mt-1 font-mono text-sm font-semibold text-brand-700 dark:text-brand-300">
                        {{ $order->tracking_number ?: 'ยังไม่ได้ระบุ' }}
                    </div>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ส่งเมื่อ</span>
                    <span>{{ $order->shipped_at?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ถึงเมื่อ</span>
                    <span>{{ $order->delivered_at?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </div>

        <x-common.activity-logs :entity="$order" :limit="8" title="ประวัติออเดอร์" />

        @if ($order->payment_status !== 'paid')
        <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('ต้องการลบออเดอร์นี้หรือไม่?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-error-300 bg-error-50 px-4 py-2.5 text-sm font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">
                ลบออเดอร์
            </button>
        </form>
        @endif
    </div>
</div>

{{-- Modal: อัปเดตสถานะจัดส่ง --}}
<div
    x-data="{ open: @js($errors->has('shipping_status')) }"
    x-show="open"
    x-cloak
    @open-shipping-status-modal.window="open = true"
    @keydown.escape.window="open = false"
    class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5"
    data-modal
    style="display: none;"
>
    <div class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
    <div @click.stop class="relative w-full max-w-md rounded-3xl bg-white p-6 dark:bg-gray-900 sm:p-8">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">อัปเดตสถานะจัดส่ง</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ออเดอร์ {{ $order->order_no }}</p>

        <form method="POST" action="{{ route('orders.shipping-status', $order) }}" class="mt-5 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="modal_shipping_status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะจัดส่ง</label>
                <select id="modal_shipping_status" name="shipping_status" required
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @foreach ($shippingStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('shipping_status', $order->shipping_status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('shipping_status')
                    <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" data-no-loading @click="open = false"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    ยกเลิก
                </button>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-warning-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-warning-600">
                    บันทึกสถานะ
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: ติดตามพัสดุ --}}
<div
    x-data="{
        open: @js($errors->hasAny(['shipping_carrier', 'shipping_carrier_custom', 'tracking_number'])),
        carrier: @js($selectedCarrier),
        otherValue: @js($otherCarrierValue),
    }"
    x-show="open"
    x-cloak
    @open-tracking-modal.window="open = true"
    @keydown.escape.window="open = false"
    class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5"
    data-modal
    style="display: none;"
>
    <div class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
    <div @click.stop class="relative w-full max-w-md rounded-3xl bg-white p-6 dark:bg-gray-900 sm:p-8">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ติดตามพัสดุ</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ระบุบริษัทขนส่งและหมายเลขพัสดุของออเดอร์ {{ $order->order_no }}</p>

        <form method="POST" action="{{ route('orders.tracking', $order) }}" class="mt-5 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="modal_shipping_carrier" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">บริษัทขนส่ง</label>
                <select id="modal_shipping_carrier" name="shipping_carrier" x-model="carrier" required
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">เลือกบริษัทขนส่ง</option>
                    @foreach ($shippingCarriers as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('shipping_carrier')
                    <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="carrier === otherValue" x-cloak>
                <label for="modal_shipping_carrier_custom" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ชื่อบริษัทขนส่ง</label>
                <input type="text" id="modal_shipping_carrier_custom" name="shipping_carrier_custom"
                    value="{{ $customCarrier }}"
                    placeholder="ระบุชื่อบริษัทขนส่ง"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                @error('shipping_carrier_custom')
                    <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="modal_tracking_number" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หมายเลขพัสดุ</label>
                <input type="text" id="modal_tracking_number" name="tracking_number" required
                    value="{{ old('tracking_number', $order->tracking_number) }}"
                    placeholder="เช่น TH1234567890"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 font-mono focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                @error('tracking_number')
                    <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                หากสถานะยังเป็นรอจัดส่ง/พร้อมส่ง ระบบจะเปลี่ยนเป็นกำลังจัดส่งให้อัตโนมัติ
            </p>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" data-no-loading @click="open = false"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    ยกเลิก
                </button>
                <button type="submit"
                    class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs">
                    บันทึกพัสดุ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
