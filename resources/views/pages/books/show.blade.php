@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $book->name }}" />

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

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start">
                    <div class="h-36 w-28 shrink-0 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                        @if ($book->thumbnail_url)
                            <img src="{{ $book->thumbnail_url }}" alt="{{ $book->name }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center text-xs text-gray-400">ไม่มีปก</div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $book->name }}</h2>
                            <x-common.status-badge :status="$book->status" />
                            <span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                หนังสือ
                            </span>
                        </div>
                        <p class="mt-1 font-mono text-xs text-gray-400">{{ $book->slug }}</p>
                        <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-400 whitespace-pre-line">
                            {{ $book->description ?: 'ยังไม่มีรายละเอียด' }}
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('books.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        กลับ
                    </a>
                    <a href="{{ route('books.edit', $book) }}"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs">
                        แก้ไขหนังสือ
                    </a>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลการขาย</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-lg bg-brand-50 px-3 py-2.5 dark:bg-brand-500/10">
                        <span class="text-brand-600 dark:text-brand-400">ราคาขาย</span>
                        <span class="font-semibold text-brand-700 dark:text-brand-300">฿{{ number_format($book->effective_price, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500">ราคาปกติ</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">฿{{ number_format((float) $book->price, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500">ราคาลด</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">
                            {{ $book->sale_price !== null ? '฿'.number_format((float) $book->sale_price, 2) : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500">คงเหลือ</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">
                            {{ $book->stock !== null ? number_format($book->stock).' เล่ม' : 'ไม่จำกัด' }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('books.destroy', $book) }}" class="mt-5"
                    onsubmit="return confirm('ต้องการลบหนังสือนี้หรือไม่?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg border border-error-300 bg-error-50 px-4 py-2.5 text-sm font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">
                        ลบหนังสือ
                    </button>
                </form>
            </div>

            <x-common.activity-logs :entity="$book" :limit="8" title="ประวัติการแก้ไขหนังสือ" />
        </div>
    </div>
@endsection
