@props([
    'prefix' => 'shipping',
    'subdistrict' => '',
    'district' => '',
    'province' => '',
    'postalCode' => '',
    'addressLine1' => '',
    'required' => true,
    'compact' => false,
])

@php
    $field = fn (string $name) => $prefix === '' ? $name : $prefix.'_'.$name;
    $ids = [
        'address' => $field('address_line1'),
        'subdistrict' => $field('subdistrict'),
        'district' => $field('district'),
        'province' => $field('province'),
        'postal' => $field('postal_code'),
    ];
    $gridClass = $compact ? 'grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-6' : 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4';
    $addressSpan = $compact ? 'sm:col-span-2 lg:col-span-2' : 'sm:col-span-2 lg:col-span-4';
    $labelClass = $compact ? 'mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400' : 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    $inputClass = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 '
        .($compact ? 'h-10 px-3 py-2' : 'h-11 px-4 py-2.5');
    $errorClass = $compact ? 'mt-1 text-xs text-error-500' : 'mt-1.5 text-sm text-error-500';
@endphp

<div class="{{ $gridClass }}" data-thai-address>
    <div class="{{ $addressSpan }}">
        <label for="{{ $ids['address'] }}" class="{{ $labelClass }}">
            ที่อยู่@if ($required)<span class="text-error-500">*</span>@endif
        </label>
        <input type="text" id="{{ $ids['address'] }}" name="{{ $ids['address'] }}"
            value="{{ old($ids['address'], $addressLine1) }}" @if ($required) required @endif
            placeholder="บ้านเลขที่ หมู่ ซอย ถนน"
            class="{{ $inputClass }}" />
        @error($ids['address'])
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['subdistrict'] }}" class="{{ $labelClass }}">
            ตำบล/แขวง
        </label>
        <input type="text" id="{{ $ids['subdistrict'] }}" name="{{ $ids['subdistrict'] }}"
            value="{{ old($ids['subdistrict'], $subdistrict) }}" autocomplete="off"
            class="js-thai-subdistrict {{ $inputClass }}" />
        @error($ids['subdistrict'])
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['district'] }}" class="{{ $labelClass }}">
            อำเภอ/เขต@if ($required)<span class="text-error-500">*</span>@endif
        </label>
        <input type="text" id="{{ $ids['district'] }}" name="{{ $ids['district'] }}"
            value="{{ old($ids['district'], $district) }}" @if ($required) required @endif autocomplete="off"
            class="js-thai-district {{ $inputClass }}" />
        @error($ids['district'])
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['province'] }}" class="{{ $labelClass }}">
            จังหวัด@if ($required)<span class="text-error-500">*</span>@endif
        </label>
        <input type="text" id="{{ $ids['province'] }}" name="{{ $ids['province'] }}"
            value="{{ old($ids['province'], $province) }}" @if ($required) required @endif autocomplete="off"
            class="js-thai-province {{ $inputClass }}" />
        @error($ids['province'])
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['postal'] }}" class="{{ $labelClass }}">
            รหัสไปรษณีย์@if ($required)<span class="text-error-500">*</span>@endif
        </label>
        <input type="text" id="{{ $ids['postal'] }}" name="{{ $ids['postal'] }}"
            value="{{ old($ids['postal'], $postalCode) }}" @if ($required) required @endif autocomplete="off"
            class="js-thai-zipcode {{ $inputClass }}" />
        @error($ids['postal'])
            <p class="{{ $errorClass }}">{{ $message }}</p>
        @enderror
    </div>
</div>
