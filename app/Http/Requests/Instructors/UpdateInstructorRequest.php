<?php

namespace App\Http\Requests\Instructors;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstructorRequest extends FormRequest
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

        $this->merge([
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $instructorId = $this->route('instructor')?->id;

        return [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::unique('instructors', 'user_id')
                    ->ignore($instructorId)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:200'],
            'bio' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'image' => MediaType::InstructorImage->rules(),
            'remove_image' => ['boolean'],
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
