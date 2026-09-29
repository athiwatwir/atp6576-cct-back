@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="แบนเนอร์" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">แบนเนอร์</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">รูปที่แสดงบนหน้าเว็บนักเรียน ตามตำแหน่งและช่วงเวลา</p>
            </div>
            <a href="{{ route('banners.create') }}" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">+ เพิ่มแบนเนอร์</a>
        </div>

        <form method="GET" action="{{ route('banners.index') }}" class="grid grid-cols-1 gap-3 border-b border-gray-100 px-5 py-4 md:grid-cols-4 dark:border-gray-800">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อ" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            <select name="placement" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">ทุกตำแหน่ง</option>
                @foreach (\App\Enums\BannerPlacement::options() as $value => $label)
                    <option value="{{ $value }}" @selected($filters['placement'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">ทุกสถานะ</option>
                @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg text-sm font-medium text-white">ค้นหา</button>
        </form>

        <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
            @forelse ($banners as $banner)
                <article class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
                    <div class="aspect-[16/7] bg-gray-50 dark:bg-gray-900">
                        @if ($banner->image_url)
                            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full items-center justify-center text-sm text-gray-400">ยังไม่มีรูป</div>
                        @endif
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-medium text-gray-800 dark:text-white/90">{{ $banner->title }}</h4>
                            <x-common.status-badge :status="$banner->status" />
                            @if ($banner->isLive())
                                <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">กำลังแสดง</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-white/10">{{ $banner->placement_label }}</span>
                            <span>ลำดับ {{ $banner->sort_order }}</span>
                            @if ($banner->published_at)
                                <span>เริ่ม {{ $banner->published_at->format('d/m/Y H:i') }}</span>
                            @endif
                            @if ($banner->expired_at)
                                <span>ถึง {{ $banner->expired_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <form method="POST" action="{{ route('banners.move', $banner) }}">
                                @csrf
                                <input type="hidden" name="direction" value="up" />
                                @foreach (['search', 'status', 'placement'] as $filter)
                                    <input type="hidden" name="{{ $filter }}" value="{{ $filters[$filter] }}" />
                                @endforeach
                                <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300">ขึ้น</button>
                            </form>
                            <form method="POST" action="{{ route('banners.move', $banner) }}">
                                @csrf
                                <input type="hidden" name="direction" value="down" />
                                @foreach (['search', 'status', 'placement'] as $filter)
                                    <input type="hidden" name="{{ $filter }}" value="{{ $filters[$filter] }}" />
                                @endforeach
                                <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300">ลง</button>
                            </form>
                            <a href="{{ route('banners.show', $banner) }}" class="inline-flex items-center rounded-lg border border-blue-light-300 bg-blue-light-50 px-3 py-2 text-xs font-medium text-blue-light-700 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400">ดู</a>
                            <a href="{{ route('banners.edit', $banner) }}" class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">แก้ไข</a>
                            <form method="POST" action="{{ route('banners.destroy', $banner) }}" onsubmit="return confirm('ต้องการลบแบนเนอร์นี้หรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center rounded-lg border border-error-300 bg-error-50 px-3 py-2 text-xs font-medium text-error-600 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">ลบ</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-12 text-center text-sm text-gray-500">ยังไม่มีแบนเนอร์</div>
            @endforelse
        </div>

        @if ($banners->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $banners->links() }}</div>
        @endif
    </div>
@endsection
