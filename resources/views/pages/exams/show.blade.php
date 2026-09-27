@extends('layouts.app')

@section('content')
@php
$statusLabels = [
    'draft' => 'ร่าง',
    'published' => 'เปิดขาย',
    'inactive' => 'ปิดใช้งาน',
];
$statusClasses = [
    'draft' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
    'published' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
    'inactive' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
];
$choiceLabels = ['ก', 'ข', 'ค', 'ง'];
@endphp

<style>[x-cloak] { display: none !important; }</style>

<div x-data="{ showQuestionForm: false, editingQuestionId: null }">
    <x-common.page-breadcrumb pageTitle="{{ $exam->title }}" />

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
                    <div class="h-28 w-40 shrink-0 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                        @if ($exam->thumbnail_url)
                            <img src="{{ $exam->thumbnail_url }}" alt="{{ $exam->title }}" class="h-full w-full object-cover" />
                        @else
                            <div class="flex h-full w-full items-center justify-center text-xs text-gray-400">ไม่มีหน้าปก</div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $exam->title }}</h2>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$exam->status] ?? $statusClasses['draft'] }}">
                                {{ $statusLabels[$exam->status] ?? $exam->status }}
                            </span>
                            <span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                ขายแยก
                            </span>
                        </div>
                        <p class="mt-1 font-mono text-xs text-gray-400">{{ $exam->slug }}</p>
                        <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
                            {{ $exam->description ?: 'ยังไม่มีรายละเอียด' }}
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('exams.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        กลับ
                    </a>
                    <a href="{{ route('exams.edit', $exam) }}"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs">
                        แก้ไขข้อสอบ
                    </a>
                </div>
            </div>
        </div>

        <div class="space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลการขาย</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-lg bg-brand-50 px-3 py-2.5 dark:bg-brand-500/10">
                        <span class="text-brand-600 dark:text-brand-400">ราคา</span>
                        <span class="font-semibold text-brand-700 dark:text-brand-300">฿{{ number_format((float) $exam->price, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500">คำถาม</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $exam->questions->count() }} ข้อ</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500">เวลา</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $exam->duration_minutes ? $exam->duration_minutes.' นาที' : 'ไม่จำกัด' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500">คะแนนผ่าน</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $exam->passing_score !== null ? number_format((float) $exam->passing_score, 0).'%' : '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
                        <span class="text-gray-500">ทำได้สูงสุด</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $exam->max_attempts ? $exam->max_attempts.' ครั้ง' : 'ไม่จำกัด' }}</span>
                    </div>
                </div>
            </div>

            <x-common.activity-logs :entity="$exam" :limit="8" title="ประวัติการแก้ไขข้อสอบ" />
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">คำถาม</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เพิ่มคำถามแบบปรนัย พร้อมเลือกเฉลย</p>
            </div>
            <button type="button" @click="showQuestionForm = true; editingQuestionId = null"
                class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs">
                + เพิ่มคำถาม
            </button>
        </div>

        <div class="space-y-3 p-5">
            @forelse ($exam->questions as $qIndex => $question)
                @php
                    $correctIndex = $question->choices->search(fn ($choice) => $choice->is_correct);
                    if ($correctIndex === false) {
                        $correctIndex = 0;
                    }
                @endphp
                <div class="rounded-xl border border-gray-200 dark:border-gray-800">
                    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-800 dark:text-white/90">
                                <span class="text-gray-400">{{ $qIndex + 1 }}.</span>
                                {{ $question->question_text }}
                            </div>
                            <div class="mt-2 space-y-1">
                                @foreach ($question->choices as $cIndex => $choice)
                                    <div class="flex items-start gap-2 text-xs {{ $choice->is_correct ? 'font-medium text-success-600 dark:text-success-400' : 'text-gray-600 dark:text-gray-400' }}">
                                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[10px] {{ $choice->is_correct ? 'border-success-500 bg-success-50 dark:bg-success-500/15' : 'border-gray-300 dark:border-gray-700' }}">
                                            {{ $choiceLabels[$cIndex] ?? ($cIndex + 1) }}
                                        </span>
                                        <span>{{ $choice->choice_text }}@if ($choice->is_correct) · เฉลย @endif</span>
                                    </div>
                                @endforeach
                            </div>
                            @if ($question->explanation)
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">คำอธิบาย: {{ $question->explanation }}</p>
                            @endif
                            <p class="mt-1 text-[11px] text-gray-400">{{ number_format((float) $question->points, 1) }} คะแนน</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" @click="editingQuestionId = {{ $question->id }}; showQuestionForm = false"
                                class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-2.5 py-1.5 text-xs font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
                                แก้ไข
                            </button>
                            <form method="POST" action="{{ route('exams.questions.destroy', [$exam, $question]) }}" onsubmit="return confirm('ลบคำถามนี้หรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center rounded-lg border border-error-300 bg-error-50 px-2.5 py-1.5 text-xs font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">
                                    ลบ
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Edit Question Modal --}}
                <div x-show="editingQuestionId === {{ $question->id }}" x-cloak @keydown.escape.window="if (editingQuestionId === {{ $question->id }}) editingQuestionId = null"
                    class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal>
                    <div @click="editingQuestionId = null" class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
                    <div @click.stop class="relative w-full max-w-[700px] rounded-3xl bg-white p-5 dark:bg-gray-900 sm:p-8">
                        <button type="button" @click="editingQuestionId = null"
                            class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white sm:right-6 sm:top-6">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1 1 0 0 0 1.414 1.415L12 13.413l4.543 4.543a1 1 0 0 0 1.414-1.415L13.414 12l4.543-4.543a1 1 0 0 0-1.414-1.414L12 10.586 7.457 6.043A1 1 0 0 0 6.043 7.457L10.586 12l-4.543 4.541Z" fill="currentColor"/></svg>
                        </button>
                        <div class="pr-10">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">แก้ไขคำถาม</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ข้อ {{ $qIndex + 1 }}</p>
                        </div>
                        <form method="POST" action="{{ route('exams.questions.update', [$exam, $question]) }}" class="mt-6 space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">คำถาม</label>
                                <textarea name="question_text" rows="3" required class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ $question->question_text }}</textarea>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">คะแนน</label>
                                    <input type="number" name="points" min="0" step="0.5" value="{{ $question->points }}" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">คำอธิบายเฉลย</label>
                                    <input type="text" name="explanation" value="{{ $question->explanation }}" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-500 dark:text-gray-400">ตัวเลือกคำตอบ (เลือก 1 ข้อเป็นเฉลย)</label>
                                <div class="space-y-2">
                                    @foreach ($choiceLabels as $index => $label)
                                        @php $existing = $question->choices->get($index); @endphp
                                        <div class="flex items-center gap-2">
                                            <label class="inline-flex shrink-0 items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                <input type="radio" name="correct_index" value="{{ $index }}" @checked((int) $correctIndex === $index) class="border-gray-300" />
                                                {{ $label }}
                                            </label>
                                            <input type="text" name="choices[{{ $index }}][choice_text]" value="{{ $existing?->choice_text }}" @required($index < 2) class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                                <button type="button" @click="editingQuestionId = null" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700">ยกเลิก</button>
                                <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">บันทึก</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    ยังไม่มีคำถาม กด “+ เพิ่มคำถาม” เพื่อเริ่มต้น
                </div>
            @endforelse
        </div>
    </div>

    {{-- Create Question Modal --}}
    <div x-show="showQuestionForm" x-cloak @keydown.escape.window="if (showQuestionForm) showQuestionForm = false"
        class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal>
        <div @click="showQuestionForm = false" class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
        <div @click.stop class="relative w-full max-w-[700px] rounded-3xl bg-white p-5 dark:bg-gray-900 sm:p-8">
            <button type="button" @click="showQuestionForm = false"
                class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white sm:right-6 sm:top-6">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1 1 0 0 0 1.414 1.415L12 13.413l4.543 4.543a1 1 0 0 0 1.414-1.415L13.414 12l4.543-4.543a1 1 0 0 0-1.414-1.414L12 10.586 7.457 6.043A1 1 0 0 0 6.043 7.457L10.586 12l-4.543 4.541Z" fill="currentColor"/></svg>
            </button>
            <div class="pr-10">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">เพิ่มคำถาม</h4>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $exam->title }}</p>
            </div>
            <form method="POST" action="{{ route('exams.questions.store', $exam) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">คำถาม</label>
                    <textarea name="question_text" rows="3" required placeholder="พิมพ์คำถาม..." class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">คะแนน</label>
                        <input type="number" name="points" min="0" step="0.5" value="1" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">คำอธิบายเฉลย (ถ้ามี)</label>
                        <input type="text" name="explanation" placeholder="อธิบายทำไมถึงถูก" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-medium text-gray-500 dark:text-gray-400">ตัวเลือกคำตอบ (เลือก 1 ข้อเป็นเฉลย)</label>
                    <div class="space-y-2">
                        @foreach ($choiceLabels as $index => $label)
                            <div class="flex items-center gap-2">
                                <label class="inline-flex shrink-0 items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                                    <input type="radio" name="correct_index" value="{{ $index }}" @checked($index === 0) class="border-gray-300" />
                                    {{ $label }}
                                </label>
                                <input type="text" name="choices[{{ $index }}][choice_text]" @required($index < 2) placeholder="ตัวเลือก {{ $label }}" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="showQuestionForm = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700">ยกเลิก</button>
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">บันทึกคำถาม</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
