@extends('layouts.app')

@section('content')
@php
    $latestPayment = $order->latestPayment ?? $order->payments->first();
@endphp

    <x-common.page-breadcrumb pageTitle="แก้ไขออเดอร์" />

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">แก้ไขออเดอร์ {{ $order->order_no }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">อัปเดตสถานะชำระเงิน ที่อยู่จัดส่ง และหมายเลขพัสดุ</p>
        </div>

        <form method="POST" action="{{ route('orders.update', $order) }}" class="space-y-8 p-5 sm:p-6">
            @csrf
            @method('PUT')

            <section>
                <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">สถานะ</h4>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะออเดอร์</label>
                        <select id="status" name="status" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach ($orderStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $order->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="payment_status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะชำระเงิน</label>
                        <select id="payment_status" name="payment_status" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach ($paymentStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_status', $order->payment_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_status')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="payment_method" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">วิธีชำระเงิน</label>
                        <select id="payment_method" name="payment_method" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method', $latestPayment?->payment_method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="shipping_status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะจัดส่ง</label>
                        <select id="shipping_status" name="shipping_status" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach ($shippingStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('shipping_status', $order->shipping_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('shipping_status')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section>
                <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">ติดตามพัสดุ</h4>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="shipping_carrier" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">บริษัทขนส่ง</label>
                        <input type="text" id="shipping_carrier" name="shipping_carrier"
                            value="{{ old('shipping_carrier', $order->shipping_carrier) }}"
                            placeholder="เช่น Kerry, Flash, Thailand Post"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('shipping_carrier')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="tracking_number" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หมายเลขพัสดุ</label>
                        <input type="text" id="tracking_number" name="tracking_number"
                            value="{{ old('tracking_number', $order->tracking_number) }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('tracking_number')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="shipping_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ค่าจัดส่ง (บาท)</label>
                        <input type="number" id="shipping_amount" name="shipping_amount" min="0" step="0.01"
                            value="{{ old('shipping_amount', $order->shipping_amount) }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                    <div>
                        <label for="discount_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ส่วนลด (บาท)</label>
                        <input type="number" id="discount_amount" name="discount_amount" min="0" step="0.01"
                            value="{{ old('discount_amount', $order->discount_amount) }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                </div>
            </section>

            <section>
                <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลลูกค้าและที่อยู่จัดส่ง</h4>
                <div class="mb-4 rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-white/[0.03]">
                    <div class="font-medium text-gray-800 dark:text-white/90">{{ $order->user?->name ?? '-' }}</div>
                    <div class="mt-1 text-gray-500">{{ $order->user?->email }}{{ $order->user?->phone ? ' · '.$order->user->phone : '' }}</div>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="shipping_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ชื่อผู้รับ<span class="text-error-500">*</span></label>
                            <input type="text" id="shipping_name" name="shipping_name" value="{{ old('shipping_name', $order->shipping_name) }}" required
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            @error('shipping_name')
                                <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="shipping_phone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">เบอร์โทร<span class="text-error-500">*</span></label>
                            <input type="text" id="shipping_phone" name="shipping_phone" value="{{ old('shipping_phone', $order->shipping_phone) }}" required
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            @error('shipping_phone')
                                <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <x-common.thai-address
                        :address-line1="old('shipping_address_line1', $order->shipping_address_line1)"
                        :subdistrict="old('shipping_subdistrict', $order->shipping_subdistrict)"
                        :district="old('shipping_district', $order->shipping_district)"
                        :province="old('shipping_province', $order->shipping_province)"
                        :postal-code="old('shipping_postal_code', $order->shipping_postal_code)"
                    />

                    <div>
                        <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หมายเหตุ</label>
                        <textarea id="notes" name="notes" rows="2"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('notes', $order->notes) }}</textarea>
                    </div>
                </div>
            </section>

            <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-white/[0.03]">
                <div class="flex justify-between"><span class="text-gray-500">ยอดสินค้า</span><span>฿{{ number_format((float) $order->subtotal, 2) }}</span></div>
                <p class="mt-2 text-xs text-gray-500">ยอดรวมจะคำนวณใหม่จากยอดสินค้า + ค่าจัดส่ง − ส่วนลด เมื่อบันทึก</p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800">
                <a href="{{ route('orders.show', $order) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    ยกเลิก
                </a>
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @include('partials.thai-address-scripts')
@endpush
