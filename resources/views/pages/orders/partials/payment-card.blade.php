{{-- ต้องส่ง $order และ $latestPayment --}}
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">การชำระเงิน</h3>
    <div class="mt-4 space-y-3 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-500">วิธีชำระ</span>
            <span class="font-medium text-gray-800 dark:text-white/90">
                {{ \App\Enums\PaymentMethod::tryFrom($latestPayment?->payment_method ?? '')?->label() ?? '-' }}
            </span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">สถานะ</span>
            <x-common.status-badge :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status)" />
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">ชำระเมื่อ</span>
            <span>{{ $order->paid_at?->format('d/m/Y H:i') ?? '-' }}</span>
        </div>
        @if ($order->payment_status === 'paid')
            <div class="flex items-center justify-between gap-3">
                <span class="shrink-0 text-gray-500">ใบเสร็จ</span>
                <span class="flex items-center gap-2">
                    @if ($order->receipt)
                        <span class="font-mono text-xs">{{ $order->receipt->receipt_no }}</span>
                    @endif
                    <a href="{{ route('orders.receipt', [$order, 'inline' => 1]) }}" target="_blank" rel="noopener" data-no-loading class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">เปิด</a>
                    <a href="{{ route('orders.receipt', $order) }}" data-no-loading class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">ดาวน์โหลด PDF</a>
                </span>
            </div>
        @endif
        @if ((float) ($latestPayment?->fee_amount ?? 0) > 0)
            <div class="flex justify-between gap-3">
                <span class="shrink-0 text-gray-500">ค่าธรรมเนียม</span>
                <span>฿{{ number_format((float) $latestPayment->fee_amount, 2) }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="shrink-0 text-gray-500">ยอดที่ลูกค้าจ่าย</span>
                <span class="font-medium text-gray-800 dark:text-white/90">฿{{ number_format((float) $latestPayment->amount, 2) }}</span>
            </div>
        @endif
        @if ($latestPayment?->isGateway())
            <div class="flex justify-between gap-3">
                <span class="shrink-0 text-gray-500">ผู้ให้บริการ</span>
                <span class="text-right font-medium text-gray-800 dark:text-white/90">
                    {{ match ($latestPayment->provider) { 'kgp' => 'KGP (Thai QR)', 'omise' => 'Omise (บัตร)', default => 'โหมดทดสอบ' } }}
                </span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="shrink-0 text-gray-500">เลขอ้างอิง</span>
                <span class="text-right font-mono text-xs text-gray-800 dark:text-white/90">{{ $latestPayment->reference ?? '-' }}</span>
            </div>
            @if ($latestPayment->transaction_id)
                <div class="flex justify-between gap-3">
                    <span class="shrink-0 text-gray-500">รหัสรายการ</span>
                    <span class="break-all text-right font-mono text-xs text-gray-800 dark:text-white/90">{{ $latestPayment->metadata['charge_id'] ?? $latestPayment->transaction_id }}</span>
                </div>
            @endif
            @if ($latestPayment->expires_at && $latestPayment->status === 'pending')
                <div class="flex justify-between gap-3">
                    <span class="shrink-0 text-gray-500">QR หมดอายุ</span>
                    <span>{{ $latestPayment->expires_at->format('d/m/Y H:i') }}</span>
                </div>
            @endif
            @if ($latestPayment->failure_message && $latestPayment->status === 'failed')
                <div class="rounded-lg bg-error-50 px-3 py-2 text-xs text-error-600 dark:bg-error-500/10 dark:text-error-400">
                    {{ $latestPayment->failure_message }}@if ($latestPayment->failure_code) ({{ $latestPayment->failure_code }})@endif
                </div>
            @endif
            @if ($latestPayment->status === 'pending')
                <div class="flex flex-wrap gap-2 pt-1">
                    <form method="POST" action="{{ route('orders.payments.refresh', [$order, $latestPayment]) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">ตรวจสถานะกับผู้ให้บริการ</button>
                    </form>
                    @if ($latestPayment->provider === 'fake')
                        <form method="POST" action="{{ route('orders.payments.simulate', [$order, $latestPayment]) }}">
                            @csrf
                            <input type="hidden" name="state" value="paid" />
                            <button type="submit" class="rounded-lg bg-success-600 px-3 py-2 text-xs font-medium text-white">จำลองชำระสำเร็จ</button>
                        </form>
                    @endif
                </div>
            @endif
        @endif
    </div>

    @if (! in_array($order->payment_status, ['paid', 'refunded'], true) && (float) $order->total_amount > 0 && $order->status !== 'cancelled')
        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
            @if (session('payment_link'))
                <div x-data="{ copied: false }" class="space-y-2">
                    <p class="text-xs text-gray-500">ลิงก์ชำระเงิน (QR พร้อมเพย์ / บัตร)</p>
                    <input type="text" readonly value="{{ session('payment_link') }}" x-ref="link" @focus="$el.select()"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 font-mono text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                    <div class="flex gap-2">
                        <button type="button" data-no-loading @click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
                            <span x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์'"></span>
                        </button>
                        <a href="{{ session('payment_link') }}" target="_blank" rel="noopener" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">เปิดหน้าชำระ</a>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('orders.payment-link', $order) }}">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-brand-300 bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-400">
                        สร้างลิงก์ชำระเงินออนไลน์
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
