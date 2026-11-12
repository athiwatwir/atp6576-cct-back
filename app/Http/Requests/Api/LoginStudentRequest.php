<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required_without:email', 'nullable', 'string', 'max:255'],
            'email' => ['required_without:login', 'nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required_without' => 'กรุณากรอกอีเมลหรือเบอร์โทร',
            'email.required_without' => 'กรุณากรอกอีเมลหรือเบอร์โทร',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'login' => 'อีเมลหรือเบอร์โทร',
            'email' => 'อีเมล',
            'password' => 'รหัสผ่าน',
        ];
    }

    /**
     * อีเมลหรือเบอร์โทรที่ใช้เข้าสู่ระบบ รับทั้ง `login` และ `email` (เว็บรุ่นเดิม)
     */
    public function identifier(): string
    {
        return trim((string) ($this->input('login') ?: $this->input('email')));
    }
}
