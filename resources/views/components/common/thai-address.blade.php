@props([
    'prefix' => 'shipping',
    'subdistrict' => '',
    'district' => '',
    'province' => '',
    'postalCode' => '',
    'addressLine1' => '',
])

@php
    $ids = [
        'address' => $prefix.'_address_line1',
        'subdistrict' => $prefix.'_subdistrict',
        'district' => $prefix.'_district',
        'province' => $prefix.'_province',
        'postal' => $prefix.'_postal_code',
    ];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" data-thai-address>
    <div class="sm:col-span-2 lg:col-span-4">
        <label for="{{ $ids['address'] }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ที่อยู่<span class="text-error-500">*</span>
        </label>
        <input type="text" id="{{ $ids['address'] }}" name="{{ $ids['address'] }}"
            value="{{ old($ids['address'], $addressLine1) }}" required
            placeholder="บ้านเลขที่ หมู่ ซอย ถนน"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error($ids['address'])
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['subdistrict'] }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ตำบล/แขวง
        </label>
        <input type="text" id="{{ $ids['subdistrict'] }}" name="{{ $ids['subdistrict'] }}"
            value="{{ old($ids['subdistrict'], $subdistrict) }}" autocomplete="off"
            class="js-thai-subdistrict dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error($ids['subdistrict'])
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['district'] }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            อำเภอ/เขต<span class="text-error-500">*</span>
        </label>
        <input type="text" id="{{ $ids['district'] }}" name="{{ $ids['district'] }}"
            value="{{ old($ids['district'], $district) }}" required autocomplete="off"
            class="js-thai-district dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error($ids['district'])
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['province'] }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            จังหวัด<span class="text-error-500">*</span>
        </label>
        <input type="text" id="{{ $ids['province'] }}" name="{{ $ids['province'] }}"
            value="{{ old($ids['province'], $province) }}" required autocomplete="off"
            class="js-thai-province dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error($ids['province'])
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $ids['postal'] }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            รหัสไปรษณีย์<span class="text-error-500">*</span>
        </label>
        <input type="text" id="{{ $ids['postal'] }}" name="{{ $ids['postal'] }}"
            value="{{ old($ids['postal'], $postalCode) }}" required autocomplete="off"
            class="js-thai-zipcode dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error($ids['postal'])
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>
</div>
