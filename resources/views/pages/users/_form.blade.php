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
            @foreach (\App\Enums\AccountStatus::options() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $user?->status ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    @unless ($user)
        <p class="text-sm text-gray-500 md:col-span-2 dark:text-gray-400">ระบบจะสร้างรหัสผ่านให้อัตโนมัติ แล้วส่งอีเมลพร้อมอีเมลและรหัสผ่านไปให้ผู้ใช้</p>
    @endunless
</div>

@php
    $roleHelp = [
        'admin' => [
            'title' => 'ผู้ดูแลระบบ',
            'text' => 'ดูแลหลังบ้านได้ทั้งหมด ใช้กับคนที่ต้องจัดการผู้ใช้งาน เนื้อหาคอร์ส การขาย และการตั้งค่าระบบ เป็นบทบาทที่มีสิทธิ์กว้างที่สุด',
        ],
        'staff' => [
            'title' => 'เจ้าหน้าที่',
            'text' => 'ทีมปฏิบัติการประจำวัน ใช้เมื่อคนนี้ต้องสร้างออเดอร์ ตรวจการชำระเงิน เปิดสิทธิ์เรียน และดูแลข้อมูลนักเรียน',
        ],
    ];
@endphp

<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        บทบาท<span class="text-error-500">*</span>
    </label>
    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">เลือกได้มากกว่า 1 บทบาท ถ้าคนนี้ทำหลายหน้าที่</p>
    <div class="grid grid-cols-1 gap-3">
        @foreach ($roles as $roleItem)
            @php
                $help = $roleHelp[$roleItem->name] ?? null;
            @endphp
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                <input type="checkbox" name="roles[]" value="{{ $roleItem->id }}"
                    @checked($selectedRoles->contains($roleItem->id))
                    class="text-brand-500 focus:ring-brand-500/20 mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
                <span>
                    <span class="block text-sm font-medium text-gray-800 dark:text-white/90">{{ $help['title'] ?? $roleItem->display_name }}</span>
                    <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $help['text'] ?? $roleItem->description }}</span>
                </span>
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
