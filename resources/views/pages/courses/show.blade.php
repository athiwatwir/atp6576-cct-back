@extends('layouts.app')

@section('content')
<style>
    [x-cloak] {
        display: none !important
    }

</style>
@php
$tabs = [
'overview' => 'ภาพรวม',
'chapters' => 'บทเรียน',
'quizzes' => 'แบบฝึกหัด',
'exams' => 'ข้อสอบ',
'materials' => 'เอกสารทั้งหมด',
];

/*
| Button hierarchy (ใช้ร่วมกับ partials ที่ include)
| 1. primary — เพิ่ม / บันทึก / CTA หลัก
| 2. secondary — กลับ / ยกเลิก
| 3. edit — แก้ไข
| 4. view — ดู / เปิด / จัดการคำถาม
| 5. success — เพิ่มเอกสาร / soft ที่ไม่ใช่ CTA หลัก
| 6. warning — เปิด-ปิดใช้งาน
| 7. danger — ลบ
*/
$btnBase = 'inline-flex items-center justify-center gap-1.5 rounded-lg font-medium transition focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-60';
$btn = [
'primary' => $btnBase.' bg-brand-500 px-4 py-2.5 text-sm text-white shadow-theme-xs hover:bg-brand-600',
'primarySm' => $btnBase.' bg-brand-500 px-3 py-1.5 text-xs text-white shadow-theme-xs hover:bg-brand-600',
'secondary' => $btnBase.' border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]',
'secondarySm' => $btnBase.' border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]',
'edit' => $btnBase.' border border-warning-300 bg-warning-50 px-3 py-1.5 text-xs text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400 dark:hover:bg-warning-500/20',
'editActive' => $btnBase.' bg-warning-500 px-3 py-1.5 text-xs text-white shadow-theme-xs hover:bg-warning-600',
'view' => $btnBase.' border border-blue-light-300 bg-blue-light-50 px-3 py-1.5 text-xs text-blue-light-700 hover:bg-blue-light-100 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400 dark:hover:bg-blue-light-500/20',
'viewActive' => $btnBase.' bg-blue-light-500 px-3 py-1.5 text-xs text-white shadow-theme-xs hover:bg-blue-light-600',
'viewMd' => $btnBase.' border border-blue-light-300 bg-blue-light-50 px-4 py-2.5 text-sm text-blue-light-700 hover:bg-blue-light-100 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400',
'success' => $btnBase.' border border-success-300 bg-success-50 px-3 py-1.5 text-xs text-success-700 hover:bg-success-100 dark:border-success-500/40 dark:bg-success-500/10 dark:text-success-400 dark:hover:bg-success-500/20',
'successActive' => $btnBase.' bg-success-500 px-3 py-1.5 text-xs text-white shadow-theme-xs hover:bg-success-600',
'successSolid' => $btnBase.' bg-success-500 px-4 py-2.5 text-sm text-white shadow-theme-xs hover:bg-success-600',
'warning' => $btnBase.' border border-warning-300 bg-warning-50 px-3 py-2.5 text-sm text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400 dark:hover:bg-warning-500/20',
'danger' => $btnBase.' border border-error-300 bg-error-50 px-3 py-1.5 text-xs text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20',
'dangerMd' => $btnBase.' border border-error-300 bg-error-50 px-3 py-2.5 text-sm text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20',
'icon' => 'flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition hover:bg-gray-200 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white',
];
@endphp

<div x-data="{
        tab: '{{ $activeTab }}',
        selectedChapter: {{ $selectedChapter?->id ?? 'null' }},
        selectedAssessment: {{ $selectedAssessment?->id ?? 'null' }},
        showChapterForm: false,
        showVideoForm: false,
        showDocumentForm: false,
        showQuizForm: false,
        showExamForm: false,
        showQuestionForm: false,
        editingVideoId: null,
        editingChapter: false,
        editingQuestionId: null,
        editingAssessmentId: null,
        uploadingVideo: false,
        uploadProgress: 0,
        uploadStage: 'idle',
        uploadVideoError: null,
        playingVideo: false,
        playingVideoUrl: null,
        playingVideoTitle: null,
        chapterSortable: null,
        reorderingChapters: false,
        flashType: null,
        flashMessage: null,
        flashTimer: null,
        showFlash(type, message) {
            if (this.flashTimer) clearTimeout(this.flashTimer);
            this.flashType = type;
            this.flashMessage = message;
            this.flashTimer = setTimeout(() => {
                this.flashType = null;
                this.flashMessage = null;
                this.flashTimer = null;
            }, 3000);
        },
        openVideoModal() {
            this.showVideoForm = true;
            this.showDocumentForm = false;
            this.editingChapter = false;
            this.editingVideoId = null;
            this.uploadingVideo = false;
            this.uploadProgress = 0;
            this.uploadStage = 'idle';
            this.uploadVideoError = null;
        },
        closeVideoModal() {
            if (this.uploadingVideo) return;
            this.showVideoForm = false;
            this.uploadProgress = 0;
            this.uploadStage = 'idle';
            this.uploadVideoError = null;
        },
        openVideoPlayer(url, title) {
            if (!url) {
                this.showFlash('error', 'ยังไม่มีไฟล์วิดีโอให้เล่น');
                return;
            }
            this.playingVideoUrl = url;
            this.playingVideoTitle = title || 'วิดีโอ';
            this.playingVideo = true;
            this.$nextTick(() => {
                const player = this.$refs.videoPlayer;
                if (player) {
                    player.load();
                    player.play().catch(() => {});
                }
            });
        },
        closeVideoPlayer() {
            const player = this.$refs.videoPlayer;
            if (player) {
                player.pause();
                player.currentTime = 0;
            }
            this.playingVideo = false;
            this.playingVideoUrl = null;
            this.playingVideoTitle = null;
        },
        uploadVideo(event) {
            window.uploadVideoForm(this, event);
        },
        initChapterSortable(el) {
            if (!window.Sortable || this.chapterSortable) return;

            this.chapterSortable = window.Sortable.create(el, {
                animation: 150,
                handle: '.chapter-drag-handle',
                draggable: '[data-chapter-id]',
                ghostClass: 'opacity-50',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;

                    const items = [...el.querySelectorAll('[data-chapter-id]')];
                    const order = items.map((item) => Number(item.dataset.chapterId));

                    items.forEach((item, index) => {
                        const badge = item.querySelector('.chapter-seq');
                        if (badge) badge.textContent = String(index + 1);
                    });

                    this.reorderingChapters = true;

                    fetch(@js(route('courses.chapters.reorder', $course)), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ order }),
                    })
                        .then((response) => {
                            if (!response.ok) throw new Error('reorder failed');
                            this.showFlash('success', 'เรียงลำดับบทเรียนเรียบร้อยแล้ว');
                        })
                        .catch((error) => {
                            console.error(error);
                            this.showFlash('error', 'เรียงลำดับไม่สำเร็จ กรุณาลองอีกครั้ง');
                            setTimeout(() => window.location.reload(), 1200);
                        })
                        .finally(() => {
                            this.reorderingChapters = false;
                        });
                },
            });
        },
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

    <template x-if="flashMessage">
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm" :class="flashType === 'success'
                ? 'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400'
                : 'border-error-200 bg-error-50 text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400'" x-text="flashMessage"></div>
    </template>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-9">
            {{-- Header --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="from-brand-500/10 via-transparent to-blue-light-500/5 bg-gradient-to-br px-5 py-5 lg:px-6 lg:py-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="ring-brand-100 dark:ring-brand-500/20 h-28 w-40 shrink-0 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-theme-xs ring-4 dark:border-gray-700 dark:bg-gray-900">
                                @if ($course->thumbnail)
                                <img src="{{ $course->thumbnail_url }}" alt="{{ $course->name }}" class="h-full w-full object-cover" />
                                @else
                                <div class="flex h-full w-full flex-col items-center justify-center gap-1 text-xs text-gray-400">
                                    <svg class="h-6 w-6 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    ไม่มีรูปปก
                                </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90 sm:text-2xl">{{ $course->name }}</h2>
                                    <x-common.status-badge :status="$course->status" />
                                </div>
                                <p class="mt-1 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $course->slug }}</p>
                                <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-400">
                                    {{ $course->short_description ?: ($course->description ?: 'ยังไม่มีคำอธิบายคอร์ส') }}
                                </p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/80 px-2.5 py-1.5 text-xs text-gray-600 shadow-theme-xs dark:bg-white/5 dark:text-gray-300">
                                        <span class="text-gray-400">ผู้สอน</span>
                                        @if ($course->instructor)
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="h-6 w-6 overflow-hidden rounded-full border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                                @if ($course->instructor->image_url)
                                                <img src="{{ $course->instructor->image_url }}" alt="{{ $course->instructor->name }}" class="h-full w-full object-cover" />
                                                @else
                                                <span class="flex h-full w-full items-center justify-center text-[9px] text-gray-400">N/A</span>
                                                @endif
                                            </span>
                                            <strong class="font-medium text-gray-800 dark:text-white/90">{{ $course->instructor->name }}</strong>
                                        </span>
                                        @else
                                        <strong class="font-medium text-gray-800 dark:text-white/90">-</strong>
                                        @endif
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/80 px-2.5 py-1.5 text-xs text-gray-600 shadow-theme-xs dark:bg-white/5 dark:text-gray-300">
                                        <span class="text-gray-400">หมวด</span>
                                        <strong class="font-medium text-gray-800 dark:text-white/90">{{ $course->category?->name ?? '-' }}</strong>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/80 px-2.5 py-1.5 text-xs text-gray-600 shadow-theme-xs dark:bg-white/5 dark:text-gray-300">
                                        <span class="text-gray-400">วิชา</span>
                                        <strong class="font-medium text-gray-800 dark:text-white/90">{{ $course->subject?->name ?? '-' }}</strong>
                                    </span>
                                </div>
                                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <div class="flex items-start gap-3 rounded-xl border px-3 py-3 {{ $course->is_trial_available ? 'border-brand-200 bg-brand-50 dark:border-brand-500/30 dark:bg-brand-500/10' : 'border-gray-200 bg-white/80 dark:border-gray-700 dark:bg-white/5' }}">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $course->is_trial_available ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' }}">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 8.5v7l6-3.5-6-3.5z" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-medium {{ $course->is_trial_available ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500' }}">ทดลองเรียน</div>
                                            <div class="text-sm font-semibold {{ $course->is_trial_available ? 'text-brand-700 dark:text-brand-300' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $course->is_trial_available ? 'เปิดอยู่' : 'ปิดอยู่' }}
                                            </div>
                                            <p class="mt-0.5 text-xs leading-5 {{ $course->is_trial_available ? 'text-brand-700/80 dark:text-brand-300/80' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $course->is_trial_available ? 'ผู้เรียนดูเนื้อหาทดลองได้ก่อนตัดสินใจซื้อ' : 'ต้องลงทะเบียนคอร์สก่อนจึงจะเข้าเรียนได้' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3 rounded-xl border px-3 py-3 {{ $course->is_featured ? 'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' : 'border-gray-200 bg-white/80 dark:border-gray-700 dark:bg-white/5' }}">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $course->is_featured ? 'bg-warning-500 text-white' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' }}">
                                            <svg class="h-5 w-5" fill="{{ $course->is_featured ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5l1.76 4.27a1 1 0 00.84.61l4.6.4-3.5 3.02a1 1 0 00-.32.98l1.07 4.47-3.95-2.4a1 1 0 00-1.04 0l-3.95 2.4 1.07-4.47a1 1 0 00-.32-.98l-3.5-3.02 4.6-.4a1 1 0 00.84-.61L11.48 3.5z" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-medium {{ $course->is_featured ? 'text-warning-700 dark:text-warning-400' : 'text-gray-400 dark:text-gray-500' }}">คอร์สแนะนำ</div>
                                            <div class="text-sm font-semibold {{ $course->is_featured ? 'text-warning-700 dark:text-warning-300' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $course->is_featured ? 'แสดงในรายการแนะนำ' : 'ไม่ได้ตั้งเป็นแนะนำ' }}
                                            </div>
                                            <p class="mt-0.5 text-xs leading-5 {{ $course->is_featured ? 'text-warning-700/80 dark:text-warning-300/80' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $course->is_featured ? 'คอร์สนี้ถูกไฮไลต์ให้ผู้เรียนเห็นก่อน' : 'ไม่แสดงคอร์สนี้ในส่วนคอร์สแนะนำ' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <a href="{{ route('courses.index') }}" class="{{ $btn['secondary'] }}">กลับ</a>
                            <a href="{{ route('courses.edit', $course) }}" class="{{ $btn['primary'] }}">แก้ไขคอร์ส</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="overflow-x-auto border-b border-gray-100 dark:border-gray-800">
                    <div class="flex min-w-max gap-1 p-2">
                        @foreach ($tabs as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'" class="rounded-xl px-4 py-2.5 text-sm font-medium transition" :class="tab === '{{ $key }}'
                                        ? 'bg-brand-500 text-white shadow-theme-xs'
                                        : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5'">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    @include('pages.courses.partials.show-overview')
                    @include('pages.courses.partials.show-chapters')
                    @include('pages.courses.partials.show-assessments', ['panel' => 'quizzes', 'types' => ['quiz'], 'title' => 'แบบฝึกหัด'])
                    @include('pages.courses.partials.show-assessments', ['panel' => 'exams', 'types' => ['exam'], 'title' => 'ข้อสอบ'])
                    @include('pages.courses.partials.show-materials')
                    @include('pages.courses.partials.show-reviews')
                </div>
            </div>
        </div>

        {{-- Right sidebar --}}
        <div class="space-y-6 xl:col-span-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลคอร์ส</h3>
                <form method="POST" action="{{ route('courses.status', $course) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</label>
                        <select name="status" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                            <option value="{{ $value }}" @selected($course->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="{{ $btn['successSolid'] }} w-full">บันทึกสถานะ</button>
                </form>

                <div class="mt-5 space-y-3 border-t border-gray-100 pt-5 text-sm dark:border-gray-800">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500 dark:text-gray-400">ราคา</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">฿{{ number_format((float) $course->price, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500 dark:text-gray-400">ราคาพิเศษ</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">
                            {{ $course->sale_price !== null ? '฿'.number_format((float) $course->sale_price, 2) : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500 dark:text-gray-400">วันที่เผยแพร่</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">
                            {{ $course->published_at?->format('d/m/Y') ?? '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-brand-50 px-3 py-2.5 dark:bg-brand-500/10">
                        <span class="text-brand-600 dark:text-brand-400">นักเรียนทั้งหมด</span>
                        <span class="text-brand-700 font-semibold dark:text-brand-300">{{ number_format($stats['students']) }} คน</span>
                    </div>
                </div>
            </div>



            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">การจัดการด่วน</h3>
                <div class="space-y-2">
                    <a href="{{ route('courses.edit', $course) }}" class="{{ $btn['viewMd'] }} w-full">แก้ไขข้อมูลคอร์ส</a>
                    <form method="POST" action="{{ route('courses.status', $course) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $course->status === 'published' ? 'inactive' : 'published' }}">
                        <button type="submit" class="{{ $btn['warning'] }} w-full">
                            {{ $course->status === 'published' ? 'ปิดใช้งานคอร์ส' : 'เปิดใช้งานคอร์ส' }}
                        </button>
                    </form>
                    <button type="button" data-no-loading @click="$dispatch('open-course-delete-modal', { id: {{ $course->id }}, name: @js($course->name), code: @js($course->code) })" class="{{ $btn['dangerMd'] }} w-full">
                        ลบคอร์ส
                    </button>
                </div>
            </div>

            <x-common.activity-logs :entity="$course" :limit="8" title="ประวัติการแก้ไขคอร์ส" />
        </div>
    </div>
</div>

<x-courses.delete-modal />
@endsection
