@extends('layouts.app')

@section('content')
@php
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
@endphp

<x-common.page-breadcrumb pageTitle="ตั้งค่าระบบ" />

@if (session('success'))
<div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
    {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('settings.system.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">อีเมลแจ้งเตือน</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                เมื่อมีนักเรียนสมัครสมาชิกใหม่ สั่งซื้อหนังสือ หรือสมัครเรียนจากหน้าร้าน/API
                ระบบจะส่งอีเมลพร้อมรายละเอียด (ลูกค้า รายการ ยอดเงิน) ไปที่รายการนี้
                และแจ้งในกระดิ่งหลังบ้านด้วย
            </p>
        </div>

        <div class="space-y-4 p-5 sm:p-6">
            <div>
                <label for="notification_emails" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    อีเมลรับแจ้งเตือน
                </label>
                <textarea
                    id="notification_emails"
                    name="notification_emails"
                    rows="5"
                    placeholder="one@example.com&#10;two@example.com"
                    class="{{ $inputClass }}">{{ $notificationEmails }}</textarea>
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    ใส่ได้หลายอีเมล คั่นด้วยขึ้นบรรทัดใหม่ จุลภาค หรือช่องว่าง เว้นว่างถ้าไม่ต้องการรับอีเมล
                </p>
                @error('notification_emails')
                    <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 pt-5 dark:border-gray-800">
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                    บันทึก
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
