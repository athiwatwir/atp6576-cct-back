@extends('layouts.app')

@section('content')
    <style>[x-cloak]{display:none!important}</style>
    @php
        $statusLabels = [
            'draft' => 'ร่าง',
            'published' => 'เปิดใช้งาน',
            'inactive' => 'ปิดใช้งาน',
            'active' => 'เปิดใช้งาน',
        ];
        $statusClasses = [
            'draft' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
            'published' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            'inactive' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
            'active' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
        ];
        $tabs = [
            'overview' => 'ภาพรวม',
            'chapters' => 'บทเรียน',
            'quizzes' => 'แบบฝึกหัด',
            'exams' => 'ข้อสอบ',
            'materials' => 'สื่อการสอน',
            'reviews' => 'รีวิว',
        ];
    @endphp

    <div x-data="{
        tab: '{{ $activeTab }}',
        selectedChapter: {{ $selectedChapter?->id ?? 'null' }},
        showChapterForm: false,
        showVideoForm: false,
        showDocumentForm: false,
        showQuizForm: false,
        showExamForm: false,
        editingVideoId: null,
        editingChapter: false,
    }">
        <x-common.page-breadcrumb pageTitle="{{ $course->name }}" />

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

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-9">
                {{-- Header --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="h-24 w-36 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                @if ($course->thumbnail)
                                    <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->name }}" class="h-full w-full object-cover" />
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-xs text-gray-400">ไม่มีรูปปก</div>
                                @endif
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $course->name }}</h2>
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$course->status] ?? $statusClasses['draft'] }}">
                                        {{ $statusLabels[$course->status] ?? $course->status }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $course->slug }}</p>
                                <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-400">
                                    {{ $course->short_description ?: ($course->description ?: 'ยังไม่มีคำอธิบายคอร์ส') }}
                                </p>
                                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span>ผู้สอน: <strong class="font-medium text-gray-800 dark:text-white/90">{{ $course->instructor?->name ?? '-' }}</strong></span>
                                    <span>หมวดหมู่: <strong class="font-medium text-gray-800 dark:text-white/90">{{ $course->category?->name ?? '-' }}</strong></span>
                                    <span>วิชา: <strong class="font-medium text-gray-800 dark:text-white/90">{{ $course->subject?->name ?? '-' }}</strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                กลับ
                            </a>
                            <a href="{{ route('courses.edit', $course) }}"
                                class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                                แก้ไขคอร์ส
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="overflow-x-auto border-b border-gray-100 dark:border-gray-800">
                        <div class="flex min-w-max gap-1 px-3 py-2">
                            @foreach ($tabs as $key => $label)
                                <button type="button" @click="tab = '{{ $key }}'"
                                    class="rounded-lg px-4 py-2.5 text-sm font-medium transition"
                                    :class="tab === '{{ $key }}'
                                        ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'
                                        : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5'">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        @include('pages.courses.partials.show-overview')
                        @include('pages.courses.partials.show-chapters')
                        @include('pages.courses.partials.show-assessments', ['panel' => 'quizzes', 'types' => ['exercise', 'quiz'], 'title' => 'แบบฝึกหัด'])
                        @include('pages.courses.partials.show-assessments', ['panel' => 'exams', 'types' => ['exam'], 'title' => 'ข้อสอบ'])
                        @include('pages.courses.partials.show-materials')
                        @include('pages.courses.partials.show-reviews')
                    </div>
                </div>
            </div>

            {{-- Right sidebar --}}
            <div class="space-y-6 xl:col-span-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลคอร์ส</h3>
                    <form method="POST" action="{{ route('courses.status', $course) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</label>
                            <select name="status"
                                class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                @foreach (['draft' => 'ร่าง', 'published' => 'เปิดใช้งาน', 'inactive' => 'ปิดใช้งาน'] as $value => $label)
                                    <option value="{{ $value }}" @selected($course->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 w-full rounded-lg px-3 py-2 text-sm font-medium text-white">
                            บันทึกสถานะ
                        </button>
                    </form>

                    <div class="mt-5 space-y-3 border-t border-gray-100 pt-5 text-sm dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">ราคา</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">฿{{ number_format((float) $course->price, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">ราคาพิเศษ</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">
                                {{ $course->sale_price !== null ? '฿'.number_format((float) $course->sale_price, 2) : '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">วันที่เผยแพร่</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">
                                {{ $course->published_at?->format('d/m/Y') ?? '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">นักเรียนทั้งหมด</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ number_format($stats['students']) }} คน</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">สถิติการเรียนรู้</h3>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['videos'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">วิดีโอ</div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['documents'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">เอกสาร</div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['quizzes'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">แบบฝึกหัด</div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/5">
                            <div class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['exams'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ชุดข้อสอบ</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">การจัดการด่วน</h3>
                    <div class="space-y-2">
                        <a href="{{ route('courses.edit', $course) }}"
                            class="flex w-full items-center justify-center rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                            แก้ไขข้อมูลคอร์ส
                        </a>
                        <form method="POST" action="{{ route('courses.status', $course) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="{{ $course->status === 'published' ? 'inactive' : 'published' }}">
                            <button type="submit"
                                class="flex w-full items-center justify-center rounded-lg border border-warning-300 px-3 py-2.5 text-sm font-medium text-warning-700 hover:bg-warning-50 dark:border-warning-500/40 dark:text-warning-400 dark:hover:bg-warning-500/10">
                                {{ $course->status === 'published' ? 'ปิดใช้งานคอร์ส' : 'เปิดใช้งานคอร์ส' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('courses.destroy', $course) }}"
                            onsubmit="return confirm('ต้องการลบคอร์สนี้หรือไม่?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex w-full items-center justify-center rounded-lg border border-error-300 px-3 py-2.5 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10">
                                ลบคอร์ส
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
