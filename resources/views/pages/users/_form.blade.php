@php
    $selectedRoles = collect(old('roles', $user?->roles?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อ<span class="text-error-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $user?->name) }}" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('name')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            อีเมล<span class="text-error-500">*</span>
        </label>
        <input type="email" id="email" name="email" value="{{ old('email', $user?->email) }}" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('email')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            เบอร์โทร
        </label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $user?->phone) }}"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('phone')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $user?->status ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            รหัสผ่าน@if (! $user)<span class="text-error-500">*</span>@endif
        </label>
        <input type="password" id="password" name="password" @if (! $user) required @endif
            autocomplete="new-password"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @if ($user)
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
        @endif
        @error('password')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ยืนยันรหัสผ่าน@if (! $user)<span class="text-error-500">*</span>@endif
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation"
            @if (! $user) required @endif autocomplete="new-password"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
    </div>
</div>

<div>
    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
        บทบาท<span class="text-error-500">*</span>
    </label>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ($roles as $roleItem)
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                <input type="checkbox" name="roles[]" value="{{ $roleItem->id }}"
                    @checked($selectedRoles->contains($roleItem->id))
                    class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $roleItem->display_name }}</span>
            </label>
        @endforeach
    </div>
    @error('roles')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
    @enderror
    @error('roles.*')
        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
    @enderror
</div>
