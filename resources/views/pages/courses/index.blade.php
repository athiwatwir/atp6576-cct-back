@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="คอร์ส" />

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
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการคอร์ส</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">จัดการคอร์สเรียนที่เปิดขายและเผยแพร่</p>
        </div>
        <a href="{{ route('courses.create') }}" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
            เพิ่มคอร์ส
        </a>
    </div>

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <form method="GET" action="{{ route('courses.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อคอร์ส" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>
            <div>
                <select name="subject_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">ทุกวิชา</option>
                    @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((int) $filters['subject_id']===$subject->id)>
                        {{ $subject->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="category_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) $filters['category_id']===$category->id)>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <select name="status" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">ทุกสถานะ</option>
                    @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 text-sm font-medium text-white whitespace-nowrap">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">คอร์ส</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">วิชา</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ผู้สอน</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ราคา</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">บท</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($courses as $course)
                <tr>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-16 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                @if ($course->thumbnail)
                                <img src="{{ $course->thumbnail_url }}" alt="{{ $course->name }}" class="h-full w-full object-cover" />
                                @else
                                <div class="flex h-full w-full items-center justify-center text-xs text-gray-400">N/A</div>
                                @endif
                            </div>
                            <div>
                                <div class="font-medium text-gray-800 dark:text-white/90"><a href="{{ route('courses.show', $course) }}" class="hover:underline">{{ $course->name }}</a></div>
                                @if ($course->code)
                                <div class="mt-1 font-mono text-[11px] text-gray-400">{{ $course->code }}</div>
                                @endif
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if ($course->is_featured)
                                    <span class="bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium">แนะนำ</span>
                                    @endif
                                    @if ($course->is_trial_available)
                                    <span class="bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium">ทดลองเรียน</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ $course->subject?->name ?? '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                        @if ($course->instructor)
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 shrink-0 overflow-hidden rounded-full border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                @if ($course->instructor->image_url)
                                <img src="{{ $course->instructor->image_url }}" alt="{{ $course->instructor->name }}" class="h-full w-full object-cover" />
                                @else
                                <div class="flex h-full w-full items-center justify-center text-[10px] text-gray-400">N/A</div>
                                @endif
                            </div>
                            <span>{{ $course->instructor->name }}</span>
                        </div>
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                        @if ($course->sale_price !== null)
                        <div class="font-medium text-gray-800 dark:text-white/90">
                            ฿{{ number_format((float) $course->sale_price, 2) }}
                        </div>
                        <div class="text-xs text-gray-400 line-through">
                            ฿{{ number_format((float) $course->price, 2) }}
                        </div>
                        @else
                        ฿{{ number_format((float) $course->price, 2) }}
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ $course->chapters_count }}
                    </td>
                    <td class="px-5 py-4">
                        <x-common.status-badge :status="$course->status" />
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('courses.show', $course) }}" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center rounded-lg px-3 py-2 text-xs font-medium text-white">
                                ดู
                            </a>
                            <a href="{{ route('courses.edit', $course) }}" class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                แก้ไข
                            </a>
                            <button type="button" data-no-loading @click="$dispatch('open-course-delete-modal', { id: {{ $course->id }}, name: @js($course->name), code: @js($course->code) })" class="inline-flex items-center rounded-lg border border-error-300 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10">
                                ลบ
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        ไม่พบคอร์ส
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($courses->hasPages())
    <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
        {{ $courses->links() }}
    </div>
    @endif
</div>

<x-courses.delete-modal />
@endsection
