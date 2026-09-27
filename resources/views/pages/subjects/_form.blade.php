@php
    /** @var \App\Models\Subject|null $subject */
    $subject = $subject ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อวิชา<span class="text-error-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $subject?->name) }}" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('name')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="category_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            หมวดหมู่
        </label>
        <select id="category_id" name="category_id"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            <option value="">ไม่ระบุ</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $subject?->category_id) === $category->id)>
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
        <select id="status" name="status" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach (\App\Enums\AccountStatus::options(['active', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $subject?->status ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
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
        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $subject?->description) }}</textarea>
    @error('description')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
    @enderror
</div>
