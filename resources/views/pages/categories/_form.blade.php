@php
    /** @var \App\Models\Category|null $category */
    $category = $category ?? null;
    $input = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อระดับชั้น<span class="text-error-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $category?->name) }}" required maxlength="150"
            placeholder="เช่น มัธยมศึกษาตอนปลาย" class="{{ $input }}" />
        @error('name')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="slug" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            Slug
        </label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $category?->slug) }}" maxlength="180"
            placeholder="เช่น upper-secondary" class="{{ $input }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ภาษาอังกฤษตัวเล็ก ตัวเลข และขีดกลาง เว็บนักเรียนใช้ค่านี้ใน URL หรือตัวกรอง เว้นว่างให้ระบบสร้างให้</p>
        @error('slug')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sort_order" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ลำดับ
        </label>
        <input type="number" id="sort_order" name="sort_order" min="0" step="1"
            value="{{ old('sort_order', $category?->sort_order ?? ($nextSortOrder ?? 0)) }}" class="{{ $input }}" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ตัวเลขน้อยแสดงก่อนในรายการเลือก</p>
        @error('sort_order')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required class="{{ $input }}">
            @foreach (\App\Enums\AccountStatus::options(['active', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $category?->status ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ปิดใช้งานแล้วจะไม่แสดงให้เลือกในฟอร์มวิชาและชนิดวิชา ข้อมูลที่ผูกไว้เดิมไม่เปลี่ยน</p>
        @error('status')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div>
    <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        รายละเอียด
    </label>
    <textarea id="description" name="description" rows="4"
        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $category?->description) }}</textarea>
    @error('description')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
    @enderror
</div>
