@php
    /** @var \App\Models\Content|null $article */
    $article = $article ?? null;
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หัวข้อ<span class="text-error-500">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title', $article?->title) }}" required class="{{ $inputClass }}" />
        @error('title') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะ<span class="text-error-500">*</span></label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $article?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="hidden md:block"></div>

    <x-form.date-picker name="published_at" label="เริ่มเผยแพร่" :enable-time="true" :value="old('published_at', $article?->published_at)" />
    <x-form.date-picker name="expired_at" label="สิ้นสุดการแสดง" :enable-time="true" :value="old('expired_at', $article?->expired_at)" />

    <div class="md:col-span-2">
        <label for="excerpt" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">คำเกริ่น</label>
        <textarea id="excerpt" name="excerpt" rows="3" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('excerpt', $article?->excerpt) }}</textarea>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">แสดงในการ์ดรายการบทความบนเว็บนักเรียน</p>
        @error('excerpt') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="content" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">เนื้อหา</label>
        <textarea id="content" name="content" rows="14" class="article-editor-source dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm leading-6 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('content', $article?->content) }}</textarea>
        <div class="article-editor" data-article-editor data-source="content" data-upload-url="{{ route('articles.images.store') }}" hidden>
            <div class="article-editor-toolbar" role="toolbar" aria-label="เครื่องมือจัดรูปแบบ">
                <button type="button" data-editor-command="heading-2" title="หัวข้อใหญ่">H2</button>
                <button type="button" data-editor-command="heading-3" title="หัวข้อรอง">H3</button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="bold" title="ตัวหนา"><strong>B</strong></button>
                <button type="button" data-editor-command="italic" title="ตัวเอียง"><em>I</em></button>
                <button type="button" data-editor-command="underline" title="ขีดเส้นใต้"><span class="underline">U</span></button>
                <button type="button" data-editor-command="strike" title="ขีดฆ่า"><span class="line-through">S</span></button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="bullet" title="รายการจุด">• รายการ</button>
                <button type="button" data-editor-command="ordered" title="รายการตัวเลข">1. รายการ</button>
                <button type="button" data-editor-command="quote" title="คำพูด">“ ”</button>
                <button type="button" data-editor-command="code" title="โค้ด">{ }</button>
                <span class="article-editor-divider"></span>
                <button type="button" data-editor-command="align-left" title="ชิดซ้าย">ซ้าย</button>
                <button type="button" data-editor-command="align-center" title="กึ่งกลาง">กลาง</button>
                <button type="button" data-editor-command="align-right" title="ชิดขวา">ขวา</button>
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
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">จัดหัวข้อ รายการ ลิงก์ และแทรกรูปในเนื้อหาได้</p>
        @error('content') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รูปปก</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">JPG, PNG หรือ WebP ไม่เกิน 4 MB</p>
        @error('image') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
        @if ($article?->image_url)
            <img src="{{ $article->image_url }}" alt="" class="mt-3 h-32 w-48 rounded-xl object-cover" />
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image')) class="rounded border-gray-300" />
                ลบรูปปัจจุบัน
            </label>
        @endif
    </div>
</div>
