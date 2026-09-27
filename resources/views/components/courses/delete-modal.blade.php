{{-- Course delete modal: select related data + step progress --}}
<div
    x-data="courseDeleteModal()"
    x-show="open"
    x-cloak
    @keydown.escape.window="if (!running) open = false"
    @open-course-delete-modal.window="openModal($event.detail)"
    class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5"
    data-modal
    style="display: none;"
>
    <div
        @click="if (!running) open = false"
        class="fixed inset-0 h-full w-full bg-gray-900/40"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <div
        @click.stop
        class="relative w-full max-w-xl rounded-3xl bg-white p-6 dark:bg-gray-900 sm:p-8"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="pr-8">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ลบคอร์ส</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="courseName">
                <span x-text="courseName"></span>
                <span class="font-mono text-gray-400" x-show="courseCode" x-text="' · ' + courseCode"></span>
            </p>
        </div>

        <button
            type="button"
            data-no-loading
            @click="if (!running) open = false"
            class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white sm:right-6 sm:top-6"
            :disabled="running"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.043 16.541a1.05 1.05 0 0 0 1.485 1.485L12 13.414l4.542 4.542a1.05 1.05 0 0 0 1.485-1.485L13.414 12l4.543-4.543a1.05 1.05 0 0 0-1.485-1.485L12 10.586 7.458 6.043a1.05 1.05 0 0 0-1.485 1.485L10.586 12 6.043 16.541Z" fill="currentColor"/>
            </svg>
        </button>

        {{-- Loading summary --}}
        <div x-show="phase === 'loading'" class="mt-6 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
            <svg class="h-5 w-5 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            กำลังโหลดข้อมูลที่เกี่ยวข้อง...
        </div>

        {{-- Select related data --}}
        <div x-show="phase === 'select'" class="mt-5">
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">เลือกข้อมูลที่เกี่ยวข้องที่ต้องการลบพร้อมกัน</p>

            <div class="mb-3 flex gap-2">
                <button type="button" data-no-loading @click="selectAll(true)" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">เลือกทั้งหมด</button>
                <span class="text-xs text-gray-300">|</span>
                <button type="button" data-no-loading @click="selectAll(false)" class="text-xs font-medium text-gray-500 hover:underline dark:text-gray-400">ยกเลิกทั้งหมด</button>
            </div>

            <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                <template x-for="item in items" :key="item.key">
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 px-3 py-2.5 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/[0.03]"
                        :class="{
                            'opacity-60': item.count === 0 && item.key !== 'course',
                            'border-error-300 bg-error-50/50 dark:border-error-500/40 dark:bg-error-500/5': item.key === 'enrollments' && item.count > 0 && !item.checked
                        }"
                    >
                        <input
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20"
                            :checked="item.checked"
                            :disabled="item.locked || (item.count === 0 && item.key !== 'course')"
                            @change="item.checked = $event.target.checked"
                        >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="item.label"></span>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="item.count + ' รายการ'"></span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" x-text="item.description"></p>
                            <p class="mt-1 text-xs text-error-600 dark:text-error-400" x-show="item.warning" x-text="item.warning"></p>
                        </div>
                    </label>
                </template>
            </div>

            <p class="mt-3 text-xs text-error-600 dark:text-error-400" x-show="errorMessage" x-text="errorMessage"></p>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" data-no-loading @click="open = false" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    ยกเลิก
                </button>
                <button type="button" data-no-loading @click="startDeletion()" class="inline-flex items-center justify-center rounded-lg bg-error-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-600">
                    ยืนยันการลบ
                </button>
            </div>
        </div>

        {{-- Progress --}}
        <div x-show="phase === 'progress' || phase === 'done' || phase === 'error'" class="mt-5">
            <div class="mb-3">
                <div class="mb-1.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="phase === 'done' ? 'เสร็จสิ้น' : (phase === 'error' ? 'ล้มเหลว' : 'กำลังลบ...')"></span>
                    <span x-text="Math.round(progress) + '%'"></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="phase === 'error' ? 'bg-error-500' : (phase === 'done' ? 'bg-success-500' : 'bg-brand-500')"
                        :style="'width:' + progress + '%'"
                    ></div>
                </div>
            </div>

            <ul class="max-h-72 space-y-2 overflow-y-auto">
                <template x-for="step in progressSteps" :key="step.key">
                    <li class="flex items-start gap-2.5 rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-800">
                        <div class="mt-0.5 shrink-0">
                            <template x-if="step.status === 'pending'">
                                <span class="block h-4 w-4 rounded-full border-2 border-gray-300 dark:border-gray-600"></span>
                            </template>
                            <template x-if="step.status === 'running'">
                                <svg class="h-4 w-4 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                            </template>
                            <template x-if="step.status === 'done'">
                                <svg class="h-4 w-4 text-success-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/>
                                </svg>
                            </template>
                            <template x-if="step.status === 'error'">
                                <svg class="h-4 w-4 text-error-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                                </svg>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="step.label"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" x-show="step.message" x-text="step.message"></div>
                        </div>
                    </li>
                </template>
            </ul>

            <p class="mt-3 text-sm text-error-600 dark:text-error-400" x-show="errorMessage" x-text="errorMessage"></p>

            <div class="mt-6 flex justify-end gap-2" x-show="phase === 'done' || phase === 'error'">
                <button
                    type="button"
                    data-no-loading
                    x-show="phase === 'error'"
                    @click="phase = 'select'; errorMessage = ''; running = false"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    กลับไปเลือกใหม่
                </button>
                <a
                    x-show="phase === 'done'"
                    :href="redirectUrl"
                    data-no-loading
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600"
                >
                    กลับหน้ารายการคอร์ส
                </a>
                <button
                    type="button"
                    data-no-loading
                    x-show="phase === 'error'"
                    @click="open = false"
                    class="inline-flex items-center justify-center rounded-lg bg-error-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-600"
                >
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
window.courseDeleteModal = function courseDeleteModal() {
    const stepOrder = @json(\App\Support\CourseDeletion::stepKeys());
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    return {
        open: false,
        running: false,
        phase: 'loading',
        courseId: null,
        courseName: '',
        courseCode: '',
        items: [],
        progressSteps: [],
        progress: 0,
        errorMessage: '',
        redirectUrl: @json(route('courses.index')),

        init() {
            this.$watch('open', (value) => {
                document.body.style.overflow = value ? 'hidden' : 'unset';
            });
        },

        async openModal(detail) {
            this.courseId = detail.id;
            this.courseName = detail.name || '';
            this.courseCode = detail.code || '';
            this.open = true;
            this.running = false;
            this.phase = 'loading';
            this.items = [];
            this.progressSteps = [];
            this.progress = 0;
            this.errorMessage = '';

            try {
                const res = await fetch(`/courses/${this.courseId}/deletion-summary`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ');
                const data = await res.json();
                this.courseName = data.course?.name || this.courseName;
                this.courseCode = data.course?.code || this.courseCode;
                this.items = (data.items || []).map((item) => ({
                    ...item,
                    checked: item.locked ? true : (item.count > 0 ? item.default : item.key === 'course'),
                }));
                this.phase = 'select';
            } catch (e) {
                this.errorMessage = e.message || 'เกิดข้อผิดพลาด';
                this.phase = 'error';
            }
        },

        selectAll(value) {
            this.items.forEach((item) => {
                if (item.locked) {
                    item.checked = true;
                    return;
                }
                if (item.count === 0 && item.key !== 'course') {
                    item.checked = false;
                    return;
                }
                item.checked = value;
            });
        },

        async startDeletion() {
            this.errorMessage = '';

            const enrollment = this.items.find((i) => i.key === 'enrollments');
            if (enrollment && enrollment.count > 0 && !enrollment.checked) {
                this.errorMessage = 'มีผู้เรียนลงทะเบียนอยู่ กรุณาเลือกลบการลงทะเบียนก่อน';
                return;
            }

            const selected = stepOrder.filter((key) => {
                const item = this.items.find((i) => i.key === key);
                return item && item.checked;
            });

            if (!selected.includes('course')) {
                this.errorMessage = 'ต้องลบตัวคอร์สด้วย';
                return;
            }

            this.running = true;
            this.phase = 'progress';
            this.progress = 0;
            this.progressSteps = selected.map((key) => {
                const item = this.items.find((i) => i.key === key);
                return {
                    key,
                    label: item?.label || key,
                    status: 'pending',
                    message: '',
                };
            });

            for (let i = 0; i < selected.length; i++) {
                const key = selected[i];
                const row = this.progressSteps[i];
                row.status = 'running';
                row.message = 'กำลังดำเนินการ...';

                try {
                    const res = await fetch(`/courses/${this.courseId}/delete-step`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ step: key }),
                    });

                    const data = await res.json();

                    if (!res.ok || data.ok === false) {
                        row.status = 'error';
                        row.message = data.message || 'ลบไม่สำเร็จ';
                        this.errorMessage = row.message;
                        this.phase = 'error';
                        this.running = false;
                        this.progress = ((i + 1) / selected.length) * 100;
                        return;
                    }

                    row.status = 'done';
                    row.message = data.message || 'สำเร็จ';
                    this.progress = ((i + 1) / selected.length) * 100;
                } catch (e) {
                    row.status = 'error';
                    row.message = e.message || 'เกิดข้อผิดพลาด';
                    this.errorMessage = row.message;
                    this.phase = 'error';
                    this.running = false;
                    this.progress = ((i + 1) / selected.length) * 100;
                    return;
                }
            }

            this.progress = 100;
            this.phase = 'done';
            this.running = false;

            setTimeout(() => {
                window.location.href = this.redirectUrl;
            }, 900);
        },
    };
};
</script>
@endpush
@endonce
