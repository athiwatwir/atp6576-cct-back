@props([
    'name',
    'label',
    // list<array{id: int|string, label: string, meta?: string|null, chip?: string|null, badge?: string|null, badgeTone?: 'success'|'gray'}>
    // chip = ข้อความบนป้ายที่เลือกแล้ว (ค่าเริ่มต้น label), badge = ป้ายสีท้ายชื่อในรายการ (ค้นหาได้)
    'items' => [],
    'selected' => [],
    'help' => null,
    'emptyText' => 'ยังไม่ได้เลือก',
    'modalTitle' => null,
])

@php
    $payload = collect($items)->map(fn ($item) => [
        'id' => (string) $item['id'],
        'label' => (string) $item['label'],
        'meta' => isset($item['meta']) && $item['meta'] !== '' ? (string) $item['meta'] : null,
        'chip' => isset($item['chip']) && $item['chip'] !== '' ? (string) $item['chip'] : (string) $item['label'],
        'badge' => isset($item['badge']) && $item['badge'] !== '' ? (string) $item['badge'] : null,
        'badgeClass' => match ($item['badgeTone'] ?? 'success') {
            'gray' => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
            default => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
        },
    ])->values()->all();
    $selectedIds = array_values(array_map('strval', (array) $selected));
@endphp

{{-- เลือกหลายรายการผ่าน modal ส่งค่าเป็น {name}[] ไม่เลือกเลย = ไม่ส่งฟิลด์นี้ --}}
<div {{ $attributes }}
    x-data="{
        open: false,
        q: '',
        items: @js($payload),
        selected: @js($selectedIds),
        draft: [],
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.items;
            return this.items.filter((item) => [item.label, item.meta, item.badge].filter(Boolean).join(' ').toLowerCase().includes(q));
        },
        find(id) {
            return this.items.find((item) => item.id === id);
        },
        openModal() {
            this.draft = [...this.selected];
            this.q = '';
            this.open = true;
            this.$nextTick(() => this.$refs.search?.focus());
        },
        toggle(id) {
            this.draft = this.draft.includes(id) ? this.draft.filter((value) => value !== id) : [...this.draft, id];
        },
        selectFiltered() {
            this.draft = [...new Set([...this.draft, ...this.filtered.map((item) => item.id)])];
        },
        confirm() {
            this.selected = this.items.map((item) => item.id).filter((id) => this.draft.includes(id));
            this.open = false;
        },
        remove(id) {
            this.selected = this.selected.filter((value) => value !== id);
        },
    }"
    @keydown.escape.window="open = false">
    <div class="mb-1 flex items-center justify-between gap-2">
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $label }}</span>
        <span x-show="selected.length" x-cloak class="text-[11px] text-gray-400" x-text="'เลือก ' + selected.length + ' รายการ'"></span>
    </div>

    <template x-for="id in selected" :key="id">
        <input type="hidden" name="{{ $name }}[]" :value="id">
    </template>

    <div class="flex min-h-10 w-full cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-transparent px-2 py-1.5 shadow-theme-xs hover:border-brand-300 dark:border-gray-700 dark:bg-gray-900"
        @click="openModal()" role="button" tabindex="0" @keydown.enter.prevent="openModal()">
        <div class="flex min-w-0 flex-1 flex-wrap gap-1">
            <span x-show="!selected.length" class="px-1 text-sm text-gray-400">{{ $emptyText }}</span>
            <template x-for="id in selected.slice(0, 6)" :key="'chip-' + id">
                <span class="inline-flex max-w-full items-center gap-1 rounded-md bg-brand-50 py-0.5 pr-1 pl-2 text-xs text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                    <span class="truncate" x-text="find(id)?.chip ?? id" :title="[find(id)?.label, find(id)?.meta].filter(Boolean).join(' · ')"></span>
                    <button type="button" data-no-loading @click.stop="remove(id)" class="rounded p-0.5 hover:bg-brand-100 dark:hover:bg-brand-500/25" aria-label="นำออก">
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </span>
            </template>
            <span x-show="selected.length > 6" x-cloak class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="'+' + (selected.length - 6)"></span>
        </div>
        <span class="inline-flex shrink-0 items-center gap-1 rounded-md border border-gray-200 px-2 py-1 text-xs font-medium text-gray-600 dark:border-gray-700 dark:text-gray-300">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            เลือก
        </span>
    </div>

    @if ($help)
        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif
    @error($name)<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
    @error($name.'.*')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-99999 flex items-start justify-center overflow-y-auto p-4 sm:items-center" style="display: none;">
            <div class="fixed inset-0 bg-gray-900/40" @click="open = false"></div>
            <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col rounded-2xl bg-white shadow-xl dark:bg-gray-900" @click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $modalTitle ?? 'เลือก'.$label }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'เลือกแล้ว ' + draft.length + ' จาก ' + items.length + ' รายการ'"></p>
                    </div>
                    <button type="button" data-no-loading @click="open = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5" aria-label="ปิด">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>

                <div class="space-y-2 border-b border-gray-100 px-5 py-3 dark:border-gray-800">
                    <div class="relative">
                        <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input type="text" x-ref="search" x-model="q" placeholder="ค้นหา..." @keydown.enter.prevent
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent pr-3 pl-9 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white/90" />
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <button type="button" data-no-loading @click="selectFiltered()" class="font-medium text-brand-600 hover:underline dark:text-brand-400"
                            x-text="q ? 'เลือกทั้งหมดที่ค้นเจอ (' + filtered.length + ')' : 'เลือกทั้งหมด'"></button>
                        <button type="button" data-no-loading @click="draft = []" x-show="draft.length" class="font-medium text-error-600 hover:underline dark:text-error-400">ล้างที่เลือก</button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                    <template x-for="item in filtered" :key="'opt-' + item.id">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-white/5"
                            :class="draft.includes(item.id) ? 'bg-brand-50/60 dark:bg-brand-500/10' : ''">
                            <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20"
                                :checked="draft.includes(item.id)" @change="toggle(item.id)">
                            <span class="min-w-0">
                                <span class="block text-sm text-gray-800 dark:text-white/90">
                                    <span x-text="item.label"></span>
                                    <span x-show="item.badge" class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 align-middle text-[11px] font-medium" :class="item.badgeClass" x-text="item.badge"></span>
                                </span>
                                <span x-show="item.meta" class="block text-xs text-gray-500 dark:text-gray-400" x-text="item.meta"></span>
                            </span>
                        </label>
                    </template>
                    <p x-show="!filtered.length" class="px-3 py-8 text-center text-sm text-gray-500">ไม่พบรายการ</p>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-3 dark:border-gray-800">
                    <button type="button" data-no-loading @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">ยกเลิก</button>
                    <button type="button" data-no-loading @click="confirm()" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2 text-sm font-medium text-white"
                        x-text="'ตกลง (' + draft.length + ')'"></button>
                </div>
            </div>
        </div>
    </template>
</div>
