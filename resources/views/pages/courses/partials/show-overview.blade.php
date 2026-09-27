<div x-show="tab === 'overview'" x-cloak class="space-y-5">
    <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ภาพรวมคอร์ส</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สรุปรายละเอียดและโครงสร้างเนื้อหา</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-gray-50/50 p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $course->chapters_count }}</div>
            <div class="mt-1 text-sm text-gray-500">บทเรียน (Chapters)</div>
        </div>
        <div class="rounded-2xl border border-blue-light-100 bg-blue-light-50/50 p-4 shadow-theme-xs dark:border-blue-light-500/20 dark:bg-blue-light-500/10">
            <div class="text-2xl font-semibold text-blue-light-700 dark:text-blue-light-400">{{ $stats['videos'] }}</div>
            <div class="mt-1 text-sm text-blue-light-600/80 dark:text-blue-light-400/80">วิดีโอ</div>
        </div>
        <div class="rounded-2xl border border-success-100 bg-success-50/50 p-4 shadow-theme-xs dark:border-success-500/20 dark:bg-success-500/10">
            <div class="text-2xl font-semibold text-success-700 dark:text-success-400">{{ $stats['documents'] }}</div>
            <div class="mt-1 text-sm text-success-600/80 dark:text-success-400/80">เอกสาร</div>
        </div>
        <div class="rounded-2xl border border-warning-100 bg-warning-50/50 p-4 shadow-theme-xs dark:border-warning-500/20 dark:bg-warning-500/10">
            <div class="text-2xl font-semibold text-warning-700 dark:text-warning-400">{{ $stats['quizzes'] + $stats['exams'] }}</div>
            <div class="mt-1 text-sm text-warning-600/80 dark:text-warning-400/80">แบบทดสอบทั้งหมด</div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-800">
        <h4 class="font-medium text-gray-800 dark:text-white/90">รายละเอียด</h4>
        <div class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-400">
            {{ $course->description ?: 'ยังไม่มีรายละเอียดเพิ่มเติม' }}
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-800">
        <h4 class="font-medium text-gray-800 dark:text-white/90">โครงสร้างบทเรียน</h4>
        <div class="mt-4 space-y-3">
            @forelse ($course->chapters as $chapter)
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/5">
                    <div>
                        <div class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $chapter->title }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            วิดีโอ {{ $chapter->videos->count() }} · เอกสาร {{ $chapter->documents->count() }}
                        </div>
                    </div>
                    <button type="button" @click="tab = 'chapters'; selectedChapter = {{ $chapter->id }}"
                        class="{{ $btn['view'] }}">
                        จัดการ
                    </button>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบทเรียน เริ่มเพิ่มได้ที่แท็บบทเรียน</p>
            @endforelse
        </div>
    </div>
</div>
