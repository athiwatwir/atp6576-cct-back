<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Course */
class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $purchased = in_array($this->id, $request->attributes->get('purchased_course_ids', []), true);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->name,
            'short_description' => $this->short_description,
            'description' => $this->when($request->routeIs('api.courses.show'), $this->description),
            'thumbnail_url' => $this->thumbnail_url,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'effective_price' => $this->effective_price,
            'is_featured' => (bool) $this->is_featured,
            'is_trial_available' => (bool) $this->is_trial_available,
            'chapter_count' => (int) ($this->chapters_count ?? 0),
            'purchased' => $purchased,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'subject' => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'slug' => $this->subject->slug,
            ] : null,
            'instructor' => $this->instructor ? [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'slug' => $this->instructor->slug,
            ] : null,
        ];
    }
}
