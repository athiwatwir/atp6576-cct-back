@php
    $items = $course->assessments->whereIn('type', $types);
    $formFlag = $panel === 'exams' ? 'showExamForm' : 'showQuizForm';
@endphp

<div x-show="tab === '{{ $panel }}'" x-cloak class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">จัดการ{{ $title }}ที่ผูกกับคอร์สนี้</p>
        </div>
        <button type="button" @click="{{ $formFlag }} = !{{ $formFlag }}"
            class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
            + เพิ่ม{{ $title }}
        </button>
    </div>

    <div x-show="{{ $formFlag }}" x-cloak class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <form method="POST" action="{{ route('courses.assessments.store', $course) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @csrf
            @if ($panel === 'exams')
                <input type="hidden" name="type" value="exam">
            @endif
            <input type="text" name="title" required placeholder="ชื่อ{{ $title }}"
                class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2" />
            <textarea name="description" rows="2" placeholder="รายละเอียด"
                class="dark:bg-dark-900 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-2"></textarea>
            <select name="chapter_id" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">ไม่ระบุบท</option>
                @foreach ($course->chapters as $chapter)
                    <option value="{{ $chapter->id }}">{{ $chapter->title }}</option>
                @endforeach
            </select>
            <select name="status" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="draft">ร่าง</option>
                <option value="published">เปิดใช้งาน</option>
                <option value="inactive">ปิดใช้งาน</option>
            </select>
            <input type="number" name="duration_minutes" min="1" placeholder="ระยะเวลา (นาที)"
                class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
            @if ($panel === 'quizzes')
                <select name="type" class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="quiz">แบบทดสอบ (Quiz)</option>
                    <option value="exercise">แบบฝึกหัด (Exercise)</option>
                </select>
            @endif
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">บันทึก</button>
                <button type="button" @click="{{ $formFlag }} = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700">ยกเลิก</button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ชื่อ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">ประเภท</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">เวลา</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">สถานะ</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($items as $assessment)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $assessment->title }}</div>
                            <div class="line-clamp-1 text-xs text-gray-500">{{ $assessment->description }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ ['exercise' => 'แบบฝึกหัด', 'quiz' => 'แบบทดสอบ', 'exam' => 'ข้อสอบ'][$assessment->type] ?? $assessment->type }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $assessment->duration_minutes ? $assessment->duration_minutes.' นาที' : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$assessment->status] ?? $statusClasses['draft'] }}">
                                {{ $statusLabels[$assessment->status] ?? $assessment->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('courses.assessments.destroy', [$course, $assessment]) }}" class="flex justify-end"
                                onsubmit="return confirm('ลบรายการนี้หรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-error-300 px-3 py-1.5 text-xs text-error-600">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            ยังไม่มี{{ $title }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
