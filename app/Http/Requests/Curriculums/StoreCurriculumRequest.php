<?php

namespace App\Http\Requests\Curriculums;

use App\Enums\MediaType;
use App\Support\PricePlans;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('category_id') === '') {
            $this->merge(['category_id' => null]);
        }

        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'plans' => PricePlans::normalizeInput($this->input('plans')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'is_featured' => ['nullable', 'boolean'],
            'thumbnail' => MediaType::CurriculumThumbnail->rules(),
            ...PricePlans::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return PricePlans::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อหลักสูตร',
            'category_id' => 'ระดับชั้น',
            'short_description' => 'คำอธิบายสั้น',
            'description' => 'รายละเอียด',
            'status' => 'สถานะ',
            'is_featured' => 'แนะนำ',
            'thumbnail' => 'รูปปก',
            ...PricePlans::attributes(),
        ];
    }
}
