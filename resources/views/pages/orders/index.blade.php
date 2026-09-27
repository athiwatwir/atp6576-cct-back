@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="ออเดอร์" />

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
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">รายการออเดอร์</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">สร้างคำสั่งซื้อหนังสือ ติดตามการชำระเงินและการจัดส่ง</p>
            </div>
            <a href="{{ route('orders.create') }}"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                + สร้างออเดอร์
            </a>
        </div>

        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <form method="GET" action="{{ route('orders.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                        placeholder="ค้นหาเลขออเดอร์ / พัสดุ / ลูกค้า"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div>
                    <select name="status"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">สถานะออเดอร์</option>
                        @foreach ($orderStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="payment_status"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">สถานะชำระเงิน</option>
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="shipping_status"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">สถานะจัดส่ง</option>
                        @foreach ($shippingStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['shipping_status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="bg-brand-500 hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-medium text-white whitespace-nowrap">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ออเดอร์</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ลูกค้า / ที่อยู่</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ยอดรวม</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">ชำระเงิน</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">พัสดุ</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-800 dark:text-white/90">{{ $order->order_no }}</div>
                                <div class="mt-1 text-xs text-gray-400">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $order->items->count() }} รายการ</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-gray-800 dark:text-white/90">{{ $order->user?->name ?? '-' }}</div>
                                <div class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $order->shipping_name }} · {{ $order->shipping_phone }}</div>
                                <div class="mt-0.5 line-clamp-1 text-xs text-gray-400">{{ $order->shipping_full_address }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">
                                ฿{{ number_format((float) $order->total_amount, 2) }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ \App\Enums\PaymentMethod::tryFrom($order->latestPayment?->payment_method ?? '')?->label() ?? '-' }}
                                </div>
                                <x-common.status-badge class="mt-1" :status="\App\Enums\PaymentStatus::tryFrom($order->payment_status) ?? $order->payment_status" />
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <div>{{ $order->shipping_status_label }}</div>
                                @if ($order->tracking_number)
                                    <div class="mt-1 font-mono text-xs text-gray-800 dark:text-white/90">{{ $order->tracking_number }}</div>
                                    @if ($order->shipping_carrier)
                                        <div class="text-xs text-gray-400">{{ $order->shipping_carrier }}</div>
                                    @endif
                                @else
                                    <div class="mt-1 text-xs text-gray-400">ยังไม่มีเลขพัสดุ</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <x-common.status-badge :status="\App\Enums\OrderStatus::tryFrom($order->status) ?? $order->status" />
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('orders.show', $order) }}"
                                        class="inline-flex items-center rounded-lg border border-blue-light-300 bg-blue-light-50 px-3 py-2 text-xs font-medium text-blue-light-700 hover:bg-blue-light-100 dark:border-blue-light-500/40 dark:bg-blue-light-500/10 dark:text-blue-light-400">
                                        ดู
                                    </a>
                                    <a href="{{ route('orders.edit', $order) }}"
                                        class="inline-flex items-center rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-400">
                                        แก้ไข
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                ยังไม่มีออเดอร์ กด “+ สร้างออเดอร์” เพื่อเริ่มต้น
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endsection
