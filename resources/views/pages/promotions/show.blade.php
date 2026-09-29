@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $promotion->name }}" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $promotion->name }}</h2>
                <x-common.status-badge :status="$promotion->status" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $promotion->discountLabel() }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('promotions.edit', $promotion) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">แก้ไข</a>
            <form method="POST" action="{{ route('promotions.destroy', $promotion) }}" onsubmit="return confirm('ต้องการลบโปรโมชันนี้หรือไม่?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-error-300 px-4 py-2.5 text-sm font-medium text-error-600">ลบ</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">เงื่อนไข</h3>
                <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">ยอดขั้นต่ำ</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->min_purchase_amount !== null ? '฿'.number_format((float) $promotion->min_purchase_amount, 2) : 'ไม่กำหนด' }}</dd></div>
                    <div><dt class="text-gray-500">จำนวนครั้ง</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->usage_count }} / {{ $promotion->usage_limit ?? 'ไม่จำกัด' }}</dd></div>
                    <div><dt class="text-gray-500">วันเริ่ม</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->start_at?->format('d/m/Y H:i') ?? 'ไม่กำหนด' }}</dd></div>
                    <div><dt class="text-gray-500">วันสิ้นสุด</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->end_at?->format('d/m/Y H:i') ?? 'ไม่กำหนด' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">ขอบเขต</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->isRestricted() ? 'เฉพาะสินค้าที่เลือก' : 'ทุกรายการในออเดอร์' }}</dd></div>
                    <div><dt class="text-gray-500">กลุ่มนักเรียน</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->studentGroupLabel() }}</dd></div>
                    <div><dt class="text-gray-500">ต้องเคยซื้อ</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->requiredPurchaseLabel() }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">ของแถม</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->giftLabel() }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">วิดีโอ</dt><dd class="font-medium text-gray-800 dark:text-white/90">{{ $promotion->requiredVideoLabel() }}</dd></div>
                </dl>
                @if ($promotion->description)
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ $promotion->description }}</p>
                @endif
            </div>

        </div>

        <div class="space-y-6 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 text-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="font-semibold text-gray-800 dark:text-white/90">สินค้าที่เลือก</h3>
                <div class="mt-3 space-y-3 text-gray-600 dark:text-gray-400">
                    <div><span class="text-gray-500">คอร์ส</span><div>{{ $promotion->courses->pluck('name')->join(', ') ?: '-' }}</div></div>
                    <div><span class="text-gray-500">หลักสูตร</span><div>{{ $promotion->curriculums->pluck('name')->join(', ') ?: '-' }}</div></div>
                    <div><span class="text-gray-500">ข้อสอบ</span><div>{{ $promotion->assessments->pluck('title')->join(', ') ?: '-' }}</div></div>
                    <div><span class="text-gray-500">หนังสือ</span><div>{{ $promotion->products->pluck('name')->join(', ') ?: '-' }}</div></div>
                </div>
            </div>
            <x-common.activity-logs :entity="$promotion" :limit="8" title="ประวัติโปรโมชัน" />
        </div>
    </div>
@endsection
