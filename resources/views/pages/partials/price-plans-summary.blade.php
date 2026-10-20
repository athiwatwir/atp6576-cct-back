@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\PricePlan> $plans */
@endphp

<div class="rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-white/[0.03]">
    <div class="text-xs text-gray-500 dark:text-gray-400">แพ็กเกจราคา</div>
    @forelse ($plans as $plan)
        <div class="mt-2 flex items-center justify-between gap-3 text-sm {{ $plan->is_active ? '' : 'opacity-50' }}">
            <span class="text-gray-700 dark:text-gray-300">
                {{ $plan->label }}
                @if (! $plan->is_active)
                    <span class="text-xs text-gray-400">(ปิดขาย)</span>
                @endif
            </span>
            <span class="text-right">
                @if ($plan->sale_price !== null && (float) $plan->sale_price > 0)
                    <span class="font-semibold text-brand-600 dark:text-brand-400">฿{{ number_format((float) $plan->sale_price, 2) }}</span>
                    <span class="ml-1 text-xs text-gray-400 line-through">฿{{ number_format((float) $plan->price, 2) }}</span>
                @else
                    <span class="font-semibold text-gray-800 dark:text-white/90">฿{{ number_format((float) $plan->price, 2) }}</span>
                @endif
            </span>
        </div>
    @empty
        <div class="mt-1 text-sm text-gray-500">ยังไม่มีแพ็กเกจ</div>
    @endforelse
</div>
