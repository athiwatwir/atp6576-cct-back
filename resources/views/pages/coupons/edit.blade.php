@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="แก้ไขคูปอง" />

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">แก้ไขคูปอง</h3>
        </div>
        <form method="POST" action="{{ route('coupons.update', $coupon) }}" class="space-y-6 p-5 sm:p-6">
            @csrf
            @method('PUT')
            @include('pages.coupons._form')
            <div class="flex items-center gap-3 border-t border-gray-100 pt-5 dark:border-gray-800">
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">บันทึก</button>
                <a href="{{ route('coupons.show', $coupon) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">ยกเลิก</a>
            </div>
        </form>
    </div>
@endsection
