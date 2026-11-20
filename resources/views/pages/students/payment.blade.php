@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="ชำระเงิน" />

@include('pages.students._steps', ['step' => 3])

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
    <div class="space-y-6 xl:col-span-7">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ชำระเงิน</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                เลือกโอน/COD เพื่อยืนยันว่าได้รับเงินแล้ว หรือเลือก QR / บัตร / e-Wallet เพื่อเปิดหน้าชำระออนไลน์
                @if (! $student->email)
                — นักเรียนไม่มีอีเมล ระบบจะไม่ส่งอีเมล{{ $hasPaidOrder ? '' : ' หลังชำระเงินให้กด "ดูรหัสผ่าน" ในหน้านักเรียนแล้วแจ้งเบอร์โทรและรหัสผ่านให้นักเรียน' }}
                @elseif ($hasPaidOrder)
                — เมื่อชำระสำเร็จจะส่งอีเมลผลการซื้อให้ {{ $student->email }} โดยไม่แนบข้อมูลเข้าระบบ
                @else
                — เมื่อชำระสำเร็จจะส่งอีเมลผลการซื้อและข้อมูลสำหรับเข้าระบบให้ {{ $student->email }}
                @endif
            </p>
        </div>
        @php
            $selectedMethod = old('payment_method', 'bank_transfer');
            $manualMethods = ['bank_transfer', 'cod'];
            $methodGroups = [
                'manual' => [
                    'title' => 'โอนเงิน / ออฟไลน์',
                    'hint' => 'ยืนยันหลังได้รับเงินหรือเก็บเงินแล้ว (ไม่เปิดหน้าชำระออนไลน์)',
                    'methods' => $manualMethods,
                ],
                'online' => [
                    'title' => 'ชำระออนไลน์',
                    'hint' => 'สร้างลิงก์แล้วเปิดหน้า /pay ให้ชำระด้วย QR หรือบัตร',
                    'methods' => ['thai_qr', 'credit_card'],
                ],
                'wallet' => [
                    'title' => 'e-Wallet',
                    'hint' => 'สร้างลิงก์แล้วเปิดหน้า /pay ให้ชำระผ่านกระเป๋าเงิน',
                    'methods' => ['truemoney', 'shopeepay', 'rabbit_linepay', 'wechat_pay'],
                ],
            ];
            $walletIcons = [
                'truemoney' => 'TM',
                'shopeepay' => 'SP',
                'rabbit_linepay' => 'LP',
                'wechat_pay' => 'WC',
            ];
        @endphp
        <form method="POST" action="{{ route('students.orders.pay', [$student, $order]) }}" class="space-y-5 p-5 sm:p-6" x-data="{ method: @js($selectedMethod), manuals: @js($manualMethods) }">
            @csrf
            <input type="hidden" name="payment_method" :value="method" />

            <div class="space-y-5">
                @foreach ($methodGroups as $groupKey => $group)
                    @php
                        $options = collect($group['methods'])
                            ->filter(fn (string $value) => array_key_exists($value, $paymentMethods))
                            ->mapWithKeys(fn (string $value) => [$value => $paymentMethods[$value]]);
                    @endphp
                    @continue($options->isEmpty())
                    <div>
                        <div class="mb-2">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $group['title'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $group['hint'] }}</p>
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($options as $value => $label)
                                <button type="button" @click="method = '{{ $value }}'"
                                    :class="method === '{{ $value }}' ? 'border-brand-500 ring-2 ring-brand-500/20 bg-brand-50/40 dark:bg-brand-500/10' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'"
                                    class="flex items-center gap-3 rounded-xl border bg-white p-3.5 text-left transition dark:bg-white/[0.02]">
                                    @if ($groupKey === 'wallet')
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-900 text-[11px] font-bold tracking-wide text-white">{{ $walletIcons[$value] ?? 'W' }}</span>
                                    @elseif ($value === 'thai_qr')
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#00427A] text-[10px] font-bold text-white">QR</span>
                                    @elseif ($value === 'credit_card')
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-900 text-[10px] font-bold tracking-wider text-white">CARD</span>
                                    @else
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $value === 'cod' ? 'COD' : 'โอน' }}</span>
                                    @endif
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-800 dark:text-white/90">{{ $label }}</span>
                                        @if ($groupKey !== 'manual')
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">เปิดหน้าชำระเงินออนไลน์</span>
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-3">
                <a href="{{ route('students.show', $student) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">ไว้ทีหลัง</a>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white"
                    x-text="manuals.includes(method) ? 'ยืนยันชำระเงินและส่งอีเมล' : 'สร้างลิงก์และเปิดหน้าชำระเงิน'"></button>
            </div>
        </form>
    </div>

    @if (! in_array($order->payment_status, ['paid', 'refunded'], true) && $order->status !== 'cancelled')
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">ลิงก์ชำระเงินออนไลน์</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สร้างลิงก์แล้วส่งให้นักเรียนชำระเองผ่าน QR บัตร หรือ e-Wallet (หรือเลือกวิธีด้านบนแล้วเปิดหน้าชำระทันที)</p>
        </div>
        <div class="p-5 sm:p-6">
            @include('pages.orders.partials.payment-link', ['order' => $order, 'variant' => 'panel'])
        </div>
    </div>
    @endif
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
