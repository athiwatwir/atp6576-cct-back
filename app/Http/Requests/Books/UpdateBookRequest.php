<?php

namespace App\Http\Requests\Books;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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

        if ($this->input('stock') === '') {
            $this->merge(['stock' => null]);
        }

        $this->merge([
            'remove_thumbnail' => $this->boolean('remove_thumbnail'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999', 'lte:price'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'thumbnail' => MediaType::BookThumbnail->rules(),
            'remove_thumbnail' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อหนังสือ',
            'description' => 'รายละเอียด',
            'status' => 'สถานะ',
            'price' => 'ราคา',
            'sale_price' => 'ราคาลด',
            'stock' => 'จำนวนคงเหลือ',
            'thumbnail' => 'ปกหนังสือ',
        ];
    }
}
