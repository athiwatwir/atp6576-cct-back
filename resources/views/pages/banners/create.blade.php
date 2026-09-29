@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="เพิ่มแบนเนอร์" />

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">เพิ่มแบนเนอร์</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">กำหนดรูป ลิงก์ ตำแหน่ง และช่วงเวลาที่แสดงบนเว็บนักเรียน</p>
        </div>
        <form method="POST" action="{{ route('banners.store') }}" enctype="multipart/form-data" class="space-y-6 p-5 sm:p-6">
            @csrf
            @include('pages.banners._form')
            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800">
                <a href="{{ route('banners.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">ยกเลิก</a>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">บันทึกแบนเนอร์</button>
            </div>
        </form>
    </div>
@endsection
