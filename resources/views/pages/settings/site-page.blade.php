@extends('layouts.app')

@section('content')
@php
    /** @var \App\Models\Content|null $page */
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $studentUrl = rtrim((string) (config('services.storefront.app_url') ?: ''), '/');
@endphp

<x-common.page-breadcrumb :pageTitle="$title" />

@if (session('success'))
<div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
@endif

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                แสดงบนเว็บนักเรียนที่ <span class="font-mono">/{{ $pageKey }}</span>
                @if ($page?->updated_at)
                    · แก้ไขล่าสุด {{ $page->updated_at->format('d/m/Y H:i') }}{{ $page->updater?->name ? ' โดย '.$page->updater->name : '' }}
                @endif
            </p>
        </div>
        @if ($studentUrl !== '')
            <a href="{{ $studentUrl }}/{{ $pageKey }}" target="_blank" rel="noopener" class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">ดูหน้าเว็บ</a>
        @endif
    </div>

    <form method="POST" action="{{ route('site-pages.update', $pageKey) }}" class="space-y-6 p-5 sm:p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div class="md:col-span-2">
                <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หัวข้อ<span class="text-error-500">*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title', $page?->title ?? $title) }}" required maxlength="255" class="{{ $inputClass }}" />
                @error('title') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะ<span class="text-error-500">*</span></label>
                <select id="status" name="status" required class="{{ $inputClass }}">
                    <option value="published" @selected(old('status', $page?->status ?? 'published') === 'published')>เผยแพร่</option>
                    <option value="draft" @selected(old('status', $page?->status ?? 'published') === 'draft')>ฉบับร่าง (ยังไม่แสดงบนเว็บ)</option>
                </select>
                @error('status') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="content" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">เนื้อหา</label>
            <textarea id="content" name="content" rows="18" class="article-editor-source dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm leading-6 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('content', $page?->content) }}</textarea>
            <div class="article-editor" data-article-editor data-source="content" data-upload-url="{{ route('articles.images.store') }}" hidden>
                <div class="article-editor-toolbar" role="toolbar" aria-label="เครื่องมือจัดรูปแบบ">
                    <button type="button" data-editor-command="heading-2" title="หัวข้อใหญ่">H2</button>
                    <button type="button" data-editor-command="heading-3" title="หัวข้อรอง">H3</button>
                    <span class="article-editor-divider"></span>
                    <button type="button" data-editor-command="bold" title="ตัวหนา"><strong>B</strong></button>
                    <button type="button" data-editor-command="italic" title="ตัวเอียง"><em>I</em></button>
                    <button type="button" data-editor-command="underline" title="ขีดเส้นใต้"><span class="underline">U</span></button>
                    <span class="article-editor-divider"></span>
                    <button type="button" data-editor-command="bullet" title="รายการจุด">• รายการ</button>
                    <button type="button" data-editor-command="ordered" title="รายการตัวเลข">1. รายการ</button>
                    <button type="button" data-editor-command="quote" title="คำพูด">“ ”</button>
                    <span class="article-editor-divider"></span>
                    <button type="button" data-editor-command="link" title="แทรกลิงก์">ลิงก์</button>
                    <span class="article-editor-divider"></span>
                    <button type="button" data-editor-command="undo" title="ย้อนกลับ">ย้อน</button>
                    <button type="button" data-editor-command="redo" title="ทำซ้ำ">ทำซ้ำ</button>
                </div>
                <div data-editor-root></div>
                <p data-editor-status class="article-editor-status"></p>
            </div>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ใช้หัวข้อ H2/H3 แบ่งเป็นข้อ ๆ เว็บนักเรียนจะสร้างสารบัญจากหัวข้อเหล่านี้ให้</p>
            @error('content') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end border-t border-gray-100 pt-5 dark:border-gray-800">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">บันทึก</button>
        </div>
    </form>
</div>
@endsection
