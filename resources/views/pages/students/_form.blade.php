@php
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อ<span class="text-error-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $student?->name) }}" required class="{{ $inputClass }}" />
        @error('name')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            อีเมล<span class="text-error-500">*</span>
        </label>
        <input type="email" id="email" name="email" value="{{ old('email', $student?->email) }}" required class="{{ $inputClass }}" />
        @error('email')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">เบอร์โทร</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $student?->phone) }}" class="{{ $inputClass }}" />
        @error('phone')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required class="{{ $inputClass }}">
            @foreach (\App\Enums\AccountStatus::options() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $student?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    @if ($student)
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รหัสผ่าน</label>
            <input type="password" id="password" name="password" autocomplete="new-password" class="{{ $inputClass }}" />
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน ระบบจะไม่ส่งอีเมลรหัสผ่านจากการแก้ไขนี้</p>
            @error('password')
                <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ยืนยันรหัสผ่าน</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="{{ $inputClass }}" />
        </div>
    @endif
</div>
