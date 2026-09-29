@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="แก้ไขผู้ใช้งานระบบ" />

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
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">แก้ไขผู้ใช้งานระบบ</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6 p-5 sm:p-6">
            @csrf
            @method('PUT')
            @include('pages.users._form', ['user' => $user])

            <div class="flex items-center gap-3 border-t border-gray-100 pt-5 dark:border-gray-800">
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                    บันทึกการแก้ไข
                </button>
                <a href="{{ route('users.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">รีเซ็ตรหัสผ่าน</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ระบบจะสร้างรหัสผ่านใหม่ แล้วส่งอีเมลไปที่ {{ $user->email }} รหัสผ่านเดิมจะใช้ไม่ได้อีก</p>
            </div>
            <form method="POST" action="{{ route('users.reset-password', $user) }}"
                onsubmit="return confirm(@js('สร้างรหัสผ่านใหม่แล้วส่งอีเมลไปที่ '.$user->email.' หรือไม่?'))">
                @csrf
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-error-300 px-4 py-2.5 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-500/40 dark:text-error-400 dark:hover:bg-error-500/10">
                    รีเซ็ตรหัสผ่านและส่งอีเมล
                </button>
            </form>
        </div>
    </div>
@endsection
