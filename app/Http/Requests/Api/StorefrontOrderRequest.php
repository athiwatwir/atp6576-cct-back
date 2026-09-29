<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorefrontOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $couponCode = strtoupper(trim((string) $this->input('coupon_code', '')));

        $this->merge([
            'coupon_code' => $couponCode === '' ? null : $couponCode,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.type' => ['required', Rule::in(['course', 'curriculum', 'book'])],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shipping' => ['nullable', 'array'],
            'shipping.name' => ['nullable', 'string', 'max:150'],
            'shipping.phone' => ['nullable', 'string', 'max:30'],
            'shipping.address_line1' => ['nullable', 'string', 'max:255'],
            'shipping.address_line2' => ['nullable', 'string', 'max:255'],
            'shipping.subdistrict' => ['nullable', 'string', 'max:100'],
            'shipping.district' => ['nullable', 'string', 'max:100'],
            'shipping.province' => ['nullable', 'string', 'max:100'],
            'shipping.postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items' => 'รายการ',
            'items.*.type' => 'ประเภทสินค้า',
            'items.*.id' => 'รหัสสินค้า',
            'items.*.quantity' => 'จำนวน',
            'coupon_code' => 'รหัสคูปอง',
            'notes' => 'หมายเหตุ',
            'shipping.name' => 'ชื่อผู้รับ',
            'shipping.phone' => 'เบอร์โทร',
            'shipping.address_line1' => 'ที่อยู่',
            'shipping.district' => 'อำเภอ/เขต',
            'shipping.province' => 'จังหวัด',
            'shipping.postal_code' => 'รหัสไปรษณีย์',
        ];
    }
}
