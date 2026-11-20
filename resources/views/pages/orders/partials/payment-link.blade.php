{{-- ต้องส่ง $order; optional $variant = card|panel|button --}}
@php
    $variant = $variant ?? 'card';
    $unpaid = ! in_array($order->payment_status, ['paid', 'refunded'], true)
        && $order->status !== 'cancelled';
    $hasAmount = (float) $order->total_amount > 0;
    $canCreateLink = $unpaid && $hasAmount;
@endphp

@if ($unpaid)
    @if ($canCreateLink && session('payment_link') && $variant === 'button')
        <div x-data="{ copied: false }" class="flex flex-wrap gap-2">
            <button type="button" data-no-loading
                @click="navigator.clipboard.writeText(@js(session('payment_link'))); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex items-center justify-center rounded-lg border border-brand-300 bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-400">
                <span x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์ชำระเงิน'"></span>
            </button>
            <a href="{{ session('payment_link') }}" target="_blank" rel="noopener"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">เปิดหน้าชำระ</a>
        </div>
    @elseif ($canCreateLink && session('payment_link'))
        <div x-data="{ copied: false }" class="space-y-2">
            <p class="text-xs text-gray-500 dark:text-gray-400">ลิงก์ชำระเงิน (QR พร้อมเพย์ / บัตร / e-Wallet)</p>
            <input type="text" readonly value="{{ session('payment_link') }}" x-ref="link" @focus="$el.select()"
                class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 font-mono text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
            <div class="flex flex-wrap gap-2">
                <button type="button" data-no-loading @click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    <span x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์'"></span>
                </button>
                <a href="{{ session('payment_link') }}" target="_blank" rel="noopener"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">เปิดหน้าชำระ</a>
            </div>
        </div>
    @elseif ($canCreateLink)
        <form method="POST" action="{{ route('orders.payment-link', $order) }}" @class(['inline' => $variant === 'button'])>
            @csrf
            <button type="submit"
                @class([
                    'inline-flex items-center justify-center rounded-lg border border-brand-300 bg-brand-50 font-medium text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-400',
                    'w-full px-4 py-2.5 text-sm' => $variant !== 'button',
                    'px-4 py-2.5 text-sm' => $variant === 'button',
                ])>
                สร้างลิงก์ชำระเงินออนไลน์
            </button>
        </form>
    @elseif ($variant !== 'button')
        <div class="rounded-lg border border-warning-200 bg-warning-50 px-3 py-2.5 text-xs text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400">
            ออเดอร์ยอด 0 บาท สร้างลิงก์ชำระเงินไม่ได้ — ยืนยันชำระเงินฟรีที่หน้าชำระเงิน
            @if ($order->user)
                <a href="{{ route('students.orders.payment', [$order->user, $order]) }}" class="mt-1 block font-medium underline">ไปหน้าชำระเงิน</a>
            @endif
        </div>
    @endif
@endif
