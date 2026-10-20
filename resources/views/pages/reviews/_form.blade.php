@php
    /** @var \App\Models\Testimonial|null $review */
    $review = $review ?? null;
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ชื่อผู้รีวิว<span class="text-error-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $review?->name) }}" required maxlength="150" class="{{ $inputClass }}" />
        @error('name') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">สถานะ<span class="text-error-500">*</span></label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $review?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="sort_order" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ลำดับ</label>
        <input type="number" id="sort_order" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $review?->sort_order ?? 0) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เลขน้อยแสดงก่อน</p>
        @error('sort_order') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="content" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ข้อความรีวิว<span class="text-error-500">*</span></label>
        <textarea id="content" name="content" rows="5" required maxlength="5000" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('content', $review?->content) }}</textarea>
        @error('content') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หน้าปก</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, WebP หรือ GIF ไม่เกิน 2 MB</p>
        @error('image') <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p> @enderror
        @if ($review?->image_url)
            <img src="{{ $review->image_url }}" alt="" class="mt-3 h-28 w-28 rounded-xl object-cover" />
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image')) class="rounded border-gray-300" />
                ลบรูปปัจจุบัน
            </label>
        @endif
    </div>
</div>
