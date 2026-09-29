@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="ชำระเงิน" />

    @include('pages.students._steps', ['step' => 3])

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-7">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ยืนยันการชำระเงิน</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($hasPaidOrder)
                        ส่งอีเมลผลการซื้อให้ {{ $student->email }} โดยไม่แนบข้อมูลเข้าระบบ
                    @else
                        ส่งอีเมลผลการซื้อและข้อมูลสำหรับเข้าระบบให้ {{ $student->email }}
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('students.orders.pay', [$student, $order]) }}" class="space-y-5 p-5 sm:p-6">
                @csrf
                <div>
                    <label for="payment_method" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">วิธีชำระเงิน</label>
                    <select id="payment_method" name="payment_method" required class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', 'bank_transfer') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('students.show', $student) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">ไว้ทีหลัง</a>
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">ยืนยันชำระเงินและส่งอีเมล</button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-5">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $order->order_no }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $student->name }}</p>
            <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($order->items as $item)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="text-gray-800 dark:text-white/90">{{ $item->item_name }}</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">฿{{ number_format((float) $item->total_price, 2) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm dark:border-gray-800">
                @if ((float) $order->discount_amount > 0)
                    <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                        <span>ส่วนลด{{ $order->coupon ? ' '.$order->coupon->code : '' }}</span>
                        <span>-฿{{ number_format((float) $order->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">ยอดชำระ</span>
                    <span class="text-lg font-semibold text-gray-800 dark:text-white/90">฿{{ number_format((float) $order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
