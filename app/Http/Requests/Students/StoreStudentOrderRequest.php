<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('coupon_code', '')));
        $this->merge(['coupon_code' => $code === '' ? null : $code]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', Rule::in(['course', 'curriculum', 'exam'])],
            'items.*.id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'items' => 'รายการ',
            'items.*.type' => 'ประเภท',
            'items.*.id' => 'สินค้า',
            'notes' => 'หมายเหตุ',
            'coupon_code' => 'รหัสคูปอง',
        ];
    }
}
