<?php

namespace App\Http\Resources\Student;

use App\Enums\AccountStatus;
use App\Models\LearningProgress;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Model;
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
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->with([
                    'course:id,name,slug,thumbnail,status',
                    'curriculum:id,name,slug,thumbnail',
                    'assessment:id,title,slug,thumbnail',
                ]),
        ]);

        $courses = [];
        $curriculums = [];
        $exams = [];

        foreach ($this->enrollments as $enrollment) {
            $expiresAt = $enrollment->expires_at?->toIso8601String();

            if ($enrollment->course) {
                $courses[$enrollment->course->id] = $this->learningItem($enrollment->course, $enrollment->course->name, $expiresAt);
            }

            if ($enrollment->curriculum) {
                $curriculums[$enrollment->curriculum->id] = $this->learningItem($enrollment->curriculum, $enrollment->curriculum->name, $expiresAt);
            }

            if ($enrollment->assessment) {
                $exams[$enrollment->assessment->id] = $this->learningItem($enrollment->assessment, $enrollment->assessment->title, $expiresAt);
            }
        }

        $progress = $courses === [] ? collect() : LearningProgress::query()
            ->where('user_id', $this->id)
            ->whereIn('course_id', array_keys($courses))
            ->get()
            ->keyBy('course_id');

        foreach ($courses as $courseId => $item) {
            $record = $progress->get($courseId);
            $courses[$courseId]['progress'] = $record ? [
                'percent' => (float) $record->progress_percent,
                'completed_items' => (int) $record->completed_items,
                'total_items' => (int) $record->total_items,
                'completed_at' => $record->completed_at?->toIso8601String(),
                'last_activity_at' => $record->last_activity_at?->toIso8601String(),
            ] : null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phone,
            'school_name' => $this->school_name,
            'school_province' => $this->school_province,
            'address' => [
                'address_line1' => $this->address_line1,
                'subdistrict' => $this->subdistrict,
                'district' => $this->district,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
            ],
            'avatar_url' => MediaStorage::url($this->avatar, $this->updated_at?->getTimestamp()),
            'status' => $this->status,
            'status_label' => AccountStatus::labelFor($this->status),
            'email_verified' => $this->email_verified_at !== null,
            'learning' => [
                'courses' => array_values($courses),
                'curriculums' => array_values($curriculums),
                'exams' => array_values($exams),
            ],
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string, thumbnail_url: string|null, expires_at: string|null}
     */
    private function learningItem(Model $item, string $name, ?string $expiresAt): array
    {
        return [
            'id' => $item->id,
            'name' => $name,
            'slug' => $item->slug,
            'thumbnail_url' => $item->thumbnail_url,
            'expires_at' => $expiresAt,
        ];
    }
}
