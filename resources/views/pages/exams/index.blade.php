@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="ข้อสอบ (ขายแยก)" />

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

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการข้อสอบขายแยก</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">จัดการข้อสอบที่ไม่ผูกกับคอร์ส สำหรับขายเป็นรายการเดี่ยว</p>
            </div>
            <a href="{{ route('exams.create') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                + เพิ่มข้อสอบ
            </a>
        </div>

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <form method="GET" action="{{ route('exams.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อ / รายละเอียด"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div>
                    <select name="status"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">ทุกสถานะ</option>
                        <option value="draft" @selected($filters['status'] === 'draft')>ร่าง</option>
                        <option value="published" @selected($filters['status'] === 'published')>เปิดขาย</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>ปิดใช้งาน</option>
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-medium text-white whitespace-nowrap">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ข้อสอบ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ราคา</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">คำถาม</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">เวลา</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($exams as $exam)
                        @php
                            $statusClasses = [
                                'draft' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
                                'published' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                                'inactive' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
                            ];
                            $statusLabels = [
                                'draft' => 'ร่าง',
                                'published' => 'เปิดขาย',
                                'inactive' => 'ปิดใช้งาน',
                            ];
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                        @if ($exam->thumbnail_url)
                                            <img src="{{ $exam->thumbnail_url }}" alt="{{ $exam->title }}" class="h-full w-full object-cover" />
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-[10px] text-gray-400">ไม่มีปก</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 dark:text-white/90">{{ $exam->title }}</div>
                                        @if ($exam->description)
                                            <div class="mt-0.5 line-clamp-1 text-sm text-gray-500 dark:text-gray-400">{{ $exam->description }}</div>
                                        @endif
                                        <div class="mt-1 font-mono text-[11px] text-gray-400">{{ $exam->code ?? $exam->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">
                                ฿{{ number_format((float) $exam->price, 2) }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $exam->questions_count }} ข้อ
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $exam->duration_minutes ? $exam->duration_minutes.' นาที' : 'ไม่จำกัด' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$exam->status] ?? $statusClasses['draft'] }}">
                                    {{ $statusLabels[$exam->status] ?? $exam->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('exams.show', $exam) }}"
                                        class="inline-flex items-center rounded-lg border border-blue-light-300 bg-blue-light-50 px-3 py-2 text-xs font-medium text-blue-light-700 hover:bg-blue-light-100 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400">
                                        จัดการ
                                    </a>
                                    <a href="{{ route('exams.edit', $exam) }}"
                                        class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
                                        แก้ไข
                                    </a>
                                    <form method="POST" action="{{ route('exams.destroy', $exam) }}"
                                        onsubmit="return confirm('ต้องการลบข้อสอบนี้และคำถามทั้งหมดหรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center rounded-lg border border-error-300 bg-error-50 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">
                                            ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                ยังไม่มีข้อสอบขายแยก กด “+ เพิ่มข้อสอบ” เพื่อเริ่มต้น
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($exams->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                {{ $exams->links() }}
            </div>
        @endif
    </div>
@endsection
