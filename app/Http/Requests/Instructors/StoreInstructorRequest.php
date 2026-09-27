<?php

namespace App\Http\Requests\Instructors;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('user_id') === '') {
            $this->merge(['user_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::unique('instructors', 'user_id')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:200'],
            'bio' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'image' => MediaType::InstructorImage->rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'บัญชีนี้ถูกผูกกับครูผู้สอนคนอื่นแล้ว',
        ];
    }
}
