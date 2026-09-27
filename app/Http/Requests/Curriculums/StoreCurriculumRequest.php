<?php

namespace App\Http\Requests\Curriculums;

use App\Enums\MediaType;
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
        if ($this->input('price') === '') {
            $this->merge(['price' => 0]);
        }

        if ($this->input('sale_price') === '') {
            $this->merge(['sale_price' => null]);
        }

        if ($this->input('category_id') === '') {
            $this->merge(['category_id' => null]);
        }

        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
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
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999', 'lte:price'],
            'is_featured' => ['nullable', 'boolean'],
            'thumbnail' => MediaType::CurriculumThumbnail->rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อหลักสูตร',
            'category_id' => 'หมวดหมู่',
            'short_description' => 'คำอธิบายสั้น',
            'description' => 'รายละเอียด',
            'status' => 'สถานะ',
            'price' => 'ราคา',
            'sale_price' => 'ราคาลด',
            'is_featured' => 'แนะนำ',
            'thumbnail' => 'รูปปก',
        ];
    }
}
