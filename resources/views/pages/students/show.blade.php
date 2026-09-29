@extends('layouts.app')

@section('content')
@php
    $sourceLabel = fn (?string $source) => match ($source) {
        'purchase' => 'จากการสั่งซื้อ',
        'manual' => 'เพิ่มโดยผู้ดูแล',
        default => $source ?: '-',
    };
@endphp

<x-common.page-breadcrumb pageTitle="{{ $student->name }}" />

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

<div class="mb-6 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 dark:border-gray-800 dark:bg-white/[0.03] lg:flex-row lg:items-start lg:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $student->name }}</h2>
            <x-common.status-badge set="account" :status="$student->status" />
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $student->email }}</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $student->phone ?: 'ไม่มีเบอร์โทร' }}
            · เข้าสู่ระบบล่าสุด {{ $student->last_login_at?->format('d/m/Y H:i') ?? 'ยังไม่เคยเข้า' }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('students.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
            กลับ
        </a>
        <a href="{{ route('students.orders.create', $student) }}"
            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
            สั่งซื้อ
        </a>
        <a href="{{ route('students.edit', $student) }}"
            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
            แก้ไขข้อมูล
        </a>
        @unless ($student->canAccessBackend())
            <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('ต้องการลบนักเรียนนี้หรือไม่?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-error-300 px-4 py-2.5 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400">
                    ลบ
                </button>
            </form>
        @endunless
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-8">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">สิทธิ์ที่ได้รับหลังชำระเงิน</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เปิดเมื่อออเดอร์ชำระเงินสำเร็จ</p>
                </div>
                <a href="{{ route('students.orders.create', $student) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">สร้างออเดอร์</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">รายการ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ออเดอร์</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($student->enrollments as $enrollment)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-gray-800 dark:text-white/90">
                                        {{ $enrollment->course?->name ?? $enrollment->curriculum?->name ?? $enrollment->assessment?->title ?? 'ไม่พบรายการ' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $sourceLabel($enrollment->source) }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-common.status-badge :status="\App\Enums\EnrollmentStatus::tryFrom($enrollment->status) ?? $enrollment->status" />
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    @if ($enrollment->order)
                                        <a href="{{ route('orders.show', $enrollment->order) }}" class="text-brand-500 hover:text-brand-600">{{ $enrollment->order->order_no }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีสิทธิ์เรียน ต้องสร้างออเดอร์และชำระเงินก่อน</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-6 xl:col-span-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ออเดอร์ล่าสุด</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($student->orders as $order)
                    <div class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <span>
                            <a href="{{ route('orders.show', $order) }}" class="block text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">{{ $order->order_no }}</a>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at?->format('d/m/Y H:i') }}</span>
                        </span>
                        <span class="text-right">
                            <span class="block text-sm font-medium text-gray-800 dark:text-white/90">฿{{ number_format((float) $order->total_amount, 2) }}</span>
                            <x-common.status-badge :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status) ?? $order->payment_status" />
                            @if ($order->payment_status !== \App\Enums\PaymentStatus::Paid->value)
                                <a href="{{ route('students.orders.payment', [$student, $order]) }}" class="mt-1 block text-xs font-medium text-brand-500 hover:text-brand-600">ชำระเงิน</a>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีออเดอร์</p>
                @endforelse
            </div>
        </div>

        <x-common.activity-logs :entity="$student" :limit="8" title="ประวัตินักเรียน" />
    </div>
</div>
@endsection
