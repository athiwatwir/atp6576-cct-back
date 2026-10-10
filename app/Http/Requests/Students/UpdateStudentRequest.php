<?php

namespace App\Http\Requests\Students;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'status' => ['required', Rule::in(array_column(AccountStatus::cases(), 'value'))],
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
            'school_name' => 'ชื่อโรงเรียน',
            'address_line1' => 'ที่อยู่',
            'subdistrict' => 'ตำบล/แขวง',
            'district' => 'อำเภอ/เขต',
            'province' => 'จังหวัด',
            'postal_code' => 'รหัสไปรษณีย์',
        ];
    }
}
