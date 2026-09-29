@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="โปรโมชัน" />

@if (session('success'))
<div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
@endif

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการโปรโมชัน</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">กำหนดกลุ่มนักเรียน เงื่อนไขการซื้อ การดูวิดีโอ และของแถม</p>
        </div>
        <a href="{{ route('promotions.create') }}" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">เพิ่มโปรโมชัน</a>
    </div>

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <form method="GET" action="{{ route('promotions.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อโปรโมชัน" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <select name="status" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">ทุกสถานะ</option>
                @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-medium text-white">ค้นหา</button>
        </form>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($promotions as $promotion)
        @php
            $period = match (true) {
                $promotion->start_at && $promotion->end_at => $promotion->start_at->format('d/m/Y H:i').' – '.$promotion->end_at->format('d/m/Y H:i'),
                $promotion->start_at => 'เริ่ม '.$promotion->start_at->format('d/m/Y H:i'),
                $promotion->end_at => 'ถึง '.$promotion->end_at->format('d/m/Y H:i'),
                default => 'ไม่กำหนดช่วงเวลา',
            };
        @endphp
        <article class="px-5 py-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('promotions.show', $promotion) }}" class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">{{ $promotion->name }}</a>
                        <x-common.status-badge :status="$promotion->status" />
                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $promotion->studentGroupLabel() }}</span>
                    </div>
                    @if ($promotion->description)
                    <p class="mt-1 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $promotion->description }}</p>
                    @endif
                </div>
                <a href="{{ route('promotions.edit', $promotion) }}" class="inline-flex shrink-0 items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">แก้ไข</a>
            </div>
            <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="min-w-0">
                    <dt class="text-[11px] font-medium text-gray-400">ส่วนลด</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-800 dark:text-white/90">{{ $promotion->discountLabel() }} · ใช้ {{ $promotion->usage_count }}/{{ $promotion->usage_limit ?? 'ไม่จำกัด' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[11px] font-medium text-gray-400">ช่วงเวลา</dt>
                    <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ $period }}</dd>
                </div>
                <div class="min-w-0 sm:col-span-2">
                    <dt class="text-[11px] font-medium text-gray-400">ต้องเคยซื้อ</dt>
                    <dd class="mt-0.5 line-clamp-2 text-sm text-gray-700 dark:text-gray-300">{{ $promotion->requiredPurchaseLabel() }}</dd>
                </div>
                <div class="min-w-0 sm:col-span-2">
                    <dt class="text-[11px] font-medium text-gray-400">ของแถม</dt>
                    <dd class="mt-0.5 line-clamp-2 text-sm text-gray-700 dark:text-gray-300">{{ $promotion->giftLabel() }}</dd>
                </div>
                <div class="min-w-0 sm:col-span-2">
                    <dt class="text-[11px] font-medium text-gray-400">วิดีโอ</dt>
                    <dd class="mt-0.5 line-clamp-2 text-sm text-gray-700 dark:text-gray-300">{{ $promotion->requiredVideoLabel() }}</dd>
                </div>
            </dl>
        </article>
        @empty
        <p class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีโปรโมชัน</p>
        @endforelse
    </div>
    @if ($promotions->hasPages())
    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $promotions->links() }}</div>
    @endif
</div>
@endsection
