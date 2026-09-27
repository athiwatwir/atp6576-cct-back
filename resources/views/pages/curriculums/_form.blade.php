@php
    /** @var \App\Models\Curriculum|null $curriculum */
    $curriculum = $curriculum ?? null;
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อหลักสูตร<span class="text-error-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $curriculum?->name) }}" required class="{{ $inputClass }}" />
        @error('name')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="category_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หมวดหมู่</label>
        <select id="category_id" name="category_id" class="{{ $inputClass }}">
            <option value="">ไม่ระบุ</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $curriculum?->category_id) === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $curriculum?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ราคาปกติ (บาท)<span class="text-error-500">*</span>
        </label>
        <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price', $curriculum?->price ?? 0) }}" required class="{{ $inputClass }}" />
        @error('price')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sale_price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ราคาลด (บาท)</label>
        <input type="number" id="sale_price" name="sale_price" min="0" step="0.01" value="{{ old('sale_price', $curriculum?->sale_price) }}" class="{{ $inputClass }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ถ้าระบุ ต้องไม่สูงกว่าราคาปกติ</p>
        @error('sale_price')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="short_description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">คำอธิบายสั้น</label>
        <input type="text" id="short_description" name="short_description" maxlength="500" value="{{ old('short_description', $curriculum?->short_description) }}" class="{{ $inputClass }}" />
        @error('short_description')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รายละเอียด</label>
        <textarea id="description" name="description" rows="5"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $curriculum?->description) }}</textarea>
        @error('description')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="thumbnail" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รูปปก</label>
        <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">รองรับ JPG/PNG/WebP/GIF ไม่เกิน 2MB</p>
        @error('thumbnail')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror

        @if ($curriculum?->thumbnail)
            <div class="mt-3 flex items-center gap-3">
                <img src="{{ $curriculum->thumbnail_url }}" alt="{{ $curriculum->name }}" class="h-16 w-24 rounded-xl border border-gray-200 object-cover dark:border-gray-700" />
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="remove_thumbnail" value="1" class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
                    ลบรูปปกปัจจุบัน
                </label>
            </div>
        @endif
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $curriculum?->is_featured)) class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
            แสดงเป็นหลักสูตรแนะนำ
        </label>
    </div>
</div>
