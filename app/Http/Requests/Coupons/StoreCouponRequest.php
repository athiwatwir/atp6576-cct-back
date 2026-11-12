<?php

namespace App\Http\Requests\Coupons;

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\Video;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

        // เว้นว่าง = ไม่มีส่วนลด (คูปองแถมอย่างเดียว)
        if ($this->input('discount_value') === null || $this->input('discount_value') === '') {
            $payload['discount_value'] = 0;
        }

        foreach (['description', 'max_discount_amount', 'min_purchase_amount', 'usage_limit', 'per_user_limit', 'start_at', 'end_at', 'required_video_minutes'] as $field) {
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
                'min:0',
                'max:999999',
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
            'student_group' => ['required', Rule::in(array_keys(Coupon::STUDENT_GROUPS))],
            'purchase_type' => ['required', Rule::in(array_keys(Coupon::PURCHASE_TYPES))],
            'required_course_ids' => ['nullable', 'array'],
            'required_course_ids.*' => ['integer', 'exists:courses,id'],
            'required_product_ids' => ['nullable', 'array'],
            'required_product_ids.*' => ['integer', 'exists:products,id'],
            'required_video_ids' => ['nullable', 'array'],
            'required_video_ids.*' => ['integer', 'exists:videos,id'],
            'required_video_minutes' => ['nullable', 'numeric', 'min:0.5', 'max:1440', 'multiple_of:0.5'],
            'gift_course_ids' => ['nullable', 'array'],
            'gift_course_ids.*' => ['integer', 'exists:courses,id'],
            'gift_assessment_ids' => ['nullable', 'array'],
            'gift_assessment_ids.*' => ['integer', 'exists:assessments,id'],
            'gift_product_ids' => ['nullable', 'array'],
            'gift_product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $gifts = array_filter([...(array) $this->input('gift_assessment_ids', []), ...(array) $this->input('gift_product_ids', [])]);
                if (array_filter((array) $this->input('gift_course_ids', [])) !== [] && $gifts === []) {
                    $validator->errors()->add('gift_course_ids', 'เลือกข้อสอบหรือหนังสือที่จะแถมด้วย');
                }

                if (! $validator->errors()->has('discount_value') && (float) $this->input('discount_value') <= 0 && $gifts === []) {
                    $validator->errors()->add('discount_value', 'กรอกมูลค่าส่วนลด หรือเลือกของแถมอย่างน้อย 1 รายการ');
                }
            },
            function (Validator $validator) {
                $minutes = (float) $this->input('required_video_minutes');
                $ids = array_filter((array) $this->input('required_video_ids', []));

                if ($minutes <= 0 || $validator->errors()->hasAny(['required_video_ids', 'required_video_ids.*', 'required_video_minutes'])) {
                    return;
                }

                if ($ids === []) {
                    $validator->errors()->add('required_video_minutes', 'เลือกวิดีโอที่ต้องดูก่อน แล้วจึงกำหนดจำนวนนาที');

                    return;
                }

                $tooShort = Video::query()
                    ->whereIn('id', $ids)
                    ->where('duration_seconds', '>', 0)
                    ->where('duration_seconds', '<', (int) round($minutes * 60))
                    ->pluck('title');

                if ($tooShort->isNotEmpty()) {
                    $validator->errors()->add('required_video_minutes', 'จำนวนนาทีเกินความยาวของวิดีโอ '.$tooShort->join(', '));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'รหัสคูปองใช้ได้เฉพาะตัวอักษรภาษาอังกฤษ ตัวเลข _ และ -',
            'required_video_minutes.multiple_of' => 'จำนวนนาทีใส่ทศนิยมได้ทีละ 0.5',
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
            'course_ids' => 'วิชา',
            'curriculum_ids' => 'หลักสูตร',
            'assessment_ids' => 'ข้อสอบ',
            'product_ids' => 'หนังสือ',
            'student_group' => 'กลุ่มนักเรียน',
            'purchase_type' => 'ประเภทการซื้อ',
            'required_course_ids' => 'วิชาที่ต้องเคยซื้อ',
            'required_product_ids' => 'หนังสือที่ต้องเคยซื้อ',
            'required_video_ids' => 'วิดีโอที่ต้องดู',
            'required_video_minutes' => 'จำนวนนาทีที่ต้องดู',
            'gift_course_ids' => 'วิชาที่ซื้อแล้วได้ของแถม',
            'gift_assessment_ids' => 'ข้อสอบแถม',
            'gift_product_ids' => 'หนังสือแถม',
        ];
    }
}
