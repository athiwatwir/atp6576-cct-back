<?php

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'excerpt' => $this->filled('excerpt') ? $this->input('excerpt') : null,
            'content' => $this->filled('content') ? $this->input('content') : null,
            'published_at' => $this->filled('published_at') ? $this->input('published_at') : null,
            'expired_at' => $this->filled('expired_at') ? $this->input('expired_at') : null,
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:200000'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'published_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'หัวข้อ',
            'excerpt' => 'คำเกริ่น',
            'content' => 'เนื้อหา',
            'status' => 'สถานะ',
            'published_at' => 'เริ่มเผยแพร่',
            'expired_at' => 'สิ้นสุด',
            'image' => 'รูปปก',
        ];
    }
}
