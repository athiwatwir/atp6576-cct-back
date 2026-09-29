@php
    $steps = [
        1 => 'สร้างนักเรียน',
        2 => 'สร้างออเดอร์',
        3 => 'ชำระเงิน',
    ];
@endphp

<ol class="mb-6 grid grid-cols-1 gap-2 sm:grid-cols-3">
    @foreach ($steps as $number => $label)
        <li class="flex items-center gap-2 rounded-xl border px-4 py-3 text-sm {{ $number === $step ? 'border-brand-300 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300' : ($number < $step ? 'border-success-200 bg-success-50 text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400' : 'border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400') }}">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold {{ $number === $step ? 'bg-brand-500 text-white' : ($number < $step ? 'bg-success-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-white/10') }}">{{ $number }}</span>
            {{ $label }}
        </li>
    @endforeach
</ol>
