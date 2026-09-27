@extends('layouts.app')

@php
$tabs = [
'course' => 'คอร์ส',
'chapter' => 'บทเรียน',
'video' => 'วิดีโอ',
'exam' => 'ข้อสอบ',
'exercise' => 'แบบฝึกหัด',
'book' => 'หนังสือ',
];
$activeTab = request('tab', 'course');
if (! array_key_exists($activeTab, $tabs)) {
$activeTab = 'course';
}

@endphp

@section('content')
<x-common.page-breadcrumb pageTitle="{{ $curriculum->name }}" />

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

<div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col gap-4 px-5 py-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex gap-4">
            <div class="h-20 w-28 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                @if ($curriculum->thumbnail_url)
                <img src="{{ $curriculum->thumbnail_url }}" alt="{{ $curriculum->name }}" class="h-full w-full object-cover" />
                @else
                <div class="flex h-full w-full items-center justify-center text-xs text-gray-400">ไม่มีปก</div>
                @endif
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $curriculum->name }}</h3>
                    <x-common.status-badge :status="$curriculum->status" />
                    @if ($curriculum->is_featured)
                    <span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">แนะนำ</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $curriculum->category?->name ?? 'ไม่มีหมวดหมู่' }}</p>
                @if ($curriculum->short_description)
                <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-300">{{ $curriculum->short_description }}</p>
                @endif
                <div class="mt-3 text-sm text-gray-800 dark:text-white/90">
                    @if ($curriculum->sale_price !== null && (float) $curriculum->sale_price > 0)
                    <span class="font-medium text-brand-600 dark:text-brand-400">฿{{ number_format((float) $curriculum->sale_price, 2) }}</span>
                    <span class="ml-2 text-xs text-gray-400 line-through">฿{{ number_format((float) $curriculum->price, 2) }}</span>
                    @else
                    <span class="font-medium">฿{{ number_format((float) $curriculum->price, 2) }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('curriculums.edit', $curriculum) }}" class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-4 py-2.5 text-sm font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
                แก้ไขข้อมูล
            </a>
            <form method="POST" action="{{ route('curriculums.destroy', $curriculum) }}" onsubmit="return confirm('ต้องการลบหลักสูตรนี้หรือไม่?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded-lg border border-error-300 bg-error-50 px-4 py-2.5 text-sm font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">
                    ลบหลักสูตร
                </button>
            </form>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2" x-data="{
                tab: @js($activeTab),
                pickerOpen: false,
                query: '',
                selected: [],
                tabs: @js($tabs),
                catalog: @js($catalog),
                attached: @js($attached),
                catalogUrl: @js(route('curriculums.catalog', $curriculum)),
                attachedUrl: @js(route('curriculums.attached', $curriculum)),
                attachedItems: [],
                attachedLoading: false,
                attachedError: '',
                listRequest: null,
                richTabs: ['course', 'chapter', 'video', 'exam', 'exercise'],
                examType: '',
                items: [],
                filterOptions: { subjects: [], courses: [], statuses: [] },
                pagination: { current_page: 1, last_page: 1, total: 0 },
                loading: false,
                error: '',
                subjectId: '',
                courseId: '',
                status: '',
                searchTimer: null,
                request: null,
                isRich() {
                    return this.richTabs.includes(this.tab);
                },
                needsCourse() {
                    return this.tab === 'chapter' || this.tab === 'video' || this.tab === 'exam' || this.tab === 'exercise';
                },
                init() {
                    this.loadAttached();
                },
                selectTab(next) {
                    if (this.tab === next) {
                        return;
                    }
                    this.tab = next;
                    this.loadAttached();
                },
                async loadAttached() {
                    if (this.listRequest) {
                        this.listRequest.abort();
                    }
                    const request = new AbortController();
                    this.listRequest = request;
                    this.attachedLoading = true;
                    this.attachedError = '';
                    try {
                        const response = await fetch(this.attachedUrl + '?type=' + encodeURIComponent(this.tab), {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: request.signal,
                        });
                        if (!response.ok) {
                            throw new Error('load failed');
                        }
                        const payload = await response.json();
                        this.attachedItems = payload.data || [];
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            return;
                        }
                        this.attachedItems = [];
                        this.attachedError = 'โหลดรายการในหลักสูตรไม่สำเร็จ';
                    } finally {
                        if (this.listRequest === request) {
                            this.attachedLoading = false;
                        }
                    }
                },
                openPicker() {
                    this.query = '';
                    this.selected = [];
                    this.subjectId = '';
                    this.courseId = '';
                    this.status = '';
                    this.examType = '';
                    this.items = [];
                    this.error = '';
                    this.pickerOpen = true;
                    if (this.isRich()) {
                        this.load(1);
                    }
                },
                closePicker() {
                    this.pickerOpen = false;
                },
                toggle(id) {
                    this.selected = this.selected.includes(id)
                        ? this.selected.filter((item) => item !== id)
                        : [...this.selected, id];
                },
                scheduleLoad() {
                    if (this.needsCourse() && !this.courseId) {
                        return;
                    }
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => this.load(1), 300);
                },
                async load(page = 1) {
                    if (!this.isRich()) {
                        return;
                    }
                    if (this.request) {
                        this.request.abort();
                    }
                    const request = new AbortController();
                    this.request = request;
                    this.loading = true;
                    this.error = '';
                    const params = new URLSearchParams({ type: this.tab, page: String(page) });
                    if (this.query.trim() !== '') {
                        params.set('search', this.query.trim());
                    }
                    if (this.subjectId) {
                        params.set('subject_id', this.subjectId);
                    }
                    if (this.courseId) {
                        params.set('course_id', this.courseId);
                    }
                    if (this.status) {
                        params.set('status', this.status);
                    }
                    if (this.tab === 'exam' && this.examType) {
                        params.set('exam_type', this.examType);
                    }
                    try {
                        const response = await fetch(this.catalogUrl + '?' + params.toString(), {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            signal: request.signal,
                        });
                        if (!response.ok) {
                            throw new Error('load failed');
                        }
                        const payload = await response.json();
                        this.items = payload.data || [];
                        this.filterOptions = payload.filters || this.filterOptions;
                        this.pagination = payload.meta || this.pagination;
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            return;
                        }
                        this.items = [];
                        this.error = 'โหลดรายการไม่สำเร็จ';
                    } finally {
                        if (this.request === request) {
                            this.loading = false;
                        }
                    }
                },
                available() {
                    const keyword = this.query.trim().toLowerCase();
                    const taken = this.attached[this.tab] || [];
                    return (this.catalog[this.tab] || []).filter((item) => {
                        if (taken.includes(item.id)) {
                            return false;
                        }
                        if (keyword === '') {
                            return true;
                        }
                        return (item.title + ' ' + (item.meta || '')).toLowerCase().includes(keyword);
                    });
                },
            }">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="flex flex-wrap gap-2">
                    @foreach ($tabs as $key => $label)
                    <button type="button" @click="selectTab('{{ $key }}')" :class="tab === '{{ $key }}' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10'" class="rounded-lg px-3 py-1.5 text-sm font-medium">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <button type="button" @click="openPicker()" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                    + เพิ่ม<span class="ml-1" x-text="tabs[tab]"></span>
                </button>
            </div>

            <p x-show="attachedLoading" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
            <p x-show="!attachedLoading && attachedError" class="px-5 py-10 text-center text-sm text-error-500" x-text="attachedError"></p>
            <p x-show="!attachedLoading && !attachedError && attachedItems.length === 0" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มี<span x-text="tabs[tab]"></span>ในหลักสูตรนี้</p>
            <ul x-show="!attachedLoading && !attachedError && attachedItems.length > 0" class="divide-y divide-gray-100 dark:divide-gray-800">
                <template x-for="item in attachedItems" :key="tab + '-' + item.id">
                    <li class="flex items-center justify-between gap-3 px-5 py-4">
                        <div class="flex min-w-0 gap-3">
                            <div class="h-16 w-24 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                <img x-show="item.thumbnail" :src="item.thumbnail" :alt="item.title" class="h-full w-full object-cover" />
                                <div x-show="!item.thumbnail" class="flex h-full w-full items-center justify-center px-2 text-center text-[10px] text-gray-400">ไม่มีปก</div>
                            </div>
                            <div class="min-w-0">
                                <div class="font-medium text-gray-800 dark:text-white/90" x-text="item.title"></div>
                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" x-show="item.code" x-text="item.code"></div>
                                <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400" x-show="item.description" x-text="item.description"></p>
                                <div class="mt-2 flex flex-wrap gap-1" x-show="item.badges && item.badges.length">
                                    <template x-for="badge in item.badges" :key="badge">
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="badge"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('curriculums.items.destroy', $curriculum) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="type" :value="tab" />
                            <input type="hidden" name="id" :value="item.id" />
                            <button type="submit" class="inline-flex items-center rounded-lg border border-error-300 bg-error-50 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">
                                นำออก
                            </button>
                        </form>
                    </li>
                </template>
            </ul>
        </div>

        <div x-show="pickerOpen" x-cloak @keydown.escape.window="if (pickerOpen) closePicker()" class="modal fixed inset-0 z-99999 flex items-center justify-center p-4" data-modal>
            <div class="absolute inset-0 bg-gray-900/40"></div>
            <div class="relative flex w-full flex-col rounded-2xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900" :class="isRich() ? 'h-[min(92vh,52rem)] max-w-6xl' : 'max-h-[80vh] max-w-lg'">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">เพิ่ม<span x-text="tabs[tab]"></span></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="isRich() && !needsCourse()" x-cloak>ค้นหา กรอง และเลือกได้หลายรายการ รายการที่อยู่ในหลักสูตรแล้วจะไม่แสดง</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="needsCourse()" x-cloak>เลือกคอร์สก่อน แล้วจึงค้นหารายการในคอร์สนั้น</p>
                    <div class="mt-3 grid grid-cols-1 gap-3" :class="tab === 'exam' ? 'md:grid-cols-2 xl:grid-cols-4' : ((tab === 'exercise' || tab === 'course') ? 'md:grid-cols-3' : (isRich() ? 'md:grid-cols-2' : ''))">
                        <select x-show="needsCourse()" x-cloak x-model="courseId" @change="query = ''; load(1)" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">เลือกคอร์ส</option>
                            <template x-for="course in filterOptions.courses" :key="course.id">
                                <option :value="course.id" x-text="course.name"></option>
                            </template>
                        </select>
                        <input type="text" x-model="query" @input="isRich() ? scheduleLoad() : null" :disabled="needsCourse() && !courseId" :placeholder="needsCourse() && !courseId ? 'เลือกคอร์สก่อน' : 'ค้นหาชื่อหรือรายละเอียด'" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                        <select x-show="isRich() && tab === 'course'" x-cloak x-model="subjectId" @change="load(1)" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">ทุกวิชา</option>
                            <template x-for="subject in filterOptions.subjects" :key="subject.id">
                                <option :value="subject.id" x-text="subject.name"></option>
                            </template>
                        </select>
                        <select x-show="tab === 'exam'" x-cloak x-model="examType" @change="load(1)" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">ทุกประเภท</option>
                            <template x-for="item in filterOptions.exam_types || []" :key="item.value">
                                <option :value="item.value" x-text="item.label"></option>
                            </template>
                        </select>
                        <select x-show="isRich() && (tab === 'course' || tab === 'exam' || tab === 'exercise')" x-cloak x-model="status" @change="load(1)" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">ทุกสถานะ</option>
                            <template x-for="item in filterOptions.statuses" :key="item.value">
                                <option :value="item.value" x-text="item.label"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <form method="POST" action="{{ route('curriculums.items.store', $curriculum) }}" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="type" :value="tab" />
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <div x-show="isRich()" x-cloak>
                            <p x-show="loading" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
                            <p x-show="!loading && error" class="px-5 py-10 text-center text-sm text-error-500" x-text="error"></p>
                            <p x-show="!loading && !error && needsCourse() && !courseId" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">เลือกคอร์สก่อน เพื่อแสดงรายการ</p>
                            <p x-show="!loading && !error && !(needsCourse() && !courseId) && items.length === 0" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ไม่มีรายการให้เลือก</p>
                            <div x-show="!loading && !error && items.length > 0" class="grid grid-cols-1 gap-3 p-4 lg:grid-cols-2">
                                <template x-for="item in items" :key="item.id">
                                    <label class="flex cursor-pointer gap-3 rounded-xl border p-3 transition" :class="selected.includes(item.id) ? 'border-brand-400 bg-brand-50 dark:border-brand-500/50 dark:bg-brand-500/10' : 'border-gray-200 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5'">
                                        <input type="checkbox" class="text-brand-500 focus:ring-brand-500/20 mt-1 h-4 w-4 shrink-0 rounded border-gray-300 dark:border-gray-700" :checked="selected.includes(item.id)" @change="toggle(item.id)" />
                                        <div class="h-20 w-28 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                                            <img x-show="item.thumbnail" :src="item.thumbnail" :alt="item.title" class="h-full w-full object-cover" />
                                            <div x-show="!item.thumbnail" class="flex h-full w-full items-center justify-center px-2 text-center text-[10px] text-gray-400">ไม่มีปก</div>
                                        </div>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-gray-800 dark:text-white/90" x-text="item.title"></span>
                                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400" x-show="item.code" x-text="item.code"></span>
                                            <span class="mt-1 line-clamp-2 block text-xs text-gray-500 dark:text-gray-400" x-show="item.description" x-text="item.description"></span>
                                            <span class="mt-2 flex flex-wrap gap-1">
                                                <template x-for="badge in item.badges" :key="badge">
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-white/10 dark:text-gray-300" x-text="badge"></span>
                                                </template>
                                            </span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <div x-show="!isRich()" x-cloak class="px-2 py-2">
                            <template x-if="available().length === 0">
                                <p class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">ไม่มีรายการให้เลือก</p>
                            </template>
                            <template x-for="item in available()" :key="item.id">
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <input type="checkbox" class="text-brand-500 focus:ring-brand-500/20 mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-gray-700" :checked="selected.includes(item.id)" @change="toggle(item.id)" />
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-800 dark:text-white/90" x-text="item.title"></span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400" x-show="item.meta" x-text="item.meta"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span>เลือก <span x-text="selected.length"></span> รายการ</span>
                            <div class="flex items-center gap-2" x-show="isRich() && pagination.last_page > 1" x-cloak>
                                <button type="button" @click="load(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs disabled:opacity-40 dark:border-gray-700">ก่อนหน้า</button>
                                <span x-text="pagination.current_page + ' / ' + pagination.last_page"></span>
                                <button type="button" @click="load(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs disabled:opacity-40 dark:border-gray-700">ถัดไป</button>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="closePicker()" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                ยกเลิก
                            </button>
                            <button type="submit" :disabled="selected.length === 0" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50">
                                เพิ่มที่เลือก
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <x-common.activity-logs :entity="$curriculum" :limit="8" title="ประวัติหลักสูตร" />
    </div>
</div>
@endsection
