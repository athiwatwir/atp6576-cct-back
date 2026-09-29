<?php

namespace App\Http\Requests\Coupons;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code', '')));
        $payload = ['code' => $code];

        foreach (['description', 'max_discount_amount', 'min_purchase_amount', 'usage_limit', 'per_user_limit', 'start_at', 'end_at'] as $field) {
            if ($this->input($field) === '') {
                $payload[$field] = null;
            }
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')],
            'description' => ['nullable', 'string', 'max:5000'],
            'discount_type' => ['required', Rule::in(array_column(DiscountType::cases(), 'value'))],
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                Rule::when($this->input('discount_type') === DiscountType::Percentage->value, 'max:100'),
            ],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', Rule::when($this->filled('start_at'), 'after_or_equal:start_at')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'curriculum_ids' => ['nullable', 'array'],
            'curriculum_ids.*' => ['integer', 'exists:curriculums,id'],
            'assessment_ids' => ['nullable', 'array'],
            'assessment_ids.*' => ['integer', 'exists:assessments,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'รหัสคูปองใช้ได้เฉพาะตัวอักษรภาษาอังกฤษ ตัวเลข _ และ -',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'รหัสคูปอง',
            'description' => 'รายละเอียด',
            'discount_type' => 'ประเภทส่วนลด',
            'discount_value' => 'มูลค่าส่วนลด',
            'max_discount_amount' => 'ส่วนลดสูงสุด',
            'min_purchase_amount' => 'ยอดขั้นต่ำ',
            'usage_limit' => 'จำนวนครั้งที่ใช้ได้',
            'per_user_limit' => 'จำกัดต่อคน',
            'start_at' => 'วันเริ่ม',
            'end_at' => 'วันสิ้นสุด',
            'status' => 'สถานะ',
            'course_ids' => 'คอร์ส',
            'curriculum_ids' => 'หลักสูตร',
            'assessment_ids' => 'ข้อสอบ',
            'product_ids' => 'หนังสือ',
        ];
    }
}
