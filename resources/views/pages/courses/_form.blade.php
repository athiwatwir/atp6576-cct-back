@php
/** @var \App\Models\Course|null $course */
$course = $course ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อคอร์ส<span class="text-error-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $course?->name) }}" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('name')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="subject_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            วิชา
        </label>
        <select id="subject_id" name="subject_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">ไม่ระบุ</option>
            @foreach ($subjects as $subject)
            <option value="{{ $subject->id }}" @selected((int) old('subject_id', $course?->subject_id) === $subject->id)>
                {{ $subject->name }}
            </option>
            @endforeach
        </select>
        @error('subject_id')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="category_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            หมวดหมู่
        </label>
        <select id="category_id" name="category_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">ไม่ระบุ / ตามวิชา</option>
            @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((int) old('category_id', $course?->category_id) === $category->id)>
                {{ $category->name }}
            </option>
            @endforeach
        </select>
        @error('category_id')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="instructor_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ผู้สอน
        </label>
        <select id="instructor_id" name="instructor_id" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">ไม่ระบุ</option>
            @foreach ($instructors as $instructor)
            <option value="{{ $instructor->id }}" @selected((int) old('instructor_id', $course?->instructor_id) === $instructor->id)>
                {{ $instructor->name }}
            </option>
            @endforeach
        </select>
        @error('instructor_id')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach (['draft' => 'ร่าง', 'published' => 'เปิดใช้งาน', 'inactive' => 'ปิดใช้งาน'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $course?->status ?? 'draft') === $value)>
                {{ $label }}
            </option>
            @endforeach
        </select>
        @error('status')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ราคา<span class="text-error-500">*</span>
        </label>
        <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price', $course?->price ?? '0.00') }}" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('price')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sale_price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ราคาลด
        </label>
        <input type="number" id="sale_price" name="sale_price" min="0" step="0.01" value="{{ old('sale_price', $course?->sale_price) }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('sale_price')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="thumbnail" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            รูปปกคอร์ส
        </label>
        <input type="file" id="thumbnail" name="thumbnail" accept="image/*" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">รองรับ JPG/PNG/WebP/GIF ไม่เกิน 2MB — ระบบจะแปลงเป็น WebP อัตโนมัติก่อนอัปโหลด</p>
        @error('thumbnail')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror

        @if ($course?->thumbnail)
            <div class="mt-3 flex items-center gap-3">
            <img src="{{ $course->thumbnail_url }}" alt="{{ $course->name }}" class="h-20 w-28 rounded-lg border border-gray-200 object-cover dark:border-gray-700" />
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="remove_thumbnail" value="1" class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
                ลบรูปปัจจุบัน
            </label>
        </div>
        @endif
    </div>

    <div class="md:col-span-2">
        <label for="short_description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            คำอธิบายสั้น
        </label>
        <textarea id="short_description" name="short_description" rows="2" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('short_description', $course?->short_description) }}</textarea>
        @error('short_description')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            รายละเอียด
        </label>
        <textarea id="description" name="description" rows="5" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $course?->description) }}</textarea>
        @error('description')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2 flex flex-wrap gap-5">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $course?->is_featured))
            class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
            คอร์สแนะนำ
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_trial_available" value="1" @checked(old('is_trial_available', $course?->is_trial_available))
            class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
            เปิดทดลองเรียน
        </label>
    </div>
</div>
