@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="โปรไฟล์" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="space-y-6">
        {{-- Header card --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 overflow-hidden rounded-full border border-gray-200 dark:border-gray-700">
                        <img
                            src="{{ $user->avatar ? asset($user->avatar) : asset('images/user/owner.png') }}"
                            alt="{{ $user->name }}"
                            class="h-full w-full object-cover" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $user->name }}</h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @forelse ($user->roles as $role)
                                <span class="bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ $role->display_name }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400">ยังไม่มีบทบาท</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <div>สถานะ:
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ ucfirst($user->status) }}</span>
                    </div>
                    <div class="mt-1">เข้าสู่ระบบล่าสุด:
                        <span class="font-medium text-gray-800 dark:text-white/90">
                            {{ $user->last_login_at?->format('d/m/Y H:i') ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- Profile form --}}
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ข้อมูลส่วนตัว</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">แก้ไขชื่อ อีเมล และเบอร์โทร</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5 p-5 sm:p-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            ชื่อ<span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('name')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            อีเมล<span class="text-error-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('email')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            เบอร์โทร
                        </label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('phone')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-5 dark:border-gray-800">
                        <button type="submit"
                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                            บันทึกโปรไฟล์
                        </button>
                    </div>
                </form>
            </div>

            {{-- Password reset --}}
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" id="password">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รีเซ็ตรหัสผ่าน</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        เปลี่ยนรหัสผ่านเข้าใช้งานระบบหลังบ้าน ระบบจะส่งอีเมลแจ้งเตือนเมื่อเปลี่ยนสำเร็จ
                    </p>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-5 p-5 sm:p-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            รหัสผ่านปัจจุบัน<span class="text-error-500">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                            autocomplete="current-password"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('current_password')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            รหัสผ่านใหม่<span class="text-error-500">*</span>
                        </label>
                        <input type="password" id="password" name="password" required
                            autocomplete="new-password"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('password')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            ยืนยันรหัสผ่านใหม่<span class="text-error-500">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            autocomplete="new-password"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>

                    <div class="border-t border-gray-100 pt-5 dark:border-gray-800">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg border border-error-300 bg-error-50 px-4 py-2.5 text-sm font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400 dark:hover:bg-error-500/20"
                            onclick="return confirm('ยืนยันการเปลี่ยนรหัสผ่านหรือไม่?')">
                            รีเซ็ตรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
