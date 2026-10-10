<?php

namespace App\Http\Requests\Api;

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
        foreach (['school_name', 'address_line1', 'subdistrict', 'district', 'province', 'postal_code'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'school_name' => ['nullable', 'string', 'max:255'],
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
            'email' => 'อีเมล',
            'phone' => 'เบอร์โทร',
            'school_name' => 'ชื่อโรงเรียน',
            'address_line1' => 'ที่อยู่',
            'subdistrict' => 'ตำบล/แขวง',
            'district' => 'อำเภอ/เขต',
            'province' => 'จังหวัด',
            'postal_code' => 'รหัสไปรษณีย์',
            'avatar' => 'รูปโปรไฟล์',
        ];
    }
}
