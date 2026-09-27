@php
    /** @var \App\Models\Assessment|null $exam */
    $exam = $exam ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="title" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อข้อสอบ<span class="text-error-500">*</span>
        </label>
        <input type="text" id="title" name="title" value="{{ old('title', $exam?->title) }}" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('title')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'published', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $exam?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ราคาขาย (บาท)<span class="text-error-500">*</span>
        </label>
        <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price', $exam?->price ?? 0) }}" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('price')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="duration_minutes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ระยะเวลา (นาที)
        </label>
        <input type="number" id="duration_minutes" name="duration_minutes" min="1" value="{{ old('duration_minutes', $exam?->duration_minutes) }}"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('duration_minutes')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="passing_score" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            คะแนนผ่าน (%)
        </label>
        <input type="number" id="passing_score" name="passing_score" min="0" max="100" step="0.01" value="{{ old('passing_score', $exam?->passing_score) }}"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('passing_score')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="max_attempts" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            จำนวนครั้งที่ทำได้
        </label>
        <input type="number" id="max_attempts" name="max_attempts" min="1" value="{{ old('max_attempts', $exam?->max_attempts) }}"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ว่าง = ไม่จำกัด</p>
        @error('max_attempts')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="thumbnail" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            หน้าปกข้อสอบ
        </label>
        <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            รองรับ JPG/PNG/WebP/GIF ไม่เกิน 2MB — ระบบจะแปลงเป็น WebP และเก็บที่
            <code class="rounded bg-gray-100 px-1 dark:bg-white/10">exams/{id}/{id}.webp</code>
        </p>
        @error('thumbnail')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror

        @if ($exam?->thumbnail)
            <div class="mt-3 flex items-center gap-3">
                <img src="{{ $exam->thumbnail_url }}" alt="{{ $exam->title }}" class="h-20 w-32 rounded-xl border border-gray-200 object-cover dark:border-gray-700" />
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="remove_thumbnail" value="1" class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
                    ลบหน้าปกปัจจุบัน
                </label>
            </div>
        @endif
    </div>

    <div class="md:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            รายละเอียด
        </label>
        <textarea id="description" name="description" rows="4"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $exam?->description) }}</textarea>
        @error('description')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<p class="mt-4 rounded-xl border border-blue-light-200 bg-blue-light-50 px-4 py-3 text-sm text-blue-light-700 dark:border-blue-light-500/30 dark:bg-blue-light-500/10 dark:text-blue-light-400">
    ข้อสอบชุดนี้เป็นรายการขายแยก ไม่ผูกกับคอร์สเรียน (`is_independent`)
</p>
