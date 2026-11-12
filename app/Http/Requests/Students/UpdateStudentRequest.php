<?php

namespace App\Http\Requests\Students;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Rules\StudentPhone;
use App\Support\ThaiProvinces;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateStudentRequest extends FormRequest
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

        $this->merge(['school_province' => ThaiProvinces::normalize($this->input('school_province'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $student */
        $student = $this->route('student');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'nullable',
                'required_without:phone',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student->id),
            ],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', new StudentPhone($student->id)],
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_province' => ['nullable', 'string', Rule::in(ThaiProvinces::all())],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'status' => ['required', Rule::in(array_column(AccountStatus::cases(), 'value'))],
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
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร',
            'postal_code.regex' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school_name' => 'ชื่อโรงเรียน',
            'school_province' => 'จังหวัดที่เรียน',
            'address_line1' => 'ที่อยู่',
            'subdistrict' => 'ตำบล/แขวง',
            'district' => 'อำเภอ/เขต',
            'province' => 'จังหวัด',
            'postal_code' => 'รหัสไปรษณีย์',
        ];
    }
}
