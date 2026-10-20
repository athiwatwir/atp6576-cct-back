@extends('layouts.app')

@section('content')
@php
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $rows = $fees->map(fn ($fee, $method) => [
        'method' => $method,
        'label_name' => \App\Enums\PaymentMethod::from($method)->label(),
        'is_enabled' => filter_var(old("fees.$method.is_enabled", $fee->is_enabled), FILTER_VALIDATE_BOOL),
        'percent' => (string) old("fees.$method.percent", $fee->percent ?? 0),
        'fixed_amount' => (string) old("fees.$method.fixed_amount", $fee->fixed_amount ?? 0),
        'min_amount' => (string) old("fees.$method.min_amount", $fee->min_amount),
        'max_amount' => (string) old("fees.$method.max_amount", $fee->max_amount),
        'label' => (string) old("fees.$method.label", $fee->label),
        'updated' => $fee->exists ? trim(($fee->updated_at?->format('d/m/Y H:i') ?? '').' '.($fee->updater?->name ? 'โดย '.$fee->updater->name : '')) : null,
    ])->values();
@endphp

<x-common.page-breadcrumb pageTitle="ค่าธรรมเนียมการชำระเงิน" />

@if (session('success'))
<div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
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

<form method="POST" action="{{ route('payment-fees.update') }}"
    x-data="{
        sample: 1000,
        rows: @js($rows),
        fee(row) {
            if (!row.is_enabled || this.sample <= 0) return 0;
            let amount = this.sample * (Number(row.percent || 0) / 100) + Number(row.fixed_amount || 0);
            if (row.min_amount !== '' && row.min_amount !== null) amount = Math.max(amount, Number(row.min_amount));
            if (row.max_amount !== '' && row.max_amount !== null) amount = Math.min(amount, Number(row.max_amount));
            return Math.max(0, Math.round(amount * 100) / 100);
        },
        money(value) {
            return '฿' + Number(value).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    }"
    class="space-y-6">
    @csrf
    @method('PUT')

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-end sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ค่าธรรมเนียมตามวิธีชำระ</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    เปิดเก็บแล้ว ระบบบวกค่าธรรมเนียมเข้ายอดที่ลูกค้าต้องจ่ายเมื่อเลือกวิธีนั้นในหน้าชำระเงินหรือส่งสลิป ยอดออเดอร์และส่วนลดไม่เปลี่ยน
                    คิดจาก ยอดออเดอร์ × เปอร์เซ็นต์ + ค่าคงที่ แล้วปรับให้อยู่ระหว่างขั้นต่ำกับสูงสุด
                </p>
            </div>
            <label class="block shrink-0">
                <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">ลองคำนวณจากยอด (บาท)</span>
                <input type="number" min="0" step="0.01" x-model.number="sample" class="{{ $inputClass }} sm:w-40" />
            </label>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <template x-for="row in rows" :key="row.method">
                <div class="p-5" :class="!row.is_enabled && 'bg-gray-50/60 dark:bg-white/[0.02]'">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="row.label_name"></h4>
                            <p class="mt-0.5 text-xs text-gray-400" x-show="row.updated" x-text="'แก้ไขล่าสุด ' + row.updated"></p>
                        </div>
                        <label class="inline-flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="hidden" :name="`fees[${row.method}][is_enabled]`" :value="row.is_enabled ? 1 : 0" />
                            <button type="button" @click="row.is_enabled = !row.is_enabled"
                                :class="row.is_enabled ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-700'"
                                class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition" role="switch" :aria-checked="row.is_enabled">
                                <span :class="row.is_enabled ? 'translate-x-5' : 'translate-x-0.5'" class="mt-0.5 inline-block h-5 w-5 rounded-full bg-white shadow transition"></span>
                            </button>
                            <span x-text="row.is_enabled ? 'เก็บจากลูกค้า' : 'ไม่เก็บ'"></span>
                        </label>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-12">
                        <label class="md:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">เปอร์เซ็นต์ (%)</span>
                            <input type="number" min="0" max="100" step="0.01" x-model="row.percent" :name="`fees[${row.method}][percent]`" class="{{ $inputClass }}" />
                        </label>
                        <label class="md:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">ค่าคงที่ (บาท)</span>
                            <input type="number" min="0" step="0.01" x-model="row.fixed_amount" :name="`fees[${row.method}][fixed_amount]`" class="{{ $inputClass }}" />
                        </label>
                        <label class="md:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">ขั้นต่ำ (บาท)</span>
                            <input type="number" min="0" step="0.01" x-model="row.min_amount" :name="`fees[${row.method}][min_amount]`" placeholder="-" class="{{ $inputClass }}" />
                        </label>
                        <label class="md:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">สูงสุด (บาท)</span>
                            <input type="number" min="0" step="0.01" x-model="row.max_amount" :name="`fees[${row.method}][max_amount]`" placeholder="-" class="{{ $inputClass }}" />
                        </label>
                        <label class="col-span-2 md:col-span-4">
                            <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">ข้อความที่ลูกค้าเห็น</span>
                            <input type="text" maxlength="100" x-model="row.label" :name="`fees[${row.method}][label]`" :placeholder="'ค่าธรรมเนียม' + (/^[A-Za-z]/.test(row.label_name) ? ' ' : '') + row.label_name" class="{{ $inputClass }}" />
                        </label>
                    </div>

                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        ยอด <span x-text="money(sample || 0)"></span>
                        → ค่าธรรมเนียม <span class="font-semibold" :class="fee(row) > 0 ? 'text-brand-600 dark:text-brand-400' : ''" x-text="money(fee(row))"></span>
                        → ลูกค้าจ่าย <span class="font-semibold text-gray-800 dark:text-white/90" x-text="money((Number(sample) || 0) + fee(row))"></span>
                    </p>
                </div>
            </template>
        </div>
    </div>

    <div class="rounded-xl border border-blue-light-200 bg-blue-light-50 px-4 py-3 text-sm text-blue-light-700 dark:border-blue-light-500/30 dark:bg-blue-light-500/10 dark:text-blue-light-400">
        <ul class="list-disc space-y-1 pl-4">
            <li>ค่าธรรมเนียมคิดตอนเริ่มชำระแต่ละครั้ง (สร้าง QR, ตัดบัตร, ส่งสลิป) QR ที่สร้างไปแล้วยังใช้ยอดเดิม</li>
            <li>ออเดอร์ที่ยอดเป็น 0 บาทไม่มีค่าธรรมเนียม</li>
            <li>แอดมินบันทึกการชำระให้นักเรียนเองในหน้านักเรียน ไม่คิดค่าธรรมเนียม</li>
        </ul>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-white">บันทึกค่าธรรมเนียม</button>
    </div>
</form>
@endsection
