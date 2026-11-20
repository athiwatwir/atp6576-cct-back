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
                    {{ match (true) {
                        $latestPayment->provider === 'kgp' => 'KGP (Thai QR)',
                        $latestPayment->provider === 'omise' && \App\Enums\PaymentMethod::tryFrom((string) $latestPayment->payment_method)?->isWallet() => 'Omise (e-Wallet)',
                        $latestPayment->provider === 'omise' => 'Omise (บัตร)',
                        default => 'โหมดทดสอบ',
                    } }}
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
            @php
                $gatewayEvents = ($latestPayment->relationLoaded('transactions')
                    ? $latestPayment->transactions
                    : $latestPayment->transactions()->latest('id')->limit(20)->get()
                )->sortByDesc('id')->take(20)->values();
            @endphp
            @if ($gatewayEvents->isNotEmpty())
                <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-800" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left text-xs font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        <span>บันทึก JSON จากระบบชำระเงิน ({{ $gatewayEvents->count() }})</span>
                        <span x-text="open ? 'ซ่อน' : 'แสดง'"></span>
                    </button>
                    <div x-show="open" x-cloak class="mt-2 max-h-80 space-y-2 overflow-y-auto">
                        @foreach ($gatewayEvents as $event)
                            <details class="rounded-lg border border-gray-100 bg-gray-50 p-2 dark:border-gray-800 dark:bg-white/[0.02]">
                                <summary class="cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                    <span class="font-mono">{{ $event->event_type }}</span>
                                    @if ($event->status)
                                        <span class="text-gray-400">· {{ $event->status }}</span>
                                    @endif
                                    <span class="text-gray-400">· {{ $event->processed_at?->format('d/m H:i:s') ?? $event->created_at?->format('d/m H:i:s') }}</span>
                                </summary>
                                <pre class="mt-2 overflow-x-auto whitespace-pre-wrap break-all text-[11px] leading-4 text-gray-600 dark:text-gray-400">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if (! in_array($order->payment_status, ['paid', 'refunded'], true) && $order->status !== 'cancelled')
        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
            @include('pages.orders.partials.payment-link', ['order' => $order, 'variant' => 'card'])
        </div>
    @endif
</div>
