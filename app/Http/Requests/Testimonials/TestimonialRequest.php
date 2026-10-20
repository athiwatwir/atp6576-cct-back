<?php

namespace App\Http\Requests\Testimonials;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'content' => $this->filled('content') ? $this->input('content') : null,
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $imageRules = MediaType::TestimonialCover->rules();
        if ($this->isMethod('post')) {
            $imageRules[0] = 'nullable';
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image' => $imageRules,
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ชื่อผู้รีวิว',
            'content' => 'ข้อความรีวิว',
            'status' => 'สถานะ',
            'sort_order' => 'ลำดับ',
            'image' => 'หน้าปก',
        ];
    }
}
