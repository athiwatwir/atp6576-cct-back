@extends('layouts.app')

@section('content')
@php
    $sourceLabel = fn (?string $source) => match ($source) {
        'purchase' => 'จากการสั่งซื้อ',
        'manual' => 'เพิ่มโดยผู้ดูแล',
        'coupon_gift' => 'ของแถมจากคูปอง',
        default => $source ?: '-',
    };
    $typeStyle = [
        'course' => ['label' => 'วิชา', 'class' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'],
        'curriculum' => ['label' => 'หลักสูตร', 'class' => 'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400'],
        'exam' => ['label' => 'ข้อสอบ', 'class' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'],
    ];
    $icons = [
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/>',
        'school' => '<path d="m22 10-10-5-10 5 10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    ];
    $icon = fn (string $name, string $class = 'h-4 w-4') => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$icons[$name].'</svg>';
    $address = collect([$student->address_line1, $student->subdistrict, $student->district, $student->province, $student->postal_code])->filter()->implode(' ');
    $details = [
        ['icon' => 'mail', 'label' => 'อีเมล', 'value' => $student->email],
        ['icon' => 'phone', 'label' => 'เบอร์โทร', 'value' => $student->phone],
        ['icon' => 'school', 'label' => 'โรงเรียน', 'value' => $student->school_name],
        ['icon' => 'pin', 'label' => 'จังหวัดที่เรียน', 'value' => $student->school_province],
        ['icon' => 'home', 'label' => 'ที่อยู่', 'value' => $address, 'wide' => true],
    ];
    $initial = mb_substr(trim((string) $student->name), 0, 1) ?: '?';
    $avatarUrl = $student->avatar ? \App\Support\MediaStorage::url($student->avatar, $student->updated_at?->getTimestamp()) : null;
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

<div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-4">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $student->name }}" class="h-16 w-16 shrink-0 rounded-full object-cover ring-4 ring-brand-50 dark:ring-brand-500/10" />
            @else
                <div class="bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-2xl font-semibold ring-4 ring-brand-50/60 dark:ring-brand-500/10">
                    {{ $initial }}
                </div>
            @endif
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $student->name }}</h2>
                    @if ($student->nickname)
                        <span class="text-sm text-gray-500 dark:text-gray-400">({{ $student->nickname }})</span>
                    @endif
                    <x-common.status-badge set="account" :status="$student->status" />
                </div>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1.5">{!! $icon('clock', 'h-3.5 w-3.5') !!} เข้าสู่ระบบล่าสุด {{ $student->last_login_at?->format('d/m/Y H:i') ?? 'ยังไม่เคยเข้า' }}</span>
                    <span class="inline-flex items-center gap-1.5">{!! $icon('calendar', 'h-3.5 w-3.5') !!} สมัครเมื่อ {{ $student->created_at?->format('d/m/Y') ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('students.index') }}"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                กลับ
            </a>
            <a href="{{ route('students.edit', $student) }}"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                แก้ไขข้อมูล
            </a>
            <a href="{{ route('students.orders.create', $student) }}"
                class="bg-brand-500 hover:bg-brand-600 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L22 7H6"/></svg>
                สั่งซื้อ
            </a>
            @unless ($student->canAccessBackend())
                <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('ต้องการลบนักเรียนนี้หรือไม่?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="ลบนักเรียน"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-error-300 px-3.5 py-2 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                        ลบ
                    </button>
                </form>
            @endunless
        </div>
    </div>

    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 border-t border-gray-100 bg-gray-50/60 px-5 py-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-800 dark:bg-white/[0.02]">
        @foreach ($details as $detail)
            <div class="flex items-start gap-3 {{ ($detail['wide'] ?? false) ? 'sm:col-span-2 lg:col-span-4' : '' }}">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-theme-xs dark:bg-white/5 dark:text-gray-400">
                    {!! $icon($detail['icon']) !!}
                </span>
                <div class="min-w-0">
                    <dt class="text-[11px] text-gray-400">{{ $detail['label'] }}</dt>
                    <dd class="text-sm break-words {{ filled($detail['value']) ? 'font-medium text-gray-800 dark:text-white/90' : 'text-gray-400' }}">{{ filled($detail['value']) ? $detail['value'] : 'ไม่ระบุ' }}</dd>
                </div>
            </div>
        @endforeach
    </dl>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    @foreach ([
        ['label' => 'สิทธิ์เรียนที่ใช้งานอยู่', 'value' => number_format($stats['active_enrollments']), 'tone' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400', 'path' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5Z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>'],
        ['label' => 'ออเดอร์ทั้งหมด', 'value' => number_format($stats['orders']), 'tone' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400', 'path' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>'],
        ['label' => 'ยอดชำระแล้ว', 'value' => '฿'.number_format($stats['paid_total'], 2), 'tone' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400', 'path' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>'],
    ] as $stat)
        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $stat['tone'] }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $stat['path'] !!}</svg>
            </span>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                <p class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stat['value'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <div class="space-y-6 xl:col-span-8">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <span class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400 flex h-9 w-9 items-center justify-center rounded-lg">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">สิทธิ์ที่ได้รับหลังชำระเงิน</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">เปิดเมื่อออเดอร์ชำระเงินสำเร็จ</p>
                    </div>
                </div>
                <a href="{{ route('students.orders.create', $student) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    สร้างออเดอร์
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">รายการ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ช่วงสิทธิ์</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ออเดอร์</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($student->enrollments as $enrollment)
                            @php
                                $type = $enrollment->course_id ? 'course' : ($enrollment->curriculum_id ? 'curriculum' : ($enrollment->assessment_id ? 'exam' : null));
                            @endphp
                            <tr>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-start gap-2">
                                        @if ($type)
                                            <span class="mt-0.5 inline-flex shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $typeStyle[$type]['class'] }}">{{ $typeStyle[$type]['label'] }}</span>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                {{ $enrollment->course?->name ?? $enrollment->curriculum?->name ?? $enrollment->assessment?->title ?? 'ไม่พบรายการ' }}
                                            </div>
                                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $sourceLabel($enrollment->source) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-xs whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    <div>เริ่ม {{ $enrollment->started_at?->format('d/m/Y') ?? '-' }}</div>
                                    <div class="mt-0.5 {{ $enrollment->expires_at?->isPast() ? 'text-error-500' : '' }}">
                                        {{ $enrollment->expires_at ? 'หมด '.$enrollment->expires_at->format('d/m/Y') : 'ไม่จำกัดเวลา' }}
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-common.status-badge :status="\App\Enums\EnrollmentStatus::tryFrom($enrollment->status) ?? $enrollment->status" />
                                </td>
                                <td class="px-5 py-3.5 text-sm">
                                    @if ($enrollment->order)
                                        <a href="{{ route('enrollments.show', $enrollment->order) }}" class="font-medium text-brand-500 hover:text-brand-600">{{ $enrollment->order->order_no }}</a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5Z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/></svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีสิทธิ์เรียน ต้องสร้างออเดอร์และชำระเงินก่อน</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-common.activity-logs :entity="$student" :limit="8" title="ประวัตินักเรียน" />
    </div>

    <div class="space-y-6 xl:col-span-4">
        <x-students.login-credentials :student="$student" class="bg-white dark:bg-white/[0.03]" />

        @foreach ([
            ['title' => 'สมัครเรียนล่าสุด', 'orders' => $enrollmentOrders, 'empty' => 'ยังไม่มีรายการสมัครเรียน', 'route' => 'enrollments.show', 'canPay' => true, 'path' => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>'],
            ['title' => 'ออเดอร์หนังสือล่าสุด', 'orders' => $bookOrders, 'empty' => 'ยังไม่มีออเดอร์หนังสือ', 'route' => 'orders.show', 'canPay' => false, 'path' => '<path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2Z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7Z"/>'],
        ] as $group)
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-2.5 border-b border-gray-100 px-5 py-3.5 dark:border-gray-800">
                    <span class="text-gray-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $group['path'] !!}</svg>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $group['title'] }}</h3>
                </div>
                <div class="divide-y divide-gray-100 px-5 dark:divide-gray-800">
                    @forelse ($group['orders'] as $order)
                        <div class="flex items-start justify-between gap-3 py-3">
                            <span class="min-w-0">
                                <a href="{{ route($group['route'], $order) }}" class="block text-sm font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">{{ $order->order_no }}</a>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at?->format('d/m/Y H:i') }}</span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block text-sm font-medium text-gray-800 dark:text-white/90">฿{{ number_format((float) $order->total_amount, 2) }}</span>
                                <x-common.status-badge :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status) ?? $order->payment_status" />
                                @if ($group['canPay'] && $order->payment_status !== \App\Enums\PaymentStatus::Paid->value)
                                    <a href="{{ route('students.orders.payment', [$student, $order]) }}" class="mt-1 block text-xs font-medium text-brand-500 hover:text-brand-600">ชำระเงิน</a>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ $group['empty'] }}</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
