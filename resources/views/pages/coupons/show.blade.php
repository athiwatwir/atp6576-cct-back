@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $coupon->code }}" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $coupon->code }}</h2>
                <x-common.status-badge :status="$coupon->status" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $coupon->discountLabel() }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('coupons.edit', $coupon) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">แก้ไข</a>
            <form method="POST" action="{{ route('coupons.destroy', $coupon) }}" onsubmit="return confirm('ต้องการลบคูปองนี้หรือไม่?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-error-300 px-4 py-2.5 text-sm font-medium text-error-600">ลบ</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">ประวัติการใช้</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ผู้ใช้</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">ออเดอร์</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">ส่วนลด</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">เวลา</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($coupon->usages as $usage)
                                <tr>
                                    <td class="px-5 py-4 text-sm text-gray-800 dark:text-white/90">{{ $usage->user?->name ?? '-' }}</td>
                                    <td class="px-5 py-4 text-sm">
                                        @if ($usage->order)
                                            <a href="{{ route('orders.show', $usage->order) }}" class="text-brand-500 hover:text-brand-600">{{ $usage->order->order_no }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm">฿{{ number_format((float) $usage->discount_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-500">{{ $usage->used_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">ยังไม่มีการใช้</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 text-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <dl class="space-y-3">
                    <div><dt class="text-gray-500">ยอดขั้นต่ำ</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->min_purchase_amount !== null ? '฿'.number_format((float) $coupon->min_purchase_amount, 2) : 'ไม่กำหนด' }}</dd></div>
                    <div><dt class="text-gray-500">ใช้แล้ว</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->usage_count }} / {{ $coupon->usage_limit ?? 'ไม่จำกัด' }}</dd></div>
                    <div><dt class="text-gray-500">จำกัดต่อคน</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->per_user_limit ?? 'ไม่จำกัด' }}</dd></div>
                    <div><dt class="text-gray-500">วันเริ่ม</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->start_at?->format('d/m/Y H:i') ?? 'ไม่กำหนด' }}</dd></div>
                    <div><dt class="text-gray-500">วันสิ้นสุด</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->end_at?->format('d/m/Y H:i') ?? 'ไม่กำหนด' }}</dd></div>
                    <div><dt class="text-gray-500">ขอบเขต</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->isRestricted() ? 'เฉพาะสินค้าที่เลือก' : 'ทุกรายการในออเดอร์' }}</dd></div>
                    <div><dt class="text-gray-500">คอร์ส</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->courses->pluck('name')->join(', ') ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">หลักสูตร</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->curriculums->pluck('name')->join(', ') ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">ข้อสอบ</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->assessments->pluck('title')->join(', ') ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">หนังสือ</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $coupon->products->pluck('name')->join(', ') ?: '-' }}</dd></div>
                </dl>
                @if ($coupon->description)
                    <p class="mt-4 text-gray-600 dark:text-gray-400">{{ $coupon->description }}</p>
                @endif
            </div>
            <x-common.activity-logs :entity="$coupon" :limit="8" title="ประวัติคูปอง" />
        </div>
    </div>
@endsection
