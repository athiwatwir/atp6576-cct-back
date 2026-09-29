@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="บทความ" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">บทความ</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ข่าวและบทความที่แสดงบนเว็บนักเรียน</p>
            </div>
            <a href="{{ route('articles.create') }}" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">+ เพิ่มบทความ</a>
        </div>

        <form method="GET" action="{{ route('articles.index') }}" class="grid grid-cols-1 gap-3 border-b border-gray-100 px-5 py-4 md:grid-cols-4 dark:border-gray-800">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาหัวข้อหรือคำเกริ่น" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            </div>
            <select name="status" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">ทุกสถานะ</option>
                @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 h-11 rounded-lg text-sm font-medium text-white">ค้นหา</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">บทความ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">เผยแพร่</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">สถานะ</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($articles as $article)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                        @if ($article->image_url)
                                            <img src="{{ $article->image_url }}" alt="" class="h-full w-full object-cover" />
                                        @else
                                            <div class="flex h-full items-center justify-center text-[10px] text-gray-400">ไม่มีรูป</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $article->title }}</div>
                                        @if ($article->excerpt)
                                            <div class="mt-0.5 line-clamp-1 text-sm text-gray-500">{{ $article->excerpt }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500">
                                {{ $article->published_at?->format('d/m/Y H:i') ?: '-' }}
                                @if ($article->isLive())
                                    <div class="mt-1 text-xs text-success-600">กำลังแสดง</div>
                                @endif
                            </td>
                            <td class="px-5 py-4"><x-common.status-badge :status="$article->status" /></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('articles.show', $article) }}" class="inline-flex items-center rounded-lg border border-blue-light-300 bg-blue-light-50 px-3 py-2 text-xs font-medium text-blue-light-700 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400">ดู</a>
                                    <a href="{{ route('articles.edit', $article) }}" class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">แก้ไข</a>
                                    <form method="POST" action="{{ route('articles.destroy', $article) }}" onsubmit="return confirm('ต้องการลบบทความนี้หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-error-300 bg-error-50 px-3 py-2 text-xs font-medium text-error-600 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">ยังไม่มีบทความ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($articles->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $articles->links() }}</div>
        @endif
    </div>
@endsection
