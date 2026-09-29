@props([
    'id' => 'datepicker-'.uniqid(),
    'name' => null,
    'label' => null,
    'value' => null,
    'defaultDate' => null,
    'placeholder' => null,
    'enableTime' => false,
    'required' => false,
    'mode' => 'single',
])

@php
    // Blade keeps the kebab-case attribute in ${'enable-time'}, separate from $enableTime.
    $enableTime = filter_var($enableTime ?: (${'enable-time'} ?? false), FILTER_VALIDATE_BOOLEAN);
    $required = filter_var($required, FILTER_VALIDATE_BOOLEAN);
    $defaultDate = $defaultDate ?? (${'default-date'} ?? null);
    $withTime = $enableTime || $mode === 'time';
    $submitFormat = $withTime && $mode !== 'time' ? 'Y-m-d H:i' : ($mode === 'time' ? 'H:i' : 'Y-m-d');
    $displayFormat = $withTime && $mode !== 'time' ? 'd/m/Y H:i' : ($mode === 'time' ? 'H:i' : 'd/m/Y');
    $placeholder = $placeholder ?? ($withTime && $mode !== 'time' ? 'วว/ดด/ปปปป ชม:นาที' : ($mode === 'time' ? 'ชม:นาที' : 'วว/ดด/ปปปป'));

    $raw = $value ?? $defaultDate;
    if ($raw instanceof \DateTimeInterface) {
        $raw = $raw->format($submitFormat);
    }
    $raw = is_string($raw) ? trim(str_replace('T', ' ', $raw)) : '';
    if ($raw !== '' && ! $withTime) {
        $raw = substr($raw, 0, 10);
    }

    $inputClass = 'h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';

    $pickerOptions = [
        'mode' => $mode === 'time' ? 'single' : $mode,
        'enableTime' => $withTime,
        'noCalendar' => $mode === 'time',
        'time_24hr' => true,
        'minuteIncrement' => 1,
        'altInput' => true,
        'altFormat' => $displayFormat,
        'altInputClass' => $inputClass,
        'dateFormat' => $submitFormat,
        'defaultDate' => $raw !== '' ? $raw : null,
        'allowInput' => true,
        'disableMobile' => true,
        'static' => true,
        'monthSelectorType' => 'static',
        'placeholder' => $placeholder,
        'required' => $required,
    ];
@endphp

<div
    data-picker-options="{{ json_encode($pickerOptions) }}"
    x-data="{
        picker: null,
        init() {
            const options = JSON.parse(this.$el.dataset.pickerOptions);
            const placeholder = options.placeholder;
            const required = options.required;
            delete options.placeholder;
            delete options.required;
            options.onReady = (selectedDates, dateStr, instance) => {
                if (!instance.altInput) return;
                instance.altInput.placeholder = placeholder;
                if (required) {
                    instance.altInput.required = true;
                    instance.input.removeAttribute('required');
                }
            };
            this.picker = flatpickr(this.$refs.dateInput, options);
        },
        destroy() {
            if (this.picker) {
                this.picker.destroy();
                this.picker = null;
            }
        },
    }"
    x-init="init()"
    x-destroy="destroy()"
>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }}@if ($required)<span class="text-error-500">*</span>@endif
        </label>
    @endif

    <div class="relative custom-datepicker">
        <input
            x-ref="dateInput"
            type="text"
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            value="{{ $raw }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            @if ($required) required @endif
            class="{{ $inputClass }}"
        />
        <button type="button" @click="picker?.open()" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400" tabindex="-1" aria-label="เปิดปฏิทิน">
            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" class="size-6">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor"></path>
            </svg>
        </button>
    </div>

    @if ($name)
        @error($name)
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    @endif
</div>
