@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $review->name }}" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="space-y-4 p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="h-28 w-28 shrink-0 overflow-hidden rounded-2xl bg-gray-50 dark:bg-gray-900">
                    @if ($review->image_url)
                        <img src="{{ $review->image_url }}" alt="{{ $review->name }}" class="h-full w-full object-cover" />
                    @else
                        <div class="flex h-full items-center justify-center text-sm text-gray-400">ยังไม่มีรูป</div>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $review->name }}</h2>
                                <x-common.status-badge :status="$review->status" />
                            </div>
                            <p class="mt-2 text-sm text-gray-500">ลำดับ {{ $review->sort_order }}</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('reviews.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">กลับ</a>
                            <a href="{{ route('reviews.edit', $review) }}" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">แก้ไข</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-4 text-sm leading-6 whitespace-pre-line text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">{{ $review->content }}</div>
        </div>
    </div>
@endsection
