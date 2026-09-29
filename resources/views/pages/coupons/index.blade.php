@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="คูปอง" />

@if (session('success'))
<div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
@endif

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการคูปอง</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">รหัสส่วนลดที่ใช้ตอนสั่งซื้อ</p>
        </div>
        <a href="{{ route('coupons.create') }}" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">เพิ่มคูปอง</a>
    </div>

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหารหัส" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
            <div class="flex gap-2 md:col-span-2">
                <select name="status" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">ทุกสถานะ</option>
                    @foreach (\App\Enums\ContentStatus::options(['active', 'inactive']) as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 text-sm font-medium text-white">ค้นหา</button>
            </div>
        </form>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($coupons as $coupon)
        @php
            $targets = collect()
                ->merge($coupon->courses->map(fn ($item) => 'คอร์ส '.$item->name))
                ->merge($coupon->curriculums->map(fn ($item) => 'หลักสูตร '.$item->name))
                ->merge($coupon->assessments->map(fn ($item) => 'ข้อสอบ '.$item->title))
                ->merge($coupon->products->map(fn ($item) => 'หนังสือ '.$item->name));
            $shownTargets = $targets->take(6);
            $hiddenTargetCount = $targets->count() - $shownTargets->count();
            $period = match (true) {
                $coupon->start_at && $coupon->end_at => $coupon->start_at->format('d/m/Y H:i').' – '.$coupon->end_at->format('d/m/Y H:i'),
                $coupon->start_at => 'เริ่ม '.$coupon->start_at->format('d/m/Y H:i'),
                $coupon->end_at => 'ถึง '.$coupon->end_at->format('d/m/Y H:i'),
                default => 'ไม่กำหนดช่วงเวลา',
            };
        @endphp
        <article class="px-5 py-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('coupons.show', $coupon) }}" class="font-mono text-sm font-semibold text-gray-800 hover:text-brand-500 dark:text-white/90">{{ $coupon->code }}</a>
                        <x-common.status-badge :status="$coupon->status" />
                    </div>
                    @if ($coupon->description)
                    <p class="mt-1 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $coupon->description }}</p>
                    @endif
                </div>
                <a href="{{ route('coupons.edit', $coupon) }}" class="inline-flex shrink-0 items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">แก้ไข</a>
            </div>

            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3 xl:grid-cols-4">
                <div class="min-w-0">
                    <dt class="text-[11px] font-medium text-gray-400">ส่วนลด</dt>
                    <dd class="mt-0.5 truncate text-sm font-medium text-gray-800 dark:text-white/90">{{ $coupon->discountLabel() }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[11px] font-medium text-gray-400">ยอดขั้นต่ำ</dt>
                    <dd class="mt-0.5 truncate text-sm text-gray-700 dark:text-gray-300">{{ $coupon->min_purchase_amount !== null ? '฿'.number_format((float) $coupon->min_purchase_amount, 2) : 'ไม่กำหนด' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[11px] font-medium text-gray-400">การใช้</dt>
                    <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ $coupon->usage_count }} / {{ $coupon->usage_limit ?? 'ไม่จำกัด' }} · ต่อคน {{ $coupon->per_user_limit ?? 'ไม่จำกัด' }}</dd>
                </div>
                <div class="min-w-0 sm:col-span-3 xl:col-span-1">
                    <dt class="text-[11px] font-medium text-gray-400">ช่วงเวลา</dt>
                    <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ $period }}</dd>
                </div>
            </dl>

            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-medium text-gray-400">ใช้กับ</span>
                @if ($shownTargets->isEmpty())
                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">ทุกรายการในออเดอร์</span>
                @else
                @foreach ($shownTargets as $target)
                <span class="inline-flex max-w-64 truncate rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $target }}</span>
                @endforeach
                @if ($hiddenTargetCount > 0)
                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">+{{ $hiddenTargetCount }}</span>
                @endif
                @endif
            </div>
        </article>
        @empty
        <p class="px-5 py-10 text-center text-sm text-gray-500">ยังไม่มีคูปอง</p>
        @endforelse
    </div>
    @if ($coupons->hasPages())
    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $coupons->links() }}</div>
    @endif
</div>
@endsection
