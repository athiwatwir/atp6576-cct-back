<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exams\StoreExamRequest;
use App\Http\Requests\Exams\UpdateExamRequest;
use App\Models\Assessment;
use App\Models\Question;
use App\Support\DocumentSequence;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $exams = Assessment::query()
            ->independent()
            ->where('type', 'primary_exam')
            ->withCount('questions')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.exams.index', [
            'title' => 'ข้อสอบ (ขายแยก)',
            'exams' => $exams,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.exams.create', [
            'title' => 'เพิ่มข้อสอบ',
        ]);
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();

        unset($data['thumbnail'], $data['remove_thumbnail']);

        $exam = Assessment::query()->create([
            'code' => DocumentSequence::nextExam(),
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'] ?? null,
            'type' => 'primary_exam',
            'status' => $data['status'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'passing_score' => $data['passing_score'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? null,
            'is_independent' => true,
            'price' => $data['price'] ?? 0,
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        if ($request->hasFile('thumbnail')) {
            $exam->update([
                'thumbnail' => MediaStorage::storeExamThumbnail(
                    $request->file('thumbnail'),
                    $exam->id
                ),
            ]);
        }

        return redirect()
            ->route('exams.show', $exam)
            ->with('success', 'เพิ่มข้อสอบเรียบร้อยแล้ว');
    }

    public function show(Assessment $assessment): View
    {
        $this->ensureIndependentExam($assessment);

        $assessment->load(['questions.choices']);

        return view('pages.exams.show', [
            'title' => $assessment->title,
            'exam' => $assessment,
        ]);
    }

    public function edit(Assessment $assessment): View
    {
        $this->ensureIndependentExam($assessment);

        return view('pages.exams.edit', [
            'title' => 'แก้ไขข้อสอบ',
            'exam' => $assessment,
        ]);
    }

    public function update(UpdateExamRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->ensureIndependentExam($assessment);

        $data = $request->validated();

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => 'primary_exam',
            'status' => $data['status'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'passing_score' => $data['passing_score'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? null,
            'is_independent' => true,
            'price' => $data['price'] ?? 0,
            'published_at' => $data['status'] === 'published'
                ? ($assessment->published_at ?? now())
                : ($data['status'] === 'draft' ? null : $assessment->published_at),
        ];

        if ($assessment->title !== $data['title']) {
            $payload['slug'] = $this->uniqueSlug($data['title'], $assessment->id);
        }

        if ($request->boolean('remove_thumbnail') && $assessment->thumbnail) {
            MediaStorage::delete($assessment->thumbnail);
            $payload['thumbnail'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            $newPath = MediaStorage::examThumbnailPath($assessment->id);

            if ($assessment->thumbnail && $assessment->thumbnail !== $newPath) {
                MediaStorage::delete($assessment->thumbnail);
            }

            $payload['thumbnail'] = MediaStorage::storeExamThumbnail(
                $request->file('thumbnail'),
                $assessment->id
            );
        }

        unset($data['thumbnail'], $data['remove_thumbnail']);

        $assessment->update($payload);

        return redirect()
            ->route('exams.show', $assessment)
            ->with('success', 'บันทึกข้อสอบเรียบร้อยแล้ว');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->ensureIndependentExam($assessment);

        MediaStorage::delete($assessment->thumbnail);

        $assessment->questions()->each(function (Question $question) {
            $question->delete();
        });

        $assessment->courses()->detach();
        $assessment->delete();

        return redirect()
            ->route('exams.index')
            ->with('success', 'ลบข้อสอบเรียบร้อยแล้ว');
    }

    private function ensureIndependentExam(Assessment $assessment): void
    {
        abort_unless($assessment->is_independent && $assessment->type === 'primary_exam', 404);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'exam';
        $slug = $base;
        $counter = 1;

        while (
            Assessment::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
