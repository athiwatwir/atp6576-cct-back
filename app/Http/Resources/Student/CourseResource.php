<?php

namespace App\Http\Resources\Student;

use App\Models\VideoProgress;
use App\Support\MediaStorage;
use App\Support\PricePlans;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

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
            'item_type' => 'course',
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
            'price_plans' => PricePlans::payload($this->resource),
            'is_featured' => (bool) $this->is_featured,
            'is_trial_available' => (bool) $this->is_trial_available,
            'chapter_count' => (int) ($this->chapters_count ?? 0),
            'purchased' => $purchased,
            'access_expires_at' => $request->attributes->get('access_expires', [])['course'][$this->id] ?? null,
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
            'chapters' => $this->when($request->routeIs('api.courses.show'), function () use ($request) {
                $purchased = in_array($this->id, $request->attributes->get('purchased_course_ids', []), true);
                $trialOpen = (bool) $this->is_trial_available;

                $stored = $request->attributes->get('video_progress');
                $progress = $stored instanceof Collection ? $stored : collect();

                $viewerId = auth('sanctum')->id();

                return $this->chapters->map(function ($chapter) use ($purchased, $trialOpen, $progress, $viewerId) {
                    return [
                        'id' => $chapter->id,
                        'title' => $chapter->title,
                        'description' => $chapter->description,
                        'videos' => $chapter->videos->map(function ($video) use ($purchased, $trialOpen, $progress, $viewerId) {
                            $isTrial = (bool) $video->is_free;
                            $canWatch = $purchased || ($trialOpen && $isTrial);
                            $record = $progress->get($video->id);

                            return [
                                'id' => $video->id,
                                'title' => $video->title,
                                'description' => $video->description,
                                'duration_seconds' => (int) $video->duration_seconds,
                                'is_trial' => $isTrial,
                                'video_url' => $canWatch ? $video->streamUrl($this->id, $viewerId ? (int) $viewerId : null) : null,
                                'progress' => $record instanceof VideoProgress
                                    ? $record->toStudentArray()
                                    : VideoProgress::unwatchedPayload(),
                            ];
                        })->values(),
                        'documents' => $chapter->documents->map(function ($document) use ($purchased, $trialOpen) {
                            $isTrial = (bool) $document->is_free;

                            return $this->documentPayload($document, $purchased || ($trialOpen && $isTrial));
                        })->values(),
                        'exams' => $this->assessments
                            ->filter(fn ($exam) => (int) $exam->pivot->chapter_id === (int) $chapter->id)
                            ->map(fn ($exam) => $this->examPayload($exam))
                            ->values(),
                    ];
                })->values();
            }),
            'exams' => $this->when($request->routeIs('api.courses.show'), function () {
                $chapterIds = $this->chapters->pluck('id')->map(fn ($id) => (int) $id);

                return $this->assessments
                    ->filter(function ($exam) use ($chapterIds) {
                        $chapterId = (int) $exam->pivot->chapter_id;

                        return $chapterId === 0 || ! $chapterIds->contains($chapterId);
                    })
                    ->map(fn ($exam) => $this->examPayload($exam))
                    ->values();
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documentPayload(object $document, bool $canOpen): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description,
            'file_name' => $document->file_name,
            'file_size' => $document->file_size !== null ? (int) $document->file_size : null,
            'is_trial' => (bool) $document->is_free,
            'file_url' => $canOpen ? MediaStorage::url($document->file_path) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function examPayload(object $exam): array
    {
        return [
            'id' => $exam->id,
            'name' => $exam->title,
            'slug' => $exam->slug,
            'description' => $exam->description,
            'duration_minutes' => $exam->duration_minutes !== null ? (int) $exam->duration_minutes : null,
            'question_count' => (int) ($exam->questions_count ?? 0),
            'chapter_id' => $exam->pivot->chapter_id ? (int) $exam->pivot->chapter_id : null,
        ];
    }
}
