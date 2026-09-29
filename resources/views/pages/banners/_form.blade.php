@php
    /** @var \App\Models\Content|null $banner */
    $banner = $banner ?? null;
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ชื่อแบนเนอร์<span class="text-error-500">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title', $banner?->title) }}" required class="{{ $inputClass }}" />
        @error('title') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="placement" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ตำแหน่ง<span class="text-error-500">*</span></label>
        <select id="placement" name="placement" required class="{{ $inputClass }}">
            @foreach (\App\Enums\BannerPlacement::options() as $value => $label)
                <option value="{{ $value }}" @selected(old('placement', $banner?->placement ?? 'home') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('placement') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะ<span class="text-error-500">*</span></label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $banner?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="sort_order" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ลำดับ</label>
        <input type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $banner?->sort_order ?? 0) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เลขน้อยแสดงก่อนในตำแหน่งเดียวกัน</p>
        @error('sort_order') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="link_url" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ลิงก์เมื่อคลิก</label>
        <input type="url" id="link_url" name="link_url" value="{{ old('link_url', $banner?->link_url) }}" placeholder="https://" class="{{ $inputClass }}" />
        @error('link_url') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <x-form.date-picker name="published_at" label="เริ่มแสดง" :enable-time="true" :value="old('published_at', $banner?->published_at)" />
    <x-form.date-picker name="expired_at" label="สิ้นสุดการแสดง" :enable-time="true" :value="old('expired_at', $banner?->expired_at)" />

    <div class="md:col-span-2">
        <label for="excerpt" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">คำอธิบายสั้น</label>
        <textarea id="excerpt" name="excerpt" rows="2" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('excerpt', $banner?->excerpt) }}</textarea>
        @error('excerpt') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รูปแบนเนอร์</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">JPG, PNG หรือ WebP ไม่เกิน 4 MB แนะนำภาพแนวนอน</p>
        @error('image') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
        @if ($banner?->image_url)
            <img src="{{ $banner->image_url }}" alt="" class="mt-3 h-28 w-full max-w-md rounded-xl object-cover" />
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image')) class="rounded border-gray-300" />
                ลบรูปปัจจุบัน
            </label>
        @endif
    </div>
</div>
