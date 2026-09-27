@extends('layouts.app')

@section('content')
@php
$money = fn ($n) => '฿'.number_format((float) $n, 2);
$presets = [
    'today' => ['label' => 'วันนี้', 'from' => now()->toDateString(), 'to' => now()->toDateString()],
    'week' => ['label' => '7 วัน', 'from' => now()->subDays(6)->toDateString(), 'to' => now()->toDateString()],
    'month' => ['label' => 'เดือนนี้', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()],
    'year' => ['label' => 'ปีนี้', 'from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()],
];
@endphp

<x-common.page-breadcrumb pageTitle="การเงิน" />

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">สรุปการเงินทั้งระบบ</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            ช่วง {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}
        </p>
    </div>
</div>

<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
    <form method="GET" action="{{ route('finance.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
        <div class="flex flex-wrap gap-2 lg:mr-auto">
            @foreach ($presets as $key => $item)
                <a href="{{ route('finance.index', ['preset' => $key, 'from' => $item['from'], 'to' => $item['to']]) }}"
                    class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-medium {{ $preset === $key || ($from->toDateString() === $item['from'] && $to->toDateString() === $item['to']) ? 'bg-brand-500 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-auto">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">จากวันที่</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">ถึงวันที่</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 flex-1 items-center justify-center rounded-lg px-4 text-sm font-medium text-white">ดูรายงาน</button>
            </div>
        </div>
    </form>
</div>

{{-- Summary cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-success-200 bg-success-50 p-5 dark:border-success-500/30 dark:bg-success-500/10">
        <div class="text-sm text-success-700 dark:text-success-400">รายได้ที่ชำระแล้ว</div>
        <div class="mt-2 text-2xl font-semibold text-success-800 dark:text-success-300">{{ $money($summary['revenue']) }}</div>
        <div class="mt-1 text-xs text-success-600/80 dark:text-success-400/80">{{ number_format($summary['paid_orders']) }} ออเดอร์</div>
    </div>
    <div class="rounded-2xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10">
        <div class="text-sm text-warning-700 dark:text-warning-400">รอตรวจสอบการชำระ</div>
        <div class="mt-2 text-2xl font-semibold text-warning-800 dark:text-warning-300">{{ $money($summary['awaiting']) }}</div>
        <div class="mt-1 text-xs text-warning-600/80">ต้องยืนยันการโอน</div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="text-sm text-gray-500 dark:text-gray-400">รอชำระเงิน</div>
        <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $money($summary['pending']) }}</div>
        <div class="mt-1 text-xs text-gray-400">รวม COD / ยังไม่จ่าย</div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="text-sm text-gray-500 dark:text-gray-400">ออเดอร์ทั้งหมดในช่วง</div>
        <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($summary['order_count']) }}</div>
        <div class="mt-1 text-xs text-gray-400">ยกเลิก {{ number_format($summary['cancelled_orders']) }} รายการ</div>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="text-sm text-gray-500">ยอดสินค้า (ชำระแล้ว)</div>
        <div class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $money($summary['subtotal']) }}</div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="text-sm text-gray-500">ค่าจัดส่งที่เก็บได้</div>
        <div class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $money($summary['shipping']) }}</div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="text-sm text-gray-500">ส่วนลดที่ให้ไป</div>
        <div class="mt-2 text-lg font-semibold text-error-600 dark:text-error-400">-{{ $money($summary['discount']) }}</div>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-12">
    {{-- By payment method --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-4">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายได้ตามวิธีชำระ</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($byPaymentMethod as $row)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $row['label'] }}</div>
                        <div class="text-xs text-gray-400">{{ number_format($row['payments']) }} รายการ</div>
                    </div>
                    <div class="font-semibold text-gray-800 dark:text-white/90">{{ $money($row['amount']) }}</div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-gray-500">ยังไม่มีข้อมูล</div>
            @endforelse
        </div>
    </div>

    {{-- By item type --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-4">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายได้ตามประเภทสินค้า</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($byItemType as $row)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $row['label'] }}</div>
                        <div class="text-xs text-gray-400">{{ number_format($row['quantity']) }} ชิ้น · {{ number_format($row['lines']) }} รายการ</div>
                    </div>
                    <div class="font-semibold text-gray-800 dark:text-white/90">{{ $money($row['amount']) }}</div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-gray-500">ยังไม่มีข้อมูล</div>
            @endforelse
        </div>
    </div>

    {{-- By payment status --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-4">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ออเดอร์ตามสถานะชำระ</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($byPaymentStatus as $row)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <div class="flex items-center gap-2">
                        <x-common.status-badge class="px-2 py-0.5 text-[11px]" :status="\App\Enums\PaymentStatus::tryFrom($row['status']) ?? $row['status']" />
                        <span class="text-xs text-gray-400">{{ number_format($row['orders']) }}</span>
                    </div>
                    <div class="font-semibold text-gray-800 dark:text-white/90">{{ $money($row['amount']) }}</div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-gray-500">ยังไม่มีข้อมูล</div>
            @endforelse
        </div>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-12">
    {{-- Daily revenue --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-5">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายได้รายวัน (ชำระแล้ว)</h3>
        </div>
        <div class="max-h-80 overflow-y-auto">
            <table class="min-w-full">
                <thead class="sticky top-0 bg-white dark:bg-gray-900">
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">วันที่</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ออเดอร์</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ยอด</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($dailyRevenue as $row)
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $row['day'] }}</td>
                            <td class="px-5 py-3 text-sm text-gray-500">{{ number_format($row['orders']) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">{{ $money($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-500">ยังไม่มีรายได้ในช่วงนี้</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Awaiting verification --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-7">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รอตรวจสอบการชำระ</h3>
            <a href="{{ route('orders.index', ['payment_status' => 'awaiting_verification']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">ดูทั้งหมด</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ออเดอร์</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ลูกค้า</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">วิธีชำระ</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ยอด</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($awaitingOrders as $order)
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('orders.show', $order) }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400">{{ $order->order_no }}</a>
                                <div class="text-[11px] text-gray-400">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $order->user?->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-gray-500">
                                {{ \App\Enums\PaymentMethod::tryFrom($order->latestPayment?->payment_method ?? '')?->label() ?? '-' }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800 dark:text-white/90">{{ $money($order->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">ไม่มีรายการรอตรวจสอบ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Recent paid --}}
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ออเดอร์ที่ชำระแล้วล่าสุด</h3>
        <a href="{{ route('orders.index', ['payment_status' => 'paid']) }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">ดูออเดอร์ทั้งหมด</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ออเดอร์</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ลูกค้า</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">วิธีชำระ</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ชำระเมื่อ</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ยอดรวม</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($recentPaid as $order)
                    <tr>
                        <td class="px-5 py-3">
                            <a href="{{ route('orders.show', $order) }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400">{{ $order->order_no }}</a>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                            <div>{{ $order->user?->name ?? '-' }}</div>
                            <div class="text-xs text-gray-400">{{ $order->user?->email }}</div>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-500">
                            {{ \App\Enums\PaymentMethod::tryFrom($order->latestPayment?->payment_method ?? '')?->label() ?? '-' }}
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-500">{{ $order->paid_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-5 py-3 text-right text-sm font-semibold text-gray-800 dark:text-white/90">{{ $money($order->total_amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">ยังไม่มีออเดอร์ที่ชำระแล้วในช่วงนี้</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
