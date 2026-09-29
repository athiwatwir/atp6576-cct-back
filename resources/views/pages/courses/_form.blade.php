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

    <div x-data="{
        open: false,
        instructorId: @js((string) old('instructor_id', $course?->instructor_id ?? '')),
        instructors: @js($instructors->map(fn ($instructor) => [
            'id' => (string) $instructor->id,
            'name' => $instructor->name,
            'image' => $instructor->image_url,
        ])->values()),
        get selected() {
            return this.instructors.find((item) => item.id === this.instructorId) || null;
        },
        choose(id) {
            this.instructorId = id;
            this.open = false;
        },
    }" @click.outside="open = false">
        <label id="instructor_id_label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ผู้สอน
        </label>
        <input type="hidden" name="instructor_id" :value="instructorId" />
        <button type="button" @click="open = !open" aria-labelledby="instructor_id_label"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 flex h-11 w-full items-center gap-2 rounded-lg border border-gray-300 bg-transparent px-3 text-left text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <span class="h-7 w-7 shrink-0 overflow-hidden rounded-full border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <img x-show="selected?.image" x-cloak :src="selected?.image || ''" :alt="selected?.name || ''" class="h-full w-full object-cover" />
                <span x-show="!selected?.image" x-cloak class="flex h-full w-full items-center justify-center text-[9px] text-gray-400">N/A</span>
            </span>
            <span class="min-w-0 flex-1 truncate" x-text="selected?.name || 'ไม่ระบุ'"></span>
        </button>
        <div x-show="open" x-cloak class="relative z-20">
            <div class="absolute mt-1 max-h-60 w-full overflow-auto rounded-lg border border-gray-200 bg-white py-1 shadow-theme-sm dark:border-gray-700 dark:bg-gray-900">
                <button type="button" @click="choose('')" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-200 text-[9px] text-gray-400 dark:border-gray-700">N/A</span>
                    ไม่ระบุ
                </button>
                <template x-for="item in instructors" :key="item.id">
                    <button type="button" @click="choose(item.id)" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-800 hover:bg-gray-50 dark:text-white/90 dark:hover:bg-white/5">
                        <span class="h-7 w-7 shrink-0 overflow-hidden rounded-full border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                            <img x-show="item.image" :src="item.image || ''" :alt="item.name" class="h-full w-full object-cover" />
                            <span x-show="!item.image" class="flex h-full w-full items-center justify-center text-[9px] text-gray-400">N/A</span>
                        </span>
                        <span class="truncate" x-text="item.name"></span>
                    </button>
                </template>
            </div>
        </div>
        @error('instructor_id')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
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
