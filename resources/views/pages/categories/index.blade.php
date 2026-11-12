@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="ระดับชั้น" />

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

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" x-data="{
            flash: null,
            flashTimer: null,
            saving: false,
            sortable: null,
            showFlash(type, text) {
                this.flash = { type, text };
                clearTimeout(this.flashTimer);
                this.flashTimer = setTimeout(() => this.flash = null, 2500);
            },
            initSortable(el) {
                if (!window.Sortable || this.sortable) return;

                this.sortable = window.Sortable.create(el, {
                    animation: 150,
                    handle: '.category-drag-handle',
                    draggable: '[data-category-id]',
                    ghostClass: 'opacity-50',
                    onEnd: (evt) => {
                        if (evt.oldIndex === evt.newIndex) return;

                        const rows = [...el.querySelectorAll('[data-category-id]')];
                        const order = rows.map((row) => Number(row.dataset.categoryId));
                        this.saving = true;

                        fetch(@js(route('categories.reorder')), {
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
                                return response.json();
                            })
                            .then((data) => {
                                rows.forEach((row) => {
                                    const value = data.sort_orders?.[row.dataset.categoryId];
                                    if (value !== undefined) row.querySelector('[data-sort-order]').textContent = value;
                                });
                                this.showFlash('success', data.message);
                            })
                            .catch(() => {
                                this.showFlash('error', 'เรียงลำดับไม่สำเร็จ กรุณาลองอีกครั้ง');
                                setTimeout(() => window.location.reload(), 1200);
                            })
                            .finally(() => this.saving = false);
                    },
                });
            },
        }">
        <div x-show="flash" x-cloak x-transition class="fixed top-24 right-6 z-99999 rounded-xl border px-4 py-3 text-sm shadow-theme-lg"
            :class="flash?.type === 'error'
                ? 'border-error-200 bg-error-50 text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400'
                : 'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400'"
            x-text="flash?.text"></div>

        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการระดับชั้น</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    จัดการระดับชั้นสำหรับจัดกลุ่มวิชา ชนิดวิชา และหลักสูตร ลากไอคอน <span class="font-semibold">⋮⋮</span> เพื่อเรียงลำดับ
                    <span x-show="saving" x-cloak class="text-brand-500">กำลังบันทึก...</span>
                </p>
            </div>
            <a href="{{ route('categories.create') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                เพิ่มระดับชั้น
            </a>
        </div>

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <form method="GET" action="{{ route('categories.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อหรือ slug"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div class="flex gap-2">
                    <select name="status"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">ทุกสถานะ</option>
                        @foreach (\App\Enums\AccountStatus::options(['active', 'inactive']) as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 text-sm font-medium text-white whitespace-nowrap">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="py-3 pr-5 pl-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ลำดับ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ระดับชั้น</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ชนิดวิชา</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">วิชา</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">หลักสูตร</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800" x-init="initSortable($el)">
                    @forelse ($categories as $category)
                        @php($inUse = $category->subjects_count + $category->courses_count + $category->curriculums_count > 0)
                        <tr data-category-id="{{ $category->id }}" class="bg-white dark:bg-transparent">
                            <td class="py-4 pr-5 pl-4">
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <button type="button" data-no-loading class="category-drag-handle flex h-8 w-8 cursor-grab items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 active:cursor-grabbing dark:hover:bg-white/5 dark:hover:text-gray-300" title="ลากเพื่อเรียงลำดับ" aria-label="ลากเพื่อเรียงลำดับ">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/></svg>
                                    </button>
                                    <span data-sort-order>{{ $category->sort_order }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-800 dark:text-white/90">{{ $category->name }}</div>
                                <div class="mt-0.5 font-mono text-xs text-gray-400">{{ $category->slug }}</div>
                                @if ($category->description)
                                    <div class="mt-0.5 line-clamp-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $category->description }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $category->subjects_count }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $category->courses_count }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $category->curriculums_count }}</td>
                            <td class="px-5 py-4">
                                <x-common.status-badge set="account" :status="$category->status" />
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('categories.edit', $category) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                        แก้ไข
                                    </a>
                                    @if ($inUse)
                                        <span title="มีข้อมูลผูกอยู่ ให้ปิดใช้งานแทน"
                                            class="inline-flex cursor-not-allowed items-center rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-400 dark:border-gray-700 dark:text-gray-500">
                                            ลบ
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                            onsubmit="return confirm('ต้องการลบระดับชั้นนี้หรือไม่?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-error-300 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10">
                                                ลบ
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                ไม่พบระดับชั้น
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
@endsection
