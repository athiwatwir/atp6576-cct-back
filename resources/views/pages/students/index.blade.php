@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="นักเรียน" />

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
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการนักเรียน</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">บัญชีสำหรับเข้าเรียน ค้นหา และดูคอร์สที่ลงทะเบียนไว้</p>
            </div>
            <a href="{{ route('students.create') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                เพิ่มนักเรียน
            </a>
        </div>

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นหาชื่อ อีเมล หรือเบอร์โทร"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div>
                    <select name="status"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">ทุกสถานะ</option>
                        @foreach (\App\Enums\AccountStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-medium text-white">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">นักเรียน</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">เบอร์โทร</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">คอร์ส</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ออเดอร์</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">เข้าสู่ระบบล่าสุด</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($students as $student)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('students.show', $student) }}" class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">{{ $student->name }}</a>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $student->email }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $student->phone ?: '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($student->active_enrollments_count) }} กำลังเรียน
                                <div class="text-xs text-gray-400">ทั้งหมด {{ number_format($student->enrollments_count) }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($student->orders_count) }}</td>
                            <td class="px-5 py-4">
                                <x-common.status-badge set="account" :status="$student->status" />
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $student->last_login_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('students.show', $student) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                        ดู
                                    </a>
                                    <a href="{{ route('students.edit', $student) }}"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                                        แก้ไข
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ไม่พบนักเรียน</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                {{ $students->links() }}
            </div>
        @endif
    </div>
@endsection
