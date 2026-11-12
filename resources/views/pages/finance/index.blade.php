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
$typeStyles = [
'course' => ['active' => 'border-brand-500 bg-brand-500 text-white', 'icon' => '<path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5z"/><path d="m10 9 5 3-5 3z"/>'],
'curriculum' => ['active' => 'border-blue-light-500 bg-blue-light-500 text-white', 'icon' => '<path d="M5 7h14M5 12h14M5 17h9"/>'],
'exam' => ['active' => 'border-warning-500 bg-warning-500 text-white', 'icon' => '<path d="M9 5h6M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="m9 13 2 2 4-4"/>'],
'book' => ['active' => 'border-success-500 bg-success-500 text-white', 'icon' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16"/>'],
];
$typeOf = fn ($item) => match (true) {
    $item->product_id !== null => 'book',
    $item->course_id !== null => 'course',
    $item->curriculum_id !== null => 'curriculum',
    $item->assessment_id !== null => 'exam',
    default => null,
};
$filterQuery = fn (array $override = []) => array_filter(array_merge([
    'preset' => $preset ?: null,
    'from' => $from->toDateString(),
    'to' => $to->toDateString(),
    'type' => $type ?: null,
], $override), fn ($value) => filled($value));
@endphp

<x-common.page-breadcrumb pageTitle="การเงิน" />

@if (session('success'))
<div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
@endif

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
            <a href="{{ route('finance.index', $filterQuery(['preset' => $key, 'from' => $item['from'], 'to' => $item['to']])) }}" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-medium {{ $preset === $key || ($from->toDateString() === $item['from'] && $to->toDateString() === $item['to']) ? 'bg-brand-500 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5' }}">
                {{ $item['label'] }}
            </a>
            @endforeach
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-auto">
            <x-form.date-picker name="from" label="จากวันที่" :value="$from" />
            <x-form.date-picker name="to" label="ถึงวันที่" :value="$to" />
            <div class="flex items-end gap-2">
                @if ($type)
                <input type="hidden" name="type" value="{{ $type }}">
                @endif
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 flex-1 items-center justify-center rounded-lg px-4 text-sm font-medium text-white">ดูรายงาน</button>
            </div>
        </div>
    </form>

    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
        <span class="mr-1 text-xs font-medium text-gray-500 dark:text-gray-400">ประเภทรายการ:</span>
        <a href="{{ route('finance.index', $filterQuery(['type' => null])) }}"
            class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-medium {{ $type === '' ? 'border-gray-800 bg-gray-800 text-white dark:border-white/30 dark:bg-white/20' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5' }}">
            ทั้งหมด
        </a>
        @foreach ($itemTypes as $value => $label)
        <a href="{{ route('finance.index', $filterQuery(['type' => $type === $value ? null : $value])) }}"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium {{ $type === $value ? $typeStyles[$value]['active'] : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5' }}">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $typeStyles[$value]['icon'] !!}</svg>
            {{ $label }}
        </a>
        @endforeach
        @if ($type)
        <span class="text-xs text-gray-400">แสดงเฉพาะออเดอร์ที่มี{{ $itemTypes[$type] }} ยอดเป็นยอดทั้งออเดอร์</span>
        @endif
    </div>
</div>

{{-- Summary cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-success-200 bg-success-50 p-5 dark:border-success-500/30 dark:bg-success-500/10">
        <div class="text-sm text-success-700 dark:text-success-400">รายได้ที่ชำระแล้ว</div>
        <div class="mt-2 text-2xl font-semibold text-success-800 dark:text-success-300">{{ $money($summary['revenue']) }}</div>
        <div class="mt-1 text-xs text-success-600/80 dark:text-success-400/80">{{ number_format($summary['paid_orders']) }} ออเดอร์</div>
        @if ($summary['fees'] > 0)
        <div class="mt-1 text-xs text-success-600/80 dark:text-success-400/80">ค่าธรรมเนียมที่เก็บจากลูกค้า {{ $money($summary['fees']) }} (ไม่รวมในยอดนี้)</div>
        @endif
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

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายการชำระเงินทั้งหมด</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ทุกสถานะในช่วงวันที่ที่เลือก แยกตามที่มาเป็นออเดอร์หรือสมัครเรียน</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">วันที่</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ที่มา</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ลูกค้า</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">วิธีชำระ</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ยอด</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">สถานะ</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ตรวจสอบ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($payments as $payment)
                @php
                    $order = $payment->order;
                    $items = $order?->items->pluck('item_name')->filter()->join(', ');
                    $rowTypes = $order?->items->map($typeOf)->filter()->unique()->values() ?? collect();
                @endphp
                <tr>
                    <td class="px-5 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $payment->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $payment->sourceLabel() === 'สมัครเรียน' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' }}">{{ $payment->sourceLabel() }}</span>
                        @if ($order)
                        <div class="mt-1">
                            <a href="{{ $order->adminUrl() }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">{{ $order->order_no }}</a>
                        </div>
                        @endif
                        @if ($rowTypes->isNotEmpty())
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach ($rowTypes as $rowType)
                            <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-medium {{ $type === $rowType ? $typeStyles[$rowType]['active'] : 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' }}">{{ $itemTypes[$rowType] }}</span>
                            @endforeach
                        </div>
                        @endif
                        @if ($items)
                        <div class="mt-0.5 max-w-xs text-xs text-gray-500 dark:text-gray-400">{{ $items }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                        <div>{{ $order?->user?->name ?? '-' }}</div>
                        <div class="text-xs text-gray-400">{{ $order?->user?->email }}</div>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-500 whitespace-nowrap">{{ \App\Enums\PaymentMethod::tryFrom((string) $payment->payment_method)?->label() ?? $payment->payment_method }}</td>
                    <td class="px-5 py-3 text-right text-sm font-semibold text-gray-800 dark:text-white/90 whitespace-nowrap">
                        {{ $money($payment->amount) }}
                        @if ((float) $payment->fee_amount > 0)
                        <div class="text-[11px] font-normal text-gray-400">รวมค่าธรรมเนียม {{ $money($payment->fee_amount) }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <x-common.status-badge :status="\App\Enums\PaymentStatus::tryFrom((string) $payment->status) ?? $payment->status" />
                    </td>
                    <td class="px-5 py-3">
                        @if ($order && $payment->status === 'paid' && $order->payment_status === 'paid')
                        <div class="mb-2 flex justify-end gap-2">
                            <a href="{{ route('orders.receipt', [$order, 'inline' => 1]) }}" target="_blank" rel="noopener" data-no-loading
                                class="inline-flex items-center rounded-lg border border-brand-300 bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-400">
                                ใบเสร็จ{{ $order->receipt ? ' '.$order->receipt->receipt_no : '' }}
                            </a>
                            <a href="{{ route('orders.receipt', $order) }}" data-no-loading
                                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" title="ดาวน์โหลด PDF">PDF</a>
                        </div>
                        @endif
                        <form method="POST" action="{{ route('finance.payments.update', $payment) }}" class="flex items-center justify-end gap-2" onsubmit="return confirm('เปลี่ยนสถานะการชำระเงินนี้หรือไม่?')">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                            <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                            @if ($preset)
                            <input type="hidden" name="preset" value="{{ $preset }}">
                            @endif
                            @if ($type)
                            <input type="hidden" name="type" value="{{ $type }}">
                            @endif
                            <select name="status" class="dark:bg-dark-900 h-9 rounded-lg border border-gray-300 bg-transparent px-2 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                @foreach ($paymentStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($payment->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="inline-flex shrink-0 items-center rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">ตรวจสอบ</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500">ยังไม่มีรายการชำระเงินในช่วงนี้</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($payments->hasPages())
    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
