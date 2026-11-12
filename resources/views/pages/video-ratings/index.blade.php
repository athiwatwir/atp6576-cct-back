@extends('layouts.app')

@php
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 disabled:cursor-not-allowed disabled:opacity-50';
    $labelClass = 'mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400';
    $starPath = 'M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.77l-5.2 2.74.99-5.79-4.21-4.1 5.82-.85L10 1.5z';
    $today = now($timezone)->toDateString();
    $presets = [
        'วันนี้' => [$today, $today],
        '7 วัน' => [now($timezone)->subDays(6)->toDateString(), $today],
        '30 วัน' => [now($timezone)->subDays(29)->toDateString(), $today],
        'ทั้งหมด' => ['', ''],
    ];
    $starTone = [
        5 => 'bg-success-500',
        4 => 'bg-success-400',
        3 => 'bg-warning-400',
        2 => 'bg-orange-400',
        1 => 'bg-error-500',
    ];
    $hasFilters = $filters['search'] !== '' || $filters['course_id'] || $filters['rating'] || $filters['comment'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';
    $maxCount = max(1, max($stats['distribution']));
@endphp

@section('content')
<x-common.page-breadcrumb pageTitle="คำแนะนำ/ติชม" />

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center gap-3">
            <span class="bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400 flex h-11 w-11 items-center justify-center rounded-xl">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.14L4 20l.9-4.6A8 8 0 1 1 21 12Z"/></svg>
            </span>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">คำติชมทั้งหมด</p>
                <p class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center gap-3">
            <span class="bg-warning-50 text-warning-500 dark:bg-warning-500/10 dark:text-warning-400 flex h-11 w-11 items-center justify-center rounded-xl">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="{{ $starPath }}"/></svg>
            </span>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">คะแนนเฉลี่ย</p>
                <p class="text-2xl font-semibold text-gray-800 dark:text-white/90">
                    {{ $stats['average'] !== null ? number_format($stats['average'], 2) : '-' }}
                    <span class="text-sm font-normal text-gray-400">/ 5</span>
                </p>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center gap-3">
            <span class="bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/10 dark:text-blue-light-400 flex h-11 w-11 items-center justify-center rounded-xl">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 9h8M8 12h5"/></svg>
            </span>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">มีข้อความแนะนำ</p>
                <p class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['with_comment']) }}</p>
            </div>
        </div>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center gap-3">
            <span class="bg-error-50 text-error-500 dark:bg-error-500/10 dark:text-error-400 flex h-11 w-11 items-center justify-center rounded-xl">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            </span>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">คะแนนต่ำ (1–2 ดาว)</p>
                <p class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($stats['low']) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">คำแนะนำ/ติชม</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">คะแนนและคำแนะนำที่นักเรียนให้กับวิดีโอในแต่ละวิชา นักเรียนแก้ไขได้ จึงแสดงและกรองตามเวลาแก้ไขล่าสุด</p>
    </div>

    <form method="GET" action="{{ route('video-ratings.index') }}" class="space-y-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        @if ($filters['rating'])
            <input type="hidden" name="rating" value="{{ $filters['rating'] }}" />
        @endif

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label class="{{ $labelClass }}" for="filter-search">ค้นหา</label>
                <input id="filter-search" type="text" name="search" value="{{ $filters['search'] }}"
                    placeholder="ชื่อนักเรียน อีเมล เบอร์โทร ชื่อวิดีโอ หรือข้อความ"
                    class="{{ $inputClass }}" />
            </div>
            <div>
                <label class="{{ $labelClass }}" for="filter-course">วิชา</label>
                <select id="filter-course" name="course_id" class="{{ $inputClass }}" onchange="this.form.video_id && (this.form.video_id.value = ''); this.form.submit()">
                    <option value="">ทุกวิชา</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected($filters['course_id'] === $course->id)>{{ $course->code ? $course->code.' · ' : '' }}{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="filter-video">วิดีโอ</label>
                <select id="filter-video" name="video_id" class="{{ $inputClass }}" onchange="this.form.submit()" @disabled(! $filters['course_id'])>
                    <option value="">{{ $filters['course_id'] ? 'ทุกวิดีโอในวิชานี้' : 'เลือกวิชาก่อน' }}</option>
                    @foreach ($videos as $video)
                        <option value="{{ $video->id }}" @selected($filters['video_id'] === $video->id)>{{ $video->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="{{ $labelClass }}" for="filter-comment">ข้อความแนะนำ</label>
                <select id="filter-comment" name="comment" class="{{ $inputClass }}" onchange="this.form.submit()">
                    <option value="">ทั้งหมด</option>
                    <option value="with" @selected($filters['comment'] === 'with')>มีข้อความ</option>
                    <option value="without" @selected($filters['comment'] === 'without')>ให้แค่ดาว</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="filter-from">ตั้งแต่วันที่</label>
                <input id="filter-from" type="date" name="from" value="{{ $filters['from'] }}" max="{{ $today }}" class="{{ $inputClass }}" />
            </div>
            <div>
                <label class="{{ $labelClass }}" for="filter-to">ถึงวันที่</label>
                <input id="filter-to" type="date" name="to" value="{{ $filters['to'] }}" max="{{ $today }}" class="{{ $inputClass }}" />
            </div>
            <div>
                <label class="{{ $labelClass }}" for="filter-sort">เรียงตาม</label>
                <select id="filter-sort" name="sort" class="{{ $inputClass }}" onchange="this.form.submit()">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 flex-1 items-center justify-center rounded-lg px-4 text-sm font-medium text-white">ค้นหา</button>
                <a href="{{ route('video-ratings.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">ล้าง</a>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">ช่วงเวลา:</span>
            @foreach ($presets as $label => [$presetFrom, $presetTo])
                @php
                    $presetActive = $filters['from'] === $presetFrom && $filters['to'] === $presetTo;
                @endphp
                <a href="{{ request()->fullUrlWithQuery(['from' => $presetFrom ?: null, 'to' => $presetTo ?: null, 'page' => null]) }}"
                    class="rounded-full border px-3 py-1 text-xs font-medium {{ $presetActive ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-200 text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:text-gray-300' }}">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">กรองตามคะแนน (กดเพื่อกรอง กดซ้ำเพื่อยกเลิก)</span>
            @if ($hasFilters)
                <a href="{{ route('video-ratings.index') }}" class="text-xs font-medium text-error-600 hover:underline dark:text-error-400">ล้างตัวกรองทั้งหมด</a>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            <a href="{{ request()->fullUrlWithQuery(['rating' => null, 'page' => null]) }}"
                class="flex items-center justify-between rounded-xl border px-3 py-2.5 text-sm {{ $filters['rating'] === null ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'border-gray-200 text-gray-700 hover:border-brand-300 dark:border-gray-700 dark:text-gray-300' }}">
                <span class="font-medium">ทุกคะแนน</span>
                <span class="text-xs">{{ number_format($stats['total']) }}</span>
            </a>
            @foreach ($stats['distribution'] as $star => $count)
                @php
                    $active = $filters['rating'] === $star;
                @endphp
                <a href="{{ request()->fullUrlWithQuery(['rating' => $active ? null : $star, 'page' => null]) }}"
                    class="rounded-xl border px-3 py-2.5 text-sm {{ $active ? 'border-warning-400 bg-warning-50 dark:bg-warning-500/10' : 'border-gray-200 hover:border-warning-300 dark:border-gray-700' }}">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 font-medium text-gray-700 dark:text-gray-200">
                            {{ $star }}
                            <svg class="text-warning-400 h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="{{ $starPath }}"/></svg>
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($count) }}</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-full rounded-full {{ $starTone[$star] }}" style="width: {{ round($count / $maxCount * 100) }}%"></div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-between px-5 pt-4 text-sm text-gray-500 dark:text-gray-400">
        <span>
            @if ($ratings->total() > 0)
                แสดง {{ number_format($ratings->firstItem()) }}–{{ number_format($ratings->lastItem()) }} จาก {{ number_format($ratings->total()) }} รายการ
            @else
                ไม่พบรายการ
            @endif
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-5 py-3 text-left text-xs font-medium whitespace-nowrap text-gray-500">วันที่</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">นักเรียน</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">วิชา / วิดีโอ</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">คะแนน</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">คำแนะนำ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($ratings as $rating)
                    @php
                        $chapter = $rating->video?->chapters->firstWhere('course_id', $rating->course_id);
                        $updatedAt = $rating->updated_at?->copy()->timezone($timezone);
                        $createdAt = $rating->created_at?->copy()->timezone($timezone);
                        $edited = $updatedAt && $createdAt && $updatedAt->ne($createdAt);
                    @endphp
                    <tr class="align-top hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-600 dark:text-gray-400">
                            <div>{{ $updatedAt?->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $updatedAt?->format('H:i') }} น.</div>
                            @if ($edited)
                                <div class="mt-1 text-[11px] text-gray-400" title="ให้คะแนนครั้งแรก {{ $createdAt->format('d/m/Y H:i') }}">แก้ไขแล้ว · ครั้งแรก {{ $createdAt->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm">
                            @if ($rating->user)
                                <a href="{{ route('students.show', $rating->user) }}" class="hover:text-brand-600 dark:hover:text-brand-400 font-medium text-gray-800 dark:text-white/90">{{ $rating->user->name ?: '-' }}</a>
                                <div class="text-xs text-gray-400">{{ $rating->user->email }}</div>
                                @if ($rating->user->phone)
                                    <div class="text-xs text-gray-400">{{ $rating->user->phone }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">ไม่พบผู้ใช้</span>
                            @endif
                        </td>
                        <td class="max-w-xs px-5 py-4 text-sm">
                            @if ($rating->course)
                                <a href="{{ route('courses.show', $rating->course) }}" class="hover:text-brand-600 dark:hover:text-brand-400 font-medium text-gray-800 dark:text-white/90">
                                    {{ $rating->course->code ? $rating->course->code.' · ' : '' }}{{ $rating->course->name }}
                                </a>
                            @endif
                            @if ($chapter)
                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">บท: {{ $chapter->title }}</div>
                            @endif
                            <div class="mt-1 inline-flex max-w-full items-center gap-1.5 rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-white/5 dark:text-gray-300">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v13.72a1 1 0 0 0 1.5.86l11-6.86a1 1 0 0 0 0-1.72l-11-6.86A1 1 0 0 0 8 5.14Z"/></svg>
                                <span class="truncate">{{ $rating->video?->title ?? 'วิดีโอถูกลบ' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-0.5" title="{{ $rating->rating }} / 5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-4 w-4 {{ $i <= $rating->rating ? 'text-warning-400' : 'text-gray-200 dark:text-gray-700' }}" viewBox="0 0 20 20" fill="currentColor"><path d="{{ $starPath }}"/></svg>
                                @endfor
                            </div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $rating->rating }} / 5</div>
                        </td>
                        <td class="min-w-64 px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                            @if ($rating->comment)
                                <p class="break-words whitespace-pre-line">{{ $rating->comment }}</p>
                            @else
                                <span class="text-xs text-gray-400">ไม่มีข้อความ (ให้แค่ดาว)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ $hasFilters ? 'ไม่พบคำติชมตามตัวกรองที่เลือก' : 'ยังไม่มีคำติชมจากนักเรียน' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($ratings->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
            {{ $ratings->links() }}
        </div>
    @endif
</div>
@endsection
