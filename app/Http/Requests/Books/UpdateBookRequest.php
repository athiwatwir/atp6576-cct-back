<?php

namespace App\Http\Requests\Books;

use App\Enums\MediaType;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'remove_video' => $this->boolean('remove_video'),
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
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => MediaType::BookGallery->rules(),
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'video' => MediaType::BookVideo->rules(),
            'remove_video' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $book = $this->route('product');

            if (! $book instanceof Product) {
                return;
            }

            $remove = array_map('intval', (array) $this->input('remove_images', []));
            $kept = $book->images()->when($remove !== [], fn ($query) => $query->whereNotIn('id', $remove))->count();
            $incoming = count($this->file('images') ?? []);

            if ($kept + $incoming > 20) {
                $validator->errors()->add('images', 'รูปภาพรวมกันได้ไม่เกิน 20 รูป');
            }
        });
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
            'images' => 'รูปภาพ',
            'images.*' => 'รูปภาพ',
            'video' => 'วิดีโอ',
        ];
    }
}
