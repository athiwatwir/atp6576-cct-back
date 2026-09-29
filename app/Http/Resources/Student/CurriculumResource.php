<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Curriculum */
class CurriculumResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $purchased = in_array($this->id, $request->attributes->get('purchased_curriculum_ids', []), true);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'short_description' => $this->short_description,
            'description' => $this->when($request->routeIs('api.curriculums.show'), $this->description),
            'thumbnail_url' => $this->thumbnail_url,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'effective_price' => $this->effective_price,
            'is_featured' => (bool) $this->is_featured,
            'course_count' => (int) ($this->courses_count ?? $this->courses?->count() ?? 0),
            'purchased' => $purchased,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'courses' => $this->when($request->routeIs('api.curriculums.show'), function () {
                return $this->courses->map(fn ($course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'slug' => $course->slug,
                    'thumbnail_url' => $course->thumbnail_url,
                ])->values();
            }),
        ];
    }
}
