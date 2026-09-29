<?php

namespace App\Http\Requests\Students;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentEnrollmentRequest extends FormRequest
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
            'status' => ['required', Rule::in(array_column(EnrollmentStatus::cases(), 'value'))],
            'started_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'started_at' => 'วันที่เริ่มเรียน',
            'expires_at' => 'วันหมดอายุ',
        ];
    }
}
