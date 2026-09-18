<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['category_id', 'subject_id', 'instructor_id', 'sale_price'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_trial_available' => $this->boolean('is_trial_available'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'is_featured' => ['boolean'],
            'is_trial_available' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sale_price.lte' => 'ราคาลดต้องไม่สูงกว่าราคาปกติ',
        ];
    }
}
