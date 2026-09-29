@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $banner->title }}" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="aspect-[16/7] bg-gray-50 dark:bg-gray-900">
            @if ($banner->image_url)
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-full w-full object-cover" />
            @else
                <div class="flex h-full items-center justify-center text-sm text-gray-400">ยังไม่มีรูป</div>
            @endif
        </div>
        <div class="space-y-4 p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $banner->title }}</h2>
                        <x-common.status-badge :status="$banner->status" />
                        @if ($banner->isLive())
                            <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">กำลังแสดงบนเว็บ</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-gray-500">{{ $banner->placement_label }} · ลำดับ {{ $banner->sort_order }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('banners.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">กลับ</a>
                    <a href="{{ route('banners.edit', $banner) }}" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">แก้ไข</a>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                    <dt class="text-gray-500">ลิงก์</dt>
                    <dd class="mt-1 break-all text-gray-800 dark:text-white/90">{{ $banner->link_url ?: 'ไม่ระบุ' }}</dd>
                </div>
                <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                    <dt class="text-gray-500">ช่วงเวลา</dt>
                    <dd class="mt-1 text-gray-800 dark:text-white/90">
                        {{ $banner->published_at?->format('d/m/Y H:i') ?: 'ทันทีที่เผยแพร่' }}
                        –
                        {{ $banner->expired_at?->format('d/m/Y H:i') ?: 'ไม่มีวันสิ้นสุด' }}
                    </dd>
                </div>
            </dl>
            @if ($banner->excerpt)
                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $banner->excerpt }}</p>
            @endif
        </div>
    </div>
@endsection
