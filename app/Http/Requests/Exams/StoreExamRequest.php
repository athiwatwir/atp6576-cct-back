<?php

namespace App\Http\Requests\Exams;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('duration_minutes') === '') {
            $this->merge(['duration_minutes' => null]);
        }

        if ($this->input('passing_score') === '') {
            $this->merge(['passing_score' => null]);
        }

        if ($this->input('max_attempts') === '') {
            $this->merge(['max_attempts' => null]);
        }

        if ($this->input('price') === '') {
            $this->merge(['price' => 0]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'passing_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'thumbnail' => MediaType::ExamThumbnail->rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'ชื่อข้อสอบ',
            'description' => 'รายละเอียด',
            'status' => 'สถานะ',
            'duration_minutes' => 'ระยะเวลา',
            'passing_score' => 'คะแนนผ่าน',
            'max_attempts' => 'จำนวนครั้งที่ทำได้',
            'price' => 'ราคา',
            'thumbnail' => 'หน้าปก',
        ];
    }
}
