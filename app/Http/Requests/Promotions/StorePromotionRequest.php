<?php

namespace App\Http\Requests\Promotions;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['description', 'max_discount_amount', 'min_purchase_amount', 'usage_limit', 'start_at', 'end_at'] as $field) {
            if ($this->input($field) === '') {
                $payload[$field] = null;
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
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
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', Rule::when($this->filled('start_at'), 'after_or_equal:start_at')],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'curriculum_ids' => ['nullable', 'array'],
            'curriculum_ids.*' => ['integer', 'exists:curriculums,id'],
            'assessment_ids' => ['nullable', 'array'],
            'assessment_ids.*' => ['integer', 'exists:assessments,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'student_group' => ['required', Rule::in(['all', 'new', 'returning'])],
            'required_course_ids' => ['nullable', 'array'],
            'required_course_ids.*' => ['integer', 'exists:courses,id'],
            'required_product_ids' => ['nullable', 'array'],
            'required_product_ids.*' => ['integer', 'exists:products,id'],
            'required_video_ids' => ['nullable', 'array'],
            'required_video_ids.*' => ['integer', 'exists:videos,id'],
            'gift_course_ids' => ['nullable', 'array'],
            'gift_course_ids.*' => ['integer', 'exists:courses,id'],
            'gift_assessment_ids' => ['nullable', 'array'],
            'gift_assessment_ids.*' => ['integer', 'exists:assessments,id'],
            'gift_product_ids' => ['nullable', 'array'],
            'gift_product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อโปรโมชัน',
            'description' => 'รายละเอียด',
            'discount_type' => 'ประเภทส่วนลด',
            'discount_value' => 'มูลค่าส่วนลด',
            'max_discount_amount' => 'ส่วนลดสูงสุด',
            'min_purchase_amount' => 'ยอดขั้นต่ำ',
            'usage_limit' => 'จำนวนครั้งที่ใช้ได้',
            'start_at' => 'วันเริ่ม',
            'end_at' => 'วันสิ้นสุด',
            'status' => 'สถานะ',
            'course_ids' => 'คอร์ส',
            'curriculum_ids' => 'หลักสูตร',
            'assessment_ids' => 'ข้อสอบ',
            'product_ids' => 'หนังสือ',
            'student_group' => 'กลุ่มนักเรียน',
            'required_course_ids' => 'คอร์สที่ต้องเคยซื้อ',
            'required_product_ids' => 'หนังสือที่ต้องเคยซื้อ',
            'required_video_ids' => 'วิดีโอที่ต้องดู',
            'gift_course_ids' => 'คอร์สที่ซื้อแล้วได้ของแถม',
            'gift_assessment_ids' => 'ข้อสอบแถม',
            'gift_product_ids' => 'หนังสือแถม',
        ];
    }
}
