@props([
    'name',
    'id' => null,
    'value' => '',
    'label' => null,
    'hint' => null,
    'rows' => 6,
    'compact' => false,
    'lazy' => false,
    'placeholder' => 'เขียนข้อความที่นี่...',
    'uploadUrl' => null,
    'uploadContext' => 'exam',
    'error' => null,
])

@php
    $fieldId = $id ?? $name;
    $uploadUrl = $uploadUrl ?? route('articles.images.store');
    $error = $error ?? (isset($errors) ? $errors->first($name) : null);
@endphp

<div {{ $attributes->class(['space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="article-editor-source dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm leading-6 text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
    >{{ old($name, $value) }}</textarea>

    <div
        class="article-editor"
        data-article-editor
        data-source="{{ $fieldId }}"
        data-upload-url="{{ $uploadUrl }}"
        data-upload-context="{{ $uploadContext }}"
        data-placeholder="{{ $placeholder }}"
        @if ($lazy) data-editor-lazy @endif
        hidden
    >
        <div class="article-editor-toolbar" role="toolbar" aria-label="เครื่องมือจัดรูปแบบ">
            @unless ($compact)
                <button type="button" data-editor-command="heading-2" title="หัวข้อใหญ่">H2</button>
                <button type="button" data-editor-command="heading-3" title="หัวข้อรอง">H3</button>
                <span class="article-editor-divider"></span>
            @endunless
            <button type="button" data-editor-command="bold" title="ตัวหนา"><strong>B</strong></button>
            <button type="button" data-editor-command="italic" title="ตัวเอียง"><em>I</em></button>
            <button type="button" data-editor-command="underline" title="ขีดเส้นใต้"><span class="underline">U</span></button>
            @unless ($compact)
                <button type="button" data-editor-command="strike" title="ขีดฆ่า"><span class="line-through">S</span></button>
            @endunless
            <span class="article-editor-divider"></span>
            <button type="button" data-editor-command="bullet" title="รายการจุด">• รายการ</button>
            <button type="button" data-editor-command="ordered" title="รายการตัวเลข">1. รายการ</button>
            @unless ($compact)
                <button type="button" data-editor-command="quote" title="คำพูด">“ ”</button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="align-left" title="ชิดซ้าย">ซ้าย</button>
                <button type="button" data-editor-command="align-center" title="กึ่งกลาง">กลาง</button>
                <button type="button" data-editor-command="align-right" title="ชิดขวา">ขวา</button>
            @endunless
            <span class="article-editor-divider"></span>
            <button type="button" data-editor-command="link" title="แทรกลิงก์">ลิงก์</button>
            <button type="button" data-editor-command="image" title="แทรกรูป">รูป</button>
            <span class="article-editor-divider"></span>
            <button type="button" data-editor-command="undo" title="ย้อนกลับ">ย้อน</button>
            <button type="button" data-editor-command="redo" title="ทำซ้ำ">ทำซ้ำ</button>
        </div>
        <div data-editor-root></div>
        <p data-editor-status class="article-editor-status"></p>
    </div>

    @if ($hint)
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-sm text-error-500">{{ $error }}</p>
    @endif
</div>
