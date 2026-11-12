@extends('layouts.app')

@section('content')
@php
$inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
$date = fn ($value) => $value ? \App\Support\AccessPeriod::shortDate($value) : '-';
$percent = fn ($value) => $value === null ? '-' : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%';
$tone = fn ($value) => match (true) {
$value === null => ['bar' => 'bg-gray-300 dark:bg-gray-600', 'text' => 'text-gray-400'],
$value >= 100 => ['bar' => 'bg-success-500', 'text' => 'text-success-600 dark:text-success-400'],
$value >= 50 => ['bar' => 'bg-brand-500', 'text' => 'text-brand-600 dark:text-brand-400'],
$value > 0 => ['bar' => 'bg-warning-500', 'text' => 'text-warning-600 dark:text-warning-400'],
default => ['bar' => 'bg-gray-300 dark:bg-gray-600', 'text' => 'text-gray-500 dark:text-gray-400'],
};
$avatarColors = [
'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
'bg-theme-purple-500/10 text-theme-purple-500 dark:bg-theme-purple-500/20',
'bg-blue-light-100 text-blue-light-700 dark:bg-blue-light-500/20 dark:text-blue-light-300',
'bg-theme-pink-500/10 text-theme-pink-500 dark:bg-theme-pink-500/20',
'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300',
];
$avatar = fn (int $seed) => $avatarColors[$seed % count($avatarColors)];
$initial = fn (?string $name) => preg_match('/[\p{L}\p{N}]/u', preg_replace('/\[[^\]]*\]/u', '', (string) $name) ?: (string) $name, $m) ? mb_strtoupper($m[0]) : '?';
$link = fn (array $changes) => route('learning-dashboard', array_filter([...$filters, ...$changes, 'page' => null], fn ($v) => $v !== null && $v !== ''));

$icons = [
'users' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
'book' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />',
'clock' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
'x' => '
<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
'chart' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />',
'trophy' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />',
'academic' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />',
'search' => '
<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />',
'calendar' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />',
'play' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />',
'infinity' => '
<path stroke-linecap="round" stroke-linejoin="round" d="M18.178 8c5.096 0 5.096 8 0 8-5.095 0-7.133-8-12.739-8-4.585 0-4.585 8 0 8 5.606 0 7.644-8 12.74-8z" />',
];
$icon = fn (string $name, string $class = 'h-5 w-5') => '<svg class="'.$class.'" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">'.($icons[$name] ?? '').'</svg>';

$cards = [
['label' => 'นักเรียนทั้งหมด', 'value' => number_format($metrics['students']), 'unit' => 'คน', 'hint' => number_format($metrics['enrollments']).' สิทธิ์เรียน', 'href' => $link(['state' => 'active']).'#students', 'icon' => 'users', 'color' => 'brand'],
['label' => 'ออนไลน์ตอนนี้', 'value' => number_format($online['online']), 'unit' => 'คน', 'hint' => 'กำลังดูวิดีโอ '.number_format($online['studying']).' คน', 'href' => '#online', 'icon' => 'users', 'color' => 'success', 'live' => true],
['label' => 'ใกล้หมดอายุ', 'value' => number_format($metrics['soon_students']), 'unit' => 'คน', 'hint' => number_format($metrics['soon_enrollments']).' สิทธิ์ ภายใน '.$filters['soon'].' วัน', 'href' => $link(['state' => 'soon']).'#students', 'icon' => 'clock', 'color' => 'warning', 'alert' => $metrics['soon_enrollments'] > 0],
['label' => 'หมดอายุแล้ว', 'value' => number_format($metrics['expired_enrollments']), 'unit' => 'สิทธิ์', 'hint' => 'ใน '.$expiredWindow.' วันที่ผ่านมา', 'href' => $link(['state' => 'expired']).'#students', 'icon' => 'x', 'color' => 'error'],
['label' => 'ความคืบหน้าเฉลี่ย', 'value' => $percent($metrics['avg_progress']), 'unit' => '', 'hint' => 'ยังไม่เริ่ม '.number_format($metrics['not_started']).' สิทธิ์', 'href' => $link(['sort' => 'progress_asc']).'#students', 'icon' => 'chart', 'color' => 'theme-purple', 'progress' => $metrics['avg_progress']],
['label' => 'เรียนจบแล้ว', 'value' => number_format($metrics['completed']), 'unit' => 'สิทธิ์', 'hint' => 'ดูวิดีโอครบ 100%', 'href' => $link(['sort' => 'progress_desc']).'#students', 'icon' => 'trophy', 'color' => 'success'],
];
$palette = [
'brand' => ['icon' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400', 'ring' => 'hover:border-brand-300 dark:hover:border-brand-500/40', 'bar' => 'bg-brand-500'],
'blue-light' => ['icon' => 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400', 'ring' => 'hover:border-blue-light-300 dark:hover:border-blue-light-500/40', 'bar' => 'bg-blue-light-500'],
'warning' => ['icon' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-400', 'ring' => 'hover:border-warning-300 dark:hover:border-warning-500/40', 'bar' => 'bg-warning-500'],
'error' => ['icon' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400', 'ring' => 'hover:border-error-300 dark:hover:border-error-500/40', 'bar' => 'bg-error-500'],
'theme-purple' => ['icon' => 'bg-theme-purple-500/10 text-theme-purple-500 dark:bg-theme-purple-500/20', 'ring' => 'hover:border-theme-purple-500/40', 'bar' => 'bg-theme-purple-500'],
'success' => ['icon' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400', 'ring' => 'hover:border-success-300 dark:hover:border-success-500/40', 'bar' => 'bg-success-500'],
];
$stateTabs = [
['key' => 'active', 'label' => 'กำลังเรียน', 'count' => $metrics['enrollments'], 'dot' => 'bg-brand-500'],
['key' => 'soon', 'label' => 'ใกล้หมดอายุ', 'count' => $metrics['soon_enrollments'], 'dot' => 'bg-warning-500'],
['key' => 'expired', 'label' => 'หมดอายุแล้ว', 'count' => $metrics['expired_enrollments'], 'dot' => 'bg-error-500'],
['key' => 'all', 'label' => 'ทั้งหมด', 'count' => $metrics['enrollments'] + $metrics['expired_enrollments'], 'dot' => 'bg-gray-400'],
];
@endphp

<x-common.page-breadcrumb pageTitle="แดชบอร์ดการเรียน" />

<div class="space-y-6"
    x-data="{
        data: @js($online),
        loading: false,
        async refresh() {
            if (document.hidden || this.loading) return;
            this.loading = true;
            try {
                const res = await fetch(@js(route('learning-dashboard.online')), { headers: { Accept: 'application/json' } });
                if (res.ok) this.data = await res.json();
            } finally {
                this.loading = false;
            }
        },
    }"
    x-init="setInterval(() => refresh(), 30000)">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-brand-600 via-brand-500 to-blue-light-500 p-6 text-white shadow-theme-sm">
        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute -bottom-16 right-24 h-32 w-32 rounded-full bg-white/10"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">{!! $icon('academic', 'h-7 w-7') !!}</span>
                <div>
                    <h2 class="text-lg font-semibold">ภาพรวมการเรียนของนักเรียน</h2>
                    <p class="mt-1 max-w-xl text-sm text-white/80">ติดตามนักเรียนรายวิชา สิทธิ์ที่ใกล้หมดอายุ และความคืบหน้า (วิดีโอที่ดูจบ ÷ วิดีโอทั้งหมดของวิชา)</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-white/80">แจ้งเตือนใกล้หมดอายุภายใน</span>
                <div class="flex gap-1 rounded-lg bg-white/15 p-1">
                    @foreach ($soonOptions as $option)
                    <a href="{{ $link(['soon' => $option]) }}" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $filters['soon'] === $option ? 'bg-white text-brand-600 shadow-theme-xs' : 'text-white/90 hover:bg-white/10' }}">{{ $option }} วัน</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach ($cards as $card)
        @php
        $p = $palette[$card['color']];
        @endphp
        <a href="{{ $card['href'] }}" class="group relative flex flex-col rounded-2xl border bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-theme-md dark:bg-white/[0.03] {{ $p['ring'] }} {{ ($card['alert'] ?? false) ? 'border-warning-300 ring-2 ring-warning-100 dark:border-warning-500/40 dark:ring-warning-500/10' : 'border-gray-200 dark:border-gray-800' }}">
            <div class="flex items-start justify-between gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $p['icon'] }}">{!! $icon($card['icon'], 'h-6 w-6') !!}</span>
                @if ($card['live'] ?? false)
                <span class="relative flex h-2.5 w-2.5" x-show="data.online > 0">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-success-500"></span>
                </span>
                @elseif ($card['alert'] ?? false)
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-warning-500"></span>
                </span>
                @endif
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-3xl font-bold text-gray-800 dark:text-white/90" @if ($card['live'] ?? false) x-text="data.online.toLocaleString()" @endif>{{ $card['value'] }}</span>
                @if ($card['unit'])<span class="text-sm text-gray-500 dark:text-gray-400">{{ $card['unit'] }}</span>@endif
            </div>
            @if (array_key_exists('progress', $card))
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div class="h-full rounded-full {{ $p['bar'] }}" style="width: {{ min(100, (float) ($card['progress'] ?? 0)) }}%"></div>
            </div>
            @endif
            <div class="mt-auto pt-3 text-xs text-gray-400" @if ($card['live'] ?? false) x-text="'กำลังดูวิดีโอ ' + data.studying.toLocaleString() + ' คน'" @endif>{{ $card['hint'] }}</div>
        </a>
        @endforeach
    </div>

    <div id="online" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div class="flex items-center gap-3">
                <span class="relative flex h-9 w-9 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400">
                    {!! $icon('users') !!}
                    <span class="absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5" x-show="data.online > 0">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-success-500"></span>
                    </span>
                </span>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ออนไลน์ตอนนี้</h3>
                    <p class="text-xs text-gray-400">นักเรียนที่ใช้งานเว็บภายใน <span x-text="data.minutes"></span> นาทีล่าสุด อัปเดตอัตโนมัติทุก 30 วินาที</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <span>ล่าสุด <span x-text="data.checked_at"></span> น.</span>
                <button type="button" @click="refresh()" :disabled="loading" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 px-3 font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <svg class="h-3.5 w-3.5" :class="loading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    รีเฟรช
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3">
            <div class="space-y-4 border-b border-gray-100 p-5 lg:border-b-0 lg:border-r dark:border-gray-800">
                <div>
                    <div class="mb-2 flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400">
                        <span>กำลังเรียนวิชา</span>
                        <span class="text-brand-600 dark:text-brand-400">ดูวิดีโอ <span x-text="data.studying.toLocaleString()"></span> คน</span>
                    </div>
                    <template x-if="data.courses.length === 0">
                        <p class="rounded-lg bg-gray-50 px-3 py-4 text-center text-xs text-gray-400 dark:bg-white/[0.02]">ยังไม่มีใครกำลังดูวิดีโอ</p>
                    </template>
                    <ul class="space-y-2">
                        <template x-for="course in data.courses" :key="course.id">
                            <li>
                                <a :href="course.url" class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 hover:border-brand-200 hover:bg-brand-50/40 dark:border-gray-800 dark:hover:border-brand-500/30 dark:hover:bg-brand-500/5">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-gray-800 dark:text-white/90" x-text="course.name"></span>
                                        <span class="block text-[11px] text-gray-400" x-show="course.code" x-text="course.code"></span>
                                    </span>
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                        {!! $icon('users', 'h-3.5 w-3.5') !!}<span x-text="course.students"></span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="lg:col-span-2">
                <template x-if="data.students.length === 0">
                    <div class="px-5 py-12 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5">{!! $icon('users', 'h-6 w-6') !!}</span>
                        <p class="mt-3 text-sm text-gray-500">ไม่มีนักเรียนออนไลน์ในขณะนี้</p>
                    </div>
                </template>
                <ul class="max-h-96 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800" x-show="data.students.length > 0">
                    <template x-for="student in data.students" :key="student.id">
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                <template x-if="student.avatar_url">
                                    <img :src="student.avatar_url" alt="" loading="lazy" class="h-9 w-9 rounded-full object-cover" />
                                </template>
                                <span x-show="!student.avatar_url" x-text="(student.name || '?').trim().charAt(0).toUpperCase()"></span>
                                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-success-500 dark:border-gray-900"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a :href="student.url" class="block truncate text-sm font-medium text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400" x-text="student.name || student.email"></a>
                                <template x-if="student.course_id">
                                    <div class="mt-0.5 flex min-w-0 items-center gap-1 text-xs text-brand-600 dark:text-brand-400">
                                        {!! $icon('play', 'h-3 w-3 shrink-0') !!}
                                        <span class="truncate" x-text="student.course_name + (student.video_title ? ' · ' + student.video_title : '')"></span>
                                    </div>
                                </template>
                                <template x-if="!student.course_id">
                                    <div class="mt-0.5 truncate text-xs text-gray-400">ออนไลน์ ยังไม่ได้ดูวิดีโอ</div>
                                </template>
                            </div>
                            <span class="shrink-0 text-[11px] text-gray-400" x-text="student.last_seen_human"></span>
                        </li>
                    </template>
                </ul>
                <p class="border-t border-gray-100 px-5 py-2 text-[11px] text-gray-400 dark:border-gray-800" x-show="data.online > data.students.length">แสดง <span x-text="data.students.length"></span> จาก <span x-text="data.online"></span> คน</p>
            </div>
        </div>
    </div>

    <div id="courses" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400">{!! $icon('book') !!}</span>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายวิชา</h3>
                    <p class="text-xs text-gray-400">นับเฉพาะสิทธิ์ที่ยังใช้งานได้ กดชื่อวิชาเพื่อดูรายชื่อนักเรียน</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-success-500"></span>เรียนจบ</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-500"></span>กำลังเรียน</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>ยังไม่เริ่ม</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/70 dark:bg-white/[0.02]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">วิชา</th>
                        <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-medium text-gray-500">นักเรียน</th>
                        <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-medium text-gray-500">ใกล้หมดอายุ</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">ความคืบหน้าเฉลี่ย</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">สถานะผู้เรียน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($courses as $course)
                    @php
                    $t = $tone($course['avg_progress']);
                    $total = max(1, $course['enrollments']);
                    $inProgress = max(0, $course['enrollments'] - $course['completed'] - $course['not_started']);
                    @endphp
                    <tr class="transition hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-semibold {{ $avatar((int) $course['id'] + 3) }}">{{ $initial($course['name']) }}</span>
                                <div class="min-w-0">
                                    <a href="{{ $link(['course_id' => $course['id'], 'state' => 'active']) }}#students" class="text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400">{{ $course['name'] }}</a>
                                    <div class="mt-0.5 flex items-center gap-1 text-[11px] text-gray-400">
                                        {!! $icon('play', 'h-3 w-3') !!}
                                        <span>{{ $course['code'] ? $course['code'].' · ' : '' }}วิดีโอ {{ number_format($course['videos']) }} รายการ</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex min-w-10 items-center justify-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                {!! $icon('users', 'h-3.5 w-3.5') !!}{{ number_format($course['students']) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if ($course['soon'] > 0)
                            <a href="{{ $link(['course_id' => $course['id'], 'state' => 'soon']) }}#students" class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-700 hover:bg-warning-100 dark:bg-warning-500/15 dark:text-warning-400">
                                {!! $icon('clock', 'h-3.5 w-3.5') !!}{{ number_format($course['soon']) }}
                            </a>
                            @else
                            <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-2.5 w-32 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full {{ $t['bar'] }}" style="width: {{ min(100, (float) ($course['avg_progress'] ?? 0)) }}%"></div>
                                </div>
                                <span class="text-sm font-semibold {{ $t['text'] }}">{{ $percent($course['avg_progress']) }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex h-2.5 w-40 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full bg-success-500" style="width: {{ $course['completed'] / $total * 100 }}%"></div>
                                <div class="h-full bg-brand-500" style="width: {{ $inProgress / $total * 100 }}%"></div>
                                <div class="h-full bg-gray-300 dark:bg-gray-600" style="width: {{ $course['not_started'] / $total * 100 }}%"></div>
                            </div>
                            <div class="mt-1.5 flex gap-3 text-[11px]">
                                <span class="text-success-600 dark:text-success-400">จบ {{ number_format($course['completed']) }}</span>
                                <span class="text-brand-600 dark:text-brand-400">เรียนอยู่ {{ number_format($inProgress) }}</span>
                                <span class="text-gray-400">ยังไม่เริ่ม {{ number_format($course['not_started']) }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5">{!! $icon('book', 'h-6 w-6') !!}</span>
                            <p class="mt-3 text-sm text-gray-500">ยังไม่มีนักเรียนที่มีสิทธิ์เรียน</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="students" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{!! $icon('users') !!}</span>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">นักเรียนรายวิชา</h3>
                    <p class="text-xs text-gray-400">พบ {{ number_format($enrollments->total()) }} รายการ</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($stateTabs as $tab)
                <a href="{{ $link(['state' => $tab['key']]) }}#students" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition {{ $filters['state'] === $tab['key'] ? 'border-brand-300 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/15 dark:text-brand-300' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5' }}">
                    <span class="h-2 w-2 rounded-full {{ $tab['dot'] }}"></span>
                    {{ $tab['label'] }}
                    <span class="rounded-full bg-white px-1.5 text-[10px] font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ number_format($tab['count']) }}</span>
                </a>
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('learning-dashboard') }}#students" class="grid grid-cols-1 gap-3 border-b border-gray-100 bg-gray-50/50 px-5 py-4 md:grid-cols-12 dark:border-gray-800 dark:bg-white/[0.01]">
            <input type="hidden" name="soon" value="{{ $filters['soon'] }}" />
            <input type="hidden" name="state" value="{{ $filters['state'] }}" />
            <div class="relative md:col-span-4">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">{!! $icon('search') !!}</span>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อหรืออีเมลนักเรียน" class="{{ $inputClass }} pl-11 pr-4" />
            </div>
            <div class="relative md:col-span-3">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">{!! $icon('book') !!}</span>
                <select name="course_id" class="{{ $inputClass }} pl-11 pr-4">
                    <option value="">ทุกวิชา</option>
                    @foreach ($courseOptions as $option)
                    <option value="{{ $option->id }}" @selected($filters['course_id']===$option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative md:col-span-3">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">{!! $icon('chart') !!}</span>
                <select name="sort" class="{{ $inputClass }} pl-11 pr-4">
                    <option value="expires" @selected($filters['sort']==='expires' )>หมดอายุก่อน</option>
                    <option value="progress_asc" @selected($filters['sort']==='progress_asc' )>ความคืบหน้าน้อยสุด</option>
                    <option value="progress_desc" @selected($filters['sort']==='progress_desc' )>ความคืบหน้ามากสุด</option>
                    <option value="recent" @selected($filters['sort']==='recent' )>เรียนล่าสุด</option>
                    <option value="name" @selected($filters['sort']==='name' )>ชื่อนักเรียน</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 flex-1 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium text-white">{!! $icon('search', 'h-4 w-4') !!}ค้นหา</button>
                <a href="{{ route('learning-dashboard', ['soon' => $filters['soon']]) }}#students" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-transparent dark:text-gray-300 dark:hover:bg-white/5">ล้าง</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/70 dark:bg-white/[0.02]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">นักเรียน</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">วิชา</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">ความคืบหน้า</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">เรียนล่าสุด</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">ระยะเวลาเรียน</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-medium text-gray-500">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($enrollments as $row)
                    @php
                    $t = $tone($row['progress']);
                    @endphp
                    <tr class="transition hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                @if ($row['student_avatar_url'])
                                <img src="{{ $row['student_avatar_url'] }}" alt="" loading="lazy" class="h-10 w-10 shrink-0 rounded-full object-cover" />
                                @else
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $avatar((int) $row['user_id']) }}">{{ $initial($row['student_name']) }}</span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('students.show', $row['user_id']) }}" class="block truncate text-sm font-medium text-gray-800 hover:text-brand-600 dark:text-white/90 dark:hover:text-brand-400">{{ $row['student_name'] ?: '-' }}</a>
                                    <div class="truncate text-xs text-gray-400">{{ $row['student_email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <a href="{{ route('courses.show', $row['course_id']) }}" class="inline-flex max-w-56 items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                                {!! $icon('book', 'h-3.5 w-3.5 shrink-0 text-gray-400') !!}<span class="truncate">{{ $row['course_name'] }}</span>
                            </a>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="h-2.5 w-28 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full {{ $t['bar'] }}" style="width: {{ min(100, (float) ($row['progress'] ?? 0)) }}%"></div>
                                </div>
                                <span class="w-12 text-sm font-semibold {{ $t['text'] }}">{{ $percent($row['progress']) }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-1 text-[11px] text-gray-400">
                                {!! $icon('play', 'h-3 w-3') !!}
                                {{ $row['total'] > 0 ? 'ดูจบ '.number_format($row['done']).'/'.number_format($row['total']).' วิดีโอ' : 'วิชานี้ยังไม่มีวิดีโอ' }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            @if ($row['last_activity_at'])
                            <div class="text-sm text-gray-700 dark:text-gray-300">{{ $row['last_activity_at']->locale('th')->diffForHumans() }}</div>
                            <div class="text-[11px] text-gray-400">{{ $date($row['last_activity_at']) }}</div>
                            @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-white/5 dark:text-gray-400">ยังไม่เริ่มเรียน</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                {!! $icon('calendar', 'h-3.5 w-3.5') !!}
                                <span>{{ $date($row['started_at']) }}</span>
                                <span class="text-gray-300 dark:text-gray-600">→</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $row['expires_at'] ? $date($row['expires_at']) : 'ไม่มีวันหมดอายุ' }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            @if ($row['expires_at'] === null)
                            <span class="inline-flex items-center gap-1 rounded-full bg-theme-purple-500/10 px-2.5 py-1 text-[11px] font-semibold text-theme-purple-500 dark:bg-theme-purple-500/20">{!! $icon('infinity', 'h-3.5 w-3.5') !!}ตลอดชีพ</span>
                            @elseif ($row['state'] === 'expired')
                            <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-1 text-[11px] font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-400">{!! $icon('x', 'h-3.5 w-3.5') !!}หมดอายุแล้ว</span>
                            @elseif ($row['state'] === 'soon')
                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-1 text-[11px] font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">{!! $icon('clock', 'h-3.5 w-3.5') !!}เหลือ {{ $row['days_left'] }} วัน</span>
                            @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-[11px] font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-400"><span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>เหลือ {{ number_format($row['days_left']) }} วัน</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5">{!! $icon('search', 'h-6 w-6') !!}</span>
                            <p class="mt-3 text-sm text-gray-500">ไม่พบรายการ ลองเปลี่ยนตัวกรอง</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($enrollments->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $enrollments->fragment('students')->links() }}</div>
        @endif
    </div>
</div>
@endsection
