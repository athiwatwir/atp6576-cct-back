@php
$items = $course->assessments->whereIn('type', $types);
$formFlag = $panel === 'exams' ? 'showExamForm' : 'showQuizForm';
$choiceLabels = ['ก', 'ข', 'ค', 'ง'];
@endphp

<div x-show="tab === '{{ $panel }}'" x-cloak class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">จัดการ{{ $title }} คำถาม ตัวเลือก และเฉลย</p>
        </div>
        <button type="button" @click="{{ $formFlag }} = !{{ $formFlag }}; selectedAssessment = null; showQuestionForm = false; editingQuestionId = null; editingAssessmentId = null" class="{{ $btn['primary'] }}">
            + เพิ่ม{{ $title }}
        </button>
    </div>

    <div x-show="{{ $formFlag }}" x-cloak class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <form method="POST" action="{{ route('courses.assessments.store', $course) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @csrf
            <input type="hidden" name="type" value="{{ $panel === 'exams' ? 'exam' : 'quiz' }}">
            <input type="text" name="title" required placeholder="ชื่อ{{ $title }}" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
            <textarea name="description" rows="2" placeholder="รายละเอียด" class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2"></textarea>
            <select name="chapter_id" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">ไม่ระบุบท</option>
                @foreach ($course->chapters as $chapter)
                <option value="{{ $chapter->id }}">{{ $chapter->title }}</option>
                @endforeach
            </select>
            <select name="status" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="draft">{{ \App\Enums\ContentStatus::Draft->label() }}</option>
                <option value="published">{{ \App\Enums\ContentStatus::Published->label() }}</option>
                <option value="inactive">{{ \App\Enums\ContentStatus::Inactive->label() }}</option>
            </select>
            <input type="number" name="duration_minutes" min="1" placeholder="ระยะเวลา (นาที)" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="{{ $btn['primary'] }}">บันทึก</button>
                <button type="button" @click="{{ $formFlag }} = false" class="{{ $btn['secondary'] }}">ยกเลิก</button>
            </div>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($items as $assessment)
        <div class="rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $assessment->title }}</h4>
                        <x-common.status-badge :status="$assessment->status" />
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $assessment->questions->count() }} คำถาม
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $assessment->duration_minutes ? $assessment->duration_minutes.' นาที' : 'ไม่จำกัดเวลา' }}
                        ·
                        @php
                        $linkedChapter = $assessment->pivot?->chapter_id
                        ? $course->chapters->firstWhere('id', (int) $assessment->pivot->chapter_id)
                        : null;
                        @endphp
                        @if ($linkedChapter)
                        บทเรียน: {{ $linkedChapter->title }}
                        @else
                        ไม่ได้เชื่อมโยงบท
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="editingAssessmentId = editingAssessmentId === {{ $assessment->id }} ? null : {{ $assessment->id }}; showQuestionForm = false; editingQuestionId = null; {{ $formFlag }} = false" :class="editingAssessmentId === {{ $assessment->id }} ? @js($btn['editActive']) : @js($btn['edit'])">แก้ไข</button>
                    <button type="button" @click="selectedAssessment = selectedAssessment === {{ $assessment->id }} ? null : {{ $assessment->id }}; showQuestionForm = false; editingQuestionId = null; editingAssessmentId = null; {{ $formFlag }} = false" :class="selectedAssessment === {{ $assessment->id }} ? @js($btn['viewActive']) : @js($btn['view'])">จัดการคำถาม</button>
                    <form method="POST" action="{{ route('courses.assessments.destroy', [$course, $assessment]) }}" onsubmit="return confirm('ลบรายการนี้และคำถามทั้งหมดหรือไม่?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="{{ $btn['danger'] }}">ลบ</button>
                    </form>
                </div>
            </div>

            {{-- Edit Assessment Modal --}}
            <div x-show="editingAssessmentId === {{ $assessment->id }}" x-cloak @keydown.escape.window="if (editingAssessmentId === {{ $assessment->id }}) editingAssessmentId = null" class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal>
                <div class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
                <div @click.stop class="relative w-full max-w-[700px] rounded-3xl bg-white p-5 dark:bg-gray-900 sm:p-8">
                    <button type="button" @click="editingAssessmentId = null" class="{{ $btn['icon'] }} absolute right-3 top-3 z-10 sm:right-6 sm:top-6">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1 1 0 0 0 1.414 1.415L12 13.413l4.543 4.543a1 1 0 0 0 1.414-1.415L13.414 12l4.543-4.543a1 1 0 0 0-1.414-1.414L12 10.586 7.457 6.043A1 1 0 0 0 6.043 7.457L10.586 12l-4.543 4.541Z" fill="currentColor" />
                        </svg>
                    </button>

                    <div class="pr-10">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">แก้ไข{{ $title }}</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $assessment->title }}</p>
                    </div>

                    <form method="POST" action="{{ route('courses.assessments.update', [$course, $assessment]) }}" class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="type" value="{{ $panel === 'exams' ? 'exam' : 'quiz' }}">
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">ชื่อ{{ $title }}</label>
                            <input type="text" name="title" value="{{ $assessment->title }}" required class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">รายละเอียด</label>
                            <textarea name="description" rows="2" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ $assessment->description }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">บทเรียน</label>
                            <select name="chapter_id" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">ไม่ระบุบท</option>
                                @foreach ($course->chapters as $chapter)
                                <option value="{{ $chapter->id }}" @selected((int) $assessment->pivot->chapter_id === $chapter->id)>{{ $chapter->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</label>
                            <select name="status" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="draft" @selected($assessment->status === 'draft')>{{ \App\Enums\ContentStatus::Draft->label() }}</option>
                                <option value="published" @selected($assessment->status === 'published')>{{ \App\Enums\ContentStatus::Published->label() }}</option>
                                <option value="inactive" @selected($assessment->status === 'inactive')>{{ \App\Enums\ContentStatus::Inactive->label() }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">ระยะเวลา (นาที)</label>
                            <input type="number" name="duration_minutes" min="1" value="{{ $assessment->duration_minutes }}" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end md:col-span-2">
                            <button type="button" @click="editingAssessmentId = null" class="{{ $btn['secondary'] }}">ยกเลิก</button>
                            <button type="submit" class="{{ $btn['primary'] }}">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="selectedAssessment === {{ $assessment->id }}" x-cloak class="space-y-4 p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">เพิ่มคำถามแบบปรนัย พร้อมเลือกเฉลย</p>
                    <button type="button" @click="showQuestionForm = true; editingQuestionId = null" class="{{ $btn['primarySm'] }}">+ เพิ่มคำถาม</button>
                </div>

                <div class="space-y-3">
                    @forelse ($assessment->questions as $qIndex => $question)
                    @php
                    $correctIndex = $question->choices->search(fn ($choice) => $choice->is_correct);
                    if ($correctIndex === false) {
                    $correctIndex = 0;
                    }
                    @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-800">
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
                                <button type="button" @click="editingQuestionId = {{ $question->id }}; showQuestionForm = false" class="{{ $btn['edit'] }}">แก้ไข</button>
                                <form method="POST" action="{{ route('courses.assessments.questions.destroy', [$course, $assessment, $question]) }}" onsubmit="return confirm('ลบคำถามนี้หรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="{{ $btn['danger'] }}">ลบ</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Question Modal --}}
                    <div x-show="editingQuestionId === {{ $question->id }}" x-cloak @keydown.escape.window="if (editingQuestionId === {{ $question->id }}) editingQuestionId = null" class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal>
                        <div class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
                        <div @click.stop class="relative w-full max-w-[700px] rounded-3xl bg-white p-5 dark:bg-gray-900 sm:p-8">
                            <button type="button" @click="editingQuestionId = null" class="{{ $btn['icon'] }} absolute right-3 top-3 z-10 sm:right-6 sm:top-6">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1 1 0 0 0 1.414 1.415L12 13.413l4.543 4.543a1 1 0 0 0 1.414-1.415L13.414 12l4.543-4.543a1 1 0 0 0-1.414-1.414L12 10.586 7.457 6.043A1 1 0 0 0 6.043 7.457L10.586 12l-4.543 4.541Z" fill="currentColor" />
                                </svg>
                            </button>

                            <div class="pr-10">
                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">แก้ไขคำถาม</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $assessment->title }} · ข้อ {{ $qIndex + 1 }}</p>
                            </div>

                            <form method="POST" action="{{ route('courses.assessments.questions.update', [$course, $assessment, $question]) }}" class="mt-6 space-y-4">
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
                                                <input type="radio" name="correct_index" value="{{ $index }}" @checked((int) $correctIndex===$index) class="border-gray-300" />
                                                {{ $label }}
                                            </label>
                                            <input type="text" name="choices[{{ $index }}][choice_text]" value="{{ $existing?->choice_text }}" @required($index < 2) class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                                    <button type="button" @click="editingQuestionId = null" class="{{ $btn['secondary'] }}">ยกเลิก</button>
                                    <button type="submit" class="{{ $btn['primary'] }}">บันทึก</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        ยังไม่มีคำถาม กด “+ เพิ่มคำถาม” เพื่อเริ่มต้น
                    </div>
                    @endforelse
                </div>

                {{-- Create Question Modal --}}
                <div x-show="showQuestionForm && selectedAssessment === {{ $assessment->id }}" x-cloak @keydown.escape.window="if (showQuestionForm && selectedAssessment === {{ $assessment->id }}) showQuestionForm = false" class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal>
                    <div class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
                    <div @click.stop class="relative w-full max-w-[700px] rounded-3xl bg-white p-5 dark:bg-gray-900 sm:p-8">
                        <button type="button" @click="showQuestionForm = false" class="{{ $btn['icon'] }} absolute right-3 top-3 z-10 sm:right-6 sm:top-6">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1 1 0 0 0 1.414 1.415L12 13.413l4.543 4.543a1 1 0 0 0 1.414-1.415L13.414 12l4.543-4.543a1 1 0 0 0-1.414-1.414L12 10.586 7.457 6.043A1 1 0 0 0 6.043 7.457L10.586 12l-4.543 4.541Z" fill="currentColor" />
                            </svg>
                        </button>

                        <div class="pr-10">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">เพิ่มคำถาม</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $assessment->title }}</p>
                        </div>

                        <form method="POST" action="{{ route('courses.assessments.questions.store', [$course, $assessment]) }}" class="mt-6 space-y-4">
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
                                            <input type="radio" name="correct_index" value="{{ $index }}" @checked($index===0) class="border-gray-300" />
                                            {{ $label }}
                                        </label>
                                        <input type="text" name="choices[{{ $index }}][choice_text]" @required($index < 2) placeholder="ตัวเลือก {{ $label }}" class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                                <button type="button" @click="showQuestionForm = false" class="{{ $btn['secondary'] }}">ยกเลิก</button>
                                <button type="submit" class="{{ $btn['primary'] }}">บันทึกคำถาม</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-xl border border-dashed border-gray-300 px-6 py-16 text-center dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มี{{ $title }}</p>
        </div>
        @endforelse
    </div>
</div>
