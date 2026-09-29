<?php

namespace App\Http\Resources\Student;

use App\Enums\AccountStatus;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'enrollments' => fn ($query) => $query
                ->where('status', 'active')
                ->with([
                    'course:id,name,slug',
                    'curriculum:id,name,slug',
                ]),
        ]);

        $courses = [];
        $curriculums = [];

        foreach ($this->enrollments as $enrollment) {
            if ($enrollment->course_id && $enrollment->course && ! $enrollment->curriculum_id && ! $enrollment->assessment_id) {
                $courses[] = [
                    'id' => $enrollment->course->id,
                    'name' => $enrollment->course->name,
                    'slug' => $enrollment->course->slug,
                ];
            }

            if ($enrollment->curriculum_id && $enrollment->curriculum && ! $enrollment->course_id && ! $enrollment->assessment_id) {
                $curriculums[] = [
                    'id' => $enrollment->curriculum->id,
                    'name' => $enrollment->curriculum->name,
                    'slug' => $enrollment->curriculum->slug,
                ];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => MediaStorage::url($this->avatar),
            'status' => $this->status,
            'status_label' => AccountStatus::labelFor($this->status),
            'learning' => [
                'courses' => $courses,
                'curriculums' => $curriculums,
            ],
        ];
    }
}
