@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="ผู้ใช้งานระบบ" />

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

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการผู้ใช้งานระบบ</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">จัดการบัญชี Admin, Staff และ Instructor</p>
            </div>
            <a href="{{ route('users.create') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                เพิ่มผู้ใช้งาน
            </a>
        </div>

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อ อีเมล หรือเบอร์โทร"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div>
                    <select name="role"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">ทุกบทบาท</option>
                        @foreach ($roles as $roleItem)
                            <option value="{{ $roleItem->name }}" @selected($filters['role'] === $roleItem->name)>
                                {{ $roleItem->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <select name="status"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">ทุกสถานะ</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                        <option value="suspended" @selected($filters['status'] === 'suspended')>Suspended</option>
                    </select>
                    <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 text-sm font-medium text-white whitespace-nowrap">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ผู้ใช้งาน</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">บทบาท</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">เบอร์โทร</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">เข้าสู่ระบบล่าสุด</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->roles as $roleItem)
                                        <span class="bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                                            {{ $roleItem->display_name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $user->phone ?: '-' }}
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusClasses = [
                                        'active' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
                                        'inactive' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
                                        'suspended' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses[$user->status] ?? $statusClasses['inactive'] }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $user->last_login_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $user) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                        แก้ไข
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                            onsubmit="return confirm('ต้องการลบผู้ใช้งานนี้หรือไม่?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-error-300 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10">
                                                ลบ
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                ไม่พบผู้ใช้งานระบบ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
