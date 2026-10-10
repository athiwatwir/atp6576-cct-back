<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Assessment */
class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $purchased = in_array($this->id, $request->attributes->get('purchased_exam_ids', []), true);

        return [
            'item_type' => 'exam',
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->title,
            'description' => $this->description,
            'thumbnail_url' => $this->thumbnail_url,
            'price' => (float) $this->price,
            'sale_price' => null,
            'effective_price' => $this->effective_price,
            'duration_minutes' => $this->duration_minutes,
            'question_count' => (int) ($this->questions_count ?? 0),
            'purchased' => $purchased,
        ];
    }
}
