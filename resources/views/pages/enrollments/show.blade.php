@extends('layouts.app')

@php
    $latestPayment = $order->latestPayment ?? $order->payments->first();
    $student = $order->user;
    $typeBadge = [
        'course' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'curriculum' => 'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400',
        'exam' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
    ];
    $itemType = fn ($item) => match (true) {
        $item->course_id !== null => 'course',
        $item->curriculum_id !== null => 'curriculum',
        $item->assessment_id !== null => 'exam',
        default => null,
    };
    $itemUrl = fn ($item) => match (true) {
        $item->course !== null => route('courses.show', $item->course),
        $item->curriculum !== null => route('curriculums.show', $item->curriculum),
        $item->assessment !== null => route('exams.show', $item->assessment),
        default => null,
    };
    $isPaid = $order->payment_status === \App\Enums\PaymentStatus::Paid->value;
@endphp

@section('content')
<x-common.page-breadcrumb :pageTitle="$title" />

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

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                {{ $student?->name ?? '-' }}
                <span class="ml-1 font-mono text-base font-medium text-gray-500 dark:text-gray-400">{{ $order->order_no }}</span>
            </h2>
            <span class="bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">สมัครเรียน</span>
            <x-common.status-badge :status="\App\Enums\OrderStatus::tryFrom($order->status)" />
            <x-common.status-badge :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status)" />
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สร้างเมื่อ {{ $order->created_at?->format('d/m/Y H:i') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('enrollments.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            กลับ
        </a>
        @if ($student)
        <a href="{{ route('students.show', $student) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            ดูนักเรียน
        </a>
        @endif
        <a href="{{ route('enrollments.edit', $order) }}" class="inline-flex items-center justify-center rounded-lg border border-warning-300 bg-warning-50 px-4 py-2.5 text-sm font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
            แก้ไข
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-8">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการที่สมัคร</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">รายการ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">แพ็กเกจ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ช่วงสิทธิ์เรียน</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ราคา</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($order->items as $item)
                        @php
                            $type = $itemType($item);
                            $url = $itemUrl($item);
                            $access = \App\Support\AccessPeriod::summary($item->metadata['access'] ?? null, $item->access_days);
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-2">
                                    @if ($type)
                                        <span class="mt-0.5 inline-flex shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $typeBadge[$type] }}">{{ $itemTypes[$type] }}</span>
                                    @endif
                                    @if ($url)
                                        <a href="{{ $url }}" class="text-sm font-medium text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400">{{ $item->item_name }}</a>
                                    @else
                                        <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $item->item_name }}</span>
                                    @endif
                                </div>
                                @if ($item->course?->code)
                                    <div class="mt-1 font-mono text-[11px] text-gray-400">{{ $item->course->code }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $item->metadata['plan_name'] ?? \App\Models\PricePlan::durationLabel($item->access_days) }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                @if ($access)
                                    {{ $access }}
                                @else
                                    <span class="text-xs text-gray-400">{{ $isPaid ? '-' : 'เปิดสิทธิ์หลังชำระเงิน' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">฿{{ number_format((float) $item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="space-y-2 border-t border-gray-100 px-5 py-4 text-sm dark:border-gray-800">
                <div class="flex justify-between"><span class="text-gray-500">ยอดรายการ</span><span>฿{{ number_format((float) $order->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">ส่วนลด{{ $order->coupon ? ' ('.$order->coupon->code.')' : '' }}</span><span>-฿{{ number_format((float) $order->discount_amount, 2) }}</span></div>
                <div class="flex justify-between border-t border-gray-100 pt-2 font-semibold dark:border-gray-800">
                    <span>ยอดรวม</span>
                    <span class="text-brand-600 dark:text-brand-400">฿{{ number_format((float) $order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">สิทธิ์เรียนจากออเดอร์นี้</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สร้างอัตโนมัติเมื่อชำระเงินแล้ว</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">รายการ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">สถานะ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">เริ่ม</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">หมดอายุ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($order->enrollments as $enrollment)
                        @php
                            $remaining = $enrollment->expires_at && $enrollment->expires_at->isFuture()
                                ? (int) ceil(now()->diffInDays($enrollment->expires_at))
                                : null;
                        @endphp
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $enrollment->course?->name ?? $enrollment->curriculum?->name ?? $enrollment->assessment?->title ?? 'ไม่พบรายการ' }}
                            </td>
                            <td class="px-5 py-4">
                                <x-common.status-badge :status="\App\Enums\EnrollmentStatus::tryFrom($enrollment->status) ?? $enrollment->status" />
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $enrollment->started_at ? \App\Support\AccessPeriod::shortDate($enrollment->started_at) : '-' }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                @if ($enrollment->expires_at)
                                    {{ \App\Support\AccessPeriod::shortDate($enrollment->expires_at) }}
                                    @if ($remaining !== null)
                                        <div class="text-xs {{ $remaining <= 7 ? 'text-error-500' : 'text-gray-400' }}">เหลือ {{ number_format($remaining) }} วัน</div>
                                    @endif
                                @else
                                    ไม่จำกัดเวลา
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $isPaid ? 'ไม่พบสิทธิ์เรียนของออเดอร์นี้' : 'ยังไม่มีสิทธิ์เรียน จะเปิดให้เมื่อชำระเงินแล้ว' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ข้อมูลนักเรียน</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <div class="space-y-2 text-gray-600 dark:text-gray-400">
                    <div class="font-medium text-gray-800 dark:text-white/90">{{ $student?->name ?? '-' }}</div>
                    <div>{{ $student?->email ?: '-' }}</div>
                    <div>{{ $student?->phone ?: '-' }}</div>
                </div>
                <div class="space-y-2 text-gray-600 dark:text-gray-400">
                    <div><span class="text-gray-500">โรงเรียน:</span> {{ $student?->school_name ?: '-' }}</div>
                    <div><span class="text-gray-500">จังหวัด:</span> {{ $student?->province ?: '-' }}</div>
                </div>
            </div>
            @if ($student)
            <x-students.login-credentials :student="$student" class="mt-5" />
            @endif

            @if ($order->notes)
            <div class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-white/[0.03]">
                <span class="text-gray-500">หมายเหตุ:</span> {{ $order->notes }}
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6 xl:col-span-4">
        @include('pages.orders.partials.payment-card')

        <x-common.activity-logs :entity="$order" :limit="8" title="ประวัติออเดอร์" />

        @if (! $isPaid)
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
@endsection
