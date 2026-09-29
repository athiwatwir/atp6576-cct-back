<?php

namespace App\Http\Requests\Banners;

use App\Enums\BannerPlacement;
use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'link_url' => $this->filled('link_url') ? $this->input('link_url') : null,
            'excerpt' => $this->filled('excerpt') ? $this->input('excerpt') : null,
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
            'link_url' => ['nullable', 'url', 'max:1000'],
            'placement' => ['required', Rule::in(array_column(BannerPlacement::cases(), 'value'))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'published_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('status') !== ContentStatus::Published->value) {
                return;
            }

            $banner = $this->route('banner');
            $hasExisting = $banner && $banner->image && ! $this->boolean('remove_image');

            if (! $this->hasFile('image') && ! $hasExisting) {
                $validator->errors()->add('image', 'แบนเนอร์ที่เผยแพร่ต้องมีรูป');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'ชื่อแบนเนอร์',
            'excerpt' => 'คำอธิบาย',
            'link_url' => 'ลิงก์',
            'placement' => 'ตำแหน่ง',
            'sort_order' => 'ลำดับ',
            'status' => 'สถานะ',
            'published_at' => 'เริ่มแสดง',
            'expired_at' => 'สิ้นสุด',
            'image' => 'รูปแบนเนอร์',
        ];
    }
}
