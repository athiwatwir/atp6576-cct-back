<?php

namespace App\Http\Requests\Api;

use App\Rules\StudentPhone;
use App\Support\ThaiProvinces;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nickname')) {
            $this->merge(['nickname' => trim((string) $this->input('nickname'))]);
        }

        foreach (['nickname', 'school_name', 'school_province', 'address_line1', 'subdistrict', 'district', 'province', 'postal_code'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->has('school_province')) {
            $this->merge(['school_province' => ThaiProvinces::normalize($this->input('school_province'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'required_without:phone', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', new StudentPhone($userId)],
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_province' => ['nullable', 'string', Rule::in(ThaiProvinces::all())],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'กรอกอีเมลหรือเบอร์โทรอย่างน้อยหนึ่งอย่าง',
            'phone.required_without' => 'กรอกอีเมลหรือเบอร์โทรอย่างน้อยหนึ่งอย่าง',
            'school_province.in' => 'กรุณาเลือกจังหวัดจากรายการ',
            'postal_code.regex' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อ',
            'nickname' => 'ชื่อเล่น',
            'email' => 'อีเมล',
            'phone' => 'เบอร์โทร',
            'school_name' => 'ชื่อโรงเรียน',
            'school_province' => 'จังหวัดที่เรียน',
            'address_line1' => 'ที่อยู่',
            'subdistrict' => 'ตำบล/แขวง',
            'district' => 'อำเภอ/เขต',
            'province' => 'จังหวัด',
            'postal_code' => 'รหัสไปรษณีย์',
            'avatar' => 'รูปโปรไฟล์',
        ];
    }
}
