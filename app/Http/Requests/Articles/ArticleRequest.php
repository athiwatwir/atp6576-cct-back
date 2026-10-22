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
            'seo_title' => $this->filled('seo_title') ? $this->input('seo_title') : null,
            'seo_description' => $this->filled('seo_description') ? $this->input('seo_description') : null,
            'seo_keywords' => $this->filled('seo_keywords') ? $this->input('seo_keywords') : null,
            'content' => $this->filled('content') ? $this->input('content') : null,
            'remove_image' => $this->boolean('remove_image'),
            'course_ids' => array_values(array_filter((array) $this->input('course_ids', []))),
            'curriculum_ids' => array_values(array_filter((array) $this->input('curriculum_ids', []))),
            'assessment_ids' => array_values(array_filter((array) $this->input('assessment_ids', []))),
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
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:200000'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'distinct', 'exists:courses,id'],
            'curriculum_ids' => ['nullable', 'array'],
            'curriculum_ids.*' => ['integer', 'distinct', 'exists:curriculums,id'],
            'assessment_ids' => ['nullable', 'array'],
            'assessment_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('assessments', 'id')->where(fn ($query) => $query->where('type', 'primary_exam')),
            ],
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
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
            'seo_keywords' => 'SEO keywords',
            'content' => 'เนื้อหา',
            'status' => 'สถานะ',
            'image' => 'รูปปก',
            'course_ids' => 'วิชาที่เกี่ยวข้อง',
            'curriculum_ids' => 'หลักสูตรที่เกี่ยวข้อง',
            'assessment_ids' => 'ข้อสอบที่เกี่ยวข้อง',
        ];
    }
}
