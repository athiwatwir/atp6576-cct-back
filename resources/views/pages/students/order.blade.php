@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="สร้างออเดอร์" />

    @include('pages.students._steps', ['step' => 2])

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
        x-data="{
            rows: {{ \Illuminate\Support\Js::from(collect(old('items', []))->filter(fn ($row) => filled($row['id'] ?? null))->values()) }},
            catalog: {{ \Illuminate\Support\Js::from($catalog) }},
            typeLabels: { course: 'วิชา', curriculum: 'หลักสูตร', exam: 'ข้อสอบ' },
            typeBadges: {
                course: 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
                curriculum: 'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400',
                exam: 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            },
            picker: { open: false, q: '', type: '', category: '', subject: '', draft: [] },
            get all() {
                return ['course', 'curriculum', 'exam'].flatMap((type) => (this.catalog[type] || []).map((item) => ({ ...item, type, key: type + ':' + item.id })));
            },
            get categories() {
                return this.unique(this.all.filter((item) => !this.picker.type || item.type === this.picker.type).map((item) => item.category));
            },
            get subjects() {
                return this.unique(this.all
                    .filter((item) => item.type === 'course' && (!this.picker.category || String(item.category?.id) === this.picker.category))
                    .map((item) => item.subject));
            },
            get filtered() {
                const q = this.picker.q.trim().toLowerCase();
                return this.all.filter((item) => (!this.picker.type || item.type === this.picker.type)
                    && (!this.picker.category || String(item.category?.id) === this.picker.category)
                    && (!this.picker.subject || String(item.subject?.id) === this.picker.subject)
                    && (!q || [item.name, item.code, item.category?.name, item.subject?.name].filter(Boolean).join(' ').toLowerCase().includes(q)));
            },
            unique(list) {
                const seen = new Map();
                list.filter(Boolean).forEach((entry) => seen.set(String(entry.id), entry));
                return [...seen.values()].sort((a, b) => a.name.localeCompare(b.name, 'th'));
            },
            added(key) {
                return this.rows.some((row) => row.type + ':' + row.id === key);
            },
            openPicker() {
                this.picker.draft = [];
                this.picker.open = true;
                this.$nextTick(() => this.$refs.pickerSearch?.focus());
            },
            toggle(key) {
                if (this.added(key)) return;
                const draft = this.picker.draft;
                this.picker.draft = draft.includes(key) ? draft.filter((value) => value !== key) : [...draft, key];
            },
            confirmPicker() {
                this.picker.draft.forEach((key) => {
                    const entry = this.all.find((item) => item.key === key);
                    if (!entry || this.added(key)) return;
                    this.rows.push({ type: entry.type, id: String(entry.id), plan_id: entry.plans.length === 1 ? String(entry.plans[0].id) : '' });
                });
                this.picker.open = false;
            },
            resetFilters() {
                Object.assign(this.picker, { q: '', type: '', category: '', subject: '' });
            },
            item(row) {
                return (this.catalog[row.type] || []).find((entry) => String(entry.id) === String(row.id)) || null;
            },
            plans(row) {
                return this.item(row)?.plans || [];
            },
            meta(entry) {
                return [entry?.category?.name, entry?.subject?.name, entry?.code].filter(Boolean).join(' · ');
            },
            priceLabel(entry) {
                return (entry.plans.length > 1 ? 'เริ่มต้น ' : '') + this.money(entry.price);
            },
            price(row) {
                const item = this.item(row);
                if (!item) return 0;
                const plan = this.plans(row).find((entry) => String(entry.id) === String(row.plan_id));
                if (plan) return Number(plan.effective_price);
                return this.plans(row).length > 1 ? 0 : Number(item.price);
            },
            total() {
                return this.rows.reduce((sum, row) => sum + this.price(row), 0);
            },
            remove(index) {
                this.rows.splice(index, 1);
            },
            money(value) {
                return '฿' + Number(value).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }"
        @keydown.escape.window="picker.open = false">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">เลือกสินค้าให้ {{ $student->name }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if (! $student->email)
                    นักเรียนคนนี้ไม่มีอีเมล ระบบจะไม่ส่งอีเมลหลังชำระเงิน
                @elseif ($hasPaidOrder)
                    นักเรียนคนนี้เคยซื้อแล้ว อีเมลหลังชำระเงินจะมีเฉพาะผลการซื้อ
                @else
                    นี่คือการซื้อครั้งแรก อีเมลหลังชำระเงินจะมีผลการซื้อและข้อมูลสำหรับเข้าระบบ
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('students.orders.store', $student) }}" class="space-y-5 p-5 sm:p-6">
            @csrf
            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-400">รายการที่เลือก <span class="text-gray-400" x-text="rows.length ? '(' + rows.length + ')' : ''"></span></span>
                    <button type="button" data-no-loading @click="openPicker()"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        เพิ่มรายการ
                    </button>
                </div>

                <div class="divide-y divide-gray-100 rounded-xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                    <button type="button" data-no-loading x-show="!rows.length" @click="openPicker()"
                        class="block w-full px-4 py-8 text-center text-sm text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5">
                        ยังไม่ได้เลือกรายการ กด "เพิ่มรายการ" เพื่อเลือกวิชา หลักสูตร หรือข้อสอบ
                    </button>
                    <template x-for="(row, index) in rows" :key="row.type + ':' + row.id">
                        <div class="grid grid-cols-1 items-center gap-3 px-4 py-3 md:grid-cols-12">
                            <input type="hidden" :name="'items[' + index + '][type]'" :value="row.type">
                            <input type="hidden" :name="'items[' + index + '][id]'" :value="row.id">
                            <div class="min-w-0 md:col-span-6">
                                <div class="flex items-start gap-2">
                                    <span class="mt-0.5 inline-flex shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium" :class="typeBadges[row.type]" x-text="typeLabels[row.type]"></span>
                                    <span class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="item(row)?.name ?? '-'"></span>
                                </div>
                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" x-show="meta(item(row))" x-text="meta(item(row))"></div>
                            </div>
                            <div class="md:col-span-3">
                                <select x-show="plans(row).length > 1" x-model="row.plan_id" :name="'items[' + index + '][plan_id]'" :required="plans(row).length > 1" :disabled="plans(row).length <= 1"
                                    class="dark:bg-dark-900 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">เลือกแพ็กเกจ</option>
                                    <template x-for="plan in plans(row)" :key="'plan-' + plan.id">
                                        <option :value="String(plan.id)" x-text="plan.name + ' · ฿' + Number(plan.effective_price).toLocaleString('th-TH')"></option>
                                    </template>
                                </select>
                                <template x-if="plans(row).length === 1">
                                    <div>
                                        <input type="hidden" :name="'items[' + index + '][plan_id]'" :value="row.plan_id">
                                        <span class="text-sm text-gray-600 dark:text-gray-400" x-text="plans(row)[0].name"></span>
                                    </div>
                                </template>
                            </div>
                            <div class="flex items-center justify-between gap-3 md:col-span-3 md:justify-end">
                                <span class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="money(price(row))"></span>
                                <button type="button" data-no-loading @click="remove(index)" class="rounded-lg p-1.5 text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10" aria-label="นำออก">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <template x-teleport="body">
                <div x-show="picker.open" x-cloak class="fixed inset-0 z-99999 flex items-start justify-center overflow-y-auto p-4 sm:items-center" style="display: none;">
                    <div class="fixed inset-0 bg-gray-900/40" @click="picker.open = false"></div>
                    <div class="relative flex max-h-[88vh] w-full max-w-3xl flex-col rounded-2xl bg-white shadow-xl dark:bg-gray-900" @click.stop>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                            <div>
                                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">เลือกรายการให้ {{ $student->name }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'พบ ' + filtered.length + ' รายการ · เลือกแล้ว ' + picker.draft.length"></p>
                            </div>
                            <button type="button" data-no-loading @click="picker.open = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5" aria-label="ปิด">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                            </button>
                        </div>

                        <div class="space-y-3 border-b border-gray-100 px-5 py-3 dark:border-gray-800">
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="option in [['', 'ทั้งหมด'], ['course', 'วิชา'], ['curriculum', 'หลักสูตร'], ['exam', 'ข้อสอบ']]" :key="'type-' + option[0]">
                                    <button type="button" data-no-loading @click="picker.type = option[0]; picker.category = ''; picker.subject = ''"
                                        class="rounded-full border px-3 py-1 text-xs font-medium"
                                        :class="picker.type === option[0] ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5'"
                                        x-text="option[1] + ' (' + (option[0] ? (catalog[option[0]] || []).length : all.length) + ')'"></button>
                                </template>
                            </div>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div class="relative">
                                    <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                                    <input type="text" x-ref="pickerSearch" x-model="picker.q" placeholder="ค้นหาชื่อหรือรหัส" @keydown.enter.prevent
                                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent pr-3 pl-9 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white/90" />
                                </div>
                                <select x-model="picker.category" @change="picker.subject = ''" :disabled="picker.type === 'exam'"
                                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">ทุกระดับชั้น</option>
                                    <template x-for="category in categories" :key="'cat-' + category.id">
                                        <option :value="String(category.id)" x-text="category.name"></option>
                                    </template>
                                </select>
                                <select x-model="picker.subject" :disabled="picker.type && picker.type !== 'course'"
                                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">ทุกชนิดวิชา</option>
                                    <template x-for="subject in subjects" :key="'sub-' + subject.id">
                                        <option :value="String(subject.id)" x-text="subject.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                            <template x-for="entry in filtered" :key="'opt-' + entry.key">
                                <label class="flex items-start gap-3 rounded-lg px-3 py-2.5"
                                    :class="added(entry.key) ? 'cursor-not-allowed opacity-60' : (picker.draft.includes(entry.key) ? 'cursor-pointer bg-brand-50/60 dark:bg-brand-500/10' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5')">
                                    <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20"
                                        :checked="added(entry.key) || picker.draft.includes(entry.key)" :disabled="added(entry.key)" @change="toggle(entry.key)">
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium" :class="typeBadges[entry.type]" x-text="typeLabels[entry.type]"></span>
                                            <span class="text-sm text-gray-800 dark:text-white/90" x-text="entry.name"></span>
                                            <span x-show="added(entry.key)" class="text-[11px] text-gray-400">เพิ่มแล้ว</span>
                                        </span>
                                        <span x-show="meta(entry)" class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400" x-text="meta(entry)"></span>
                                    </span>
                                    <span class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-300" x-text="priceLabel(entry)"></span>
                                </label>
                            </template>
                            <div x-show="!filtered.length" class="px-3 py-10 text-center text-sm text-gray-500">
                                ไม่พบรายการตามตัวกรอง
                                <button type="button" data-no-loading @click="resetFilters()" class="ml-1 font-medium text-brand-600 hover:underline dark:text-brand-400">ล้างตัวกรอง</button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 border-t border-gray-100 px-5 py-3 dark:border-gray-800">
                            <button type="button" data-no-loading @click="picker.draft = []" x-show="picker.draft.length" class="text-xs font-medium text-error-600 hover:underline dark:text-error-400">ล้างที่เลือก</button>
                            <div class="ml-auto flex gap-2">
                                <button type="button" data-no-loading @click="picker.open = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">ยกเลิก</button>
                                <button type="button" data-no-loading @click="confirmPicker()" :disabled="!picker.draft.length"
                                    class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                                    x-text="'เพิ่ม ' + picker.draft.length + ' รายการ'"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div>
                <label for="coupon_code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รหัสคูปอง</label>
                <input type="text" id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" maxlength="100" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm uppercase dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เว้นว่างได้ ส่วนลดจะถูกคำนวณตอนสร้างออเดอร์ และนับการใช้เมื่อชำระเงินสำเร็จ</p>
                @error('coupon_code')
                    <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หมายเหตุ</label>
                <textarea id="notes" name="notes" rows="3" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('notes') }}</textarea>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    ยอดรวม <span class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="money(total())"></span>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('students.show', $student) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">ยกเลิก</a>
                    <button type="submit" :disabled="!rows.length" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50">ถัดไป: ชำระเงิน</button>
                </div>
            </div>
        </form>
    </div>
@endsection
