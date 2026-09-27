@props([
    'status' => null,
    'set' => 'content',
    'fallback' => null,
])

@php
    use App\Enums\AccountStatus;
    use App\Enums\ContentStatus;

    if ($status instanceof \BackedEnum && method_exists($status, 'label') && method_exists($status, 'badgeClass')) {
        $label = $status->label();
        $class = $status->badgeClass();
    } else {
        $value = is_scalar($status) ? (string) $status : '';
        $resolved = $set === 'account' ? AccountStatus::tryFrom($value) : ContentStatus::tryFrom($value);
        $fallbackValue = $fallback ?? ($set === 'account' ? 'inactive' : 'draft');
        $fallbackStatus = $set === 'account'
            ? AccountStatus::tryFrom($fallbackValue)
            : ContentStatus::tryFrom($fallbackValue);

        $label = $resolved?->label() ?? ($value !== '' ? $value : ($fallbackStatus?->label() ?? ''));
        $class = $resolved?->badgeClass() ?? $fallbackStatus?->badgeClass() ?? \App\Support\StatusPalette::NEUTRAL;
    }
@endphp

<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium', $class]) }}>{{ $label }}</span>
