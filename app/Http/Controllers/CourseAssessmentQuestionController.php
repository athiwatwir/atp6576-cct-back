<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseAssessmentQuestionController extends Controller
{
    public function store(Request $request, Course $course, Assessment $assessment): RedirectResponse
    {
        $this->ensureAssessmentBelongsToCourse($course, $assessment);

        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'choices' => ['required', 'array', 'min:2'],
            'choices.*.choice_text' => ['nullable', 'string'],
            'correct_index' => ['required', 'integer', 'min:0'],
        ]);

        $choices = $this->normalizedChoices($data['choices'], (int) $data['correct_index']);

        $sortOrder = ((int) $assessment->questions()->max('sort_order')) + 1;

        DB::transaction(function () use ($assessment, $data, $choices, $sortOrder) {
            $question = $assessment->questions()->create([
                'question_text' => $data['question_text'],
                'question_type' => 'single_choice',
                'explanation' => $data['explanation'] ?? null,
                'points' => $data['points'] ?? 1,
                'sort_order' => $sortOrder,
                'status' => 'active',
            ]);

            foreach ($choices as $index => $choice) {
                $question->choices()->create([
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => $choice['is_correct'],
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return $this->redirectBack($course, $assessment)
            ->with('success', 'เพิ่มคำถามเรียบร้อยแล้ว');
    }

    public function update(Request $request, Course $course, Assessment $assessment, Question $question): RedirectResponse
    {
        $this->ensureAssessmentBelongsToCourse($course, $assessment);
        $this->ensureQuestionBelongsToAssessment($assessment, $question);

        $data = $request->validate([
            'question_text' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'choices' => ['required', 'array', 'min:2'],
            'choices.*.choice_text' => ['nullable', 'string'],
            'correct_index' => ['required', 'integer', 'min:0'],
        ]);

        $choices = $this->normalizedChoices($data['choices'], (int) $data['correct_index']);

        DB::transaction(function () use ($question, $data, $choices) {
            $question->update([
                'question_text' => $data['question_text'],
                'explanation' => $data['explanation'] ?? null,
                'points' => $data['points'] ?? $question->points,
            ]);

            $question->choices()->delete();

            foreach ($choices as $index => $choice) {
                $question->choices()->create([
                    'choice_text' => $choice['choice_text'],
                    'is_correct' => $choice['is_correct'],
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return $this->redirectBack($course, $assessment)
            ->with('success', 'บันทึกคำถามเรียบร้อยแล้ว');
    }

    public function destroy(Course $course, Assessment $assessment, Question $question): RedirectResponse
    {
        $this->ensureAssessmentBelongsToCourse($course, $assessment);
        $this->ensureQuestionBelongsToAssessment($assessment, $question);

        $question->delete();

        return $this->redirectBack($course, $assessment)
            ->with('success', 'ลบคำถามเรียบร้อยแล้ว');
    }

    /**
     * @param  array<int, array{choice_text?: string|null}>  $rawChoices
     * @return array<int, array{choice_text: string, is_correct: bool}>
     */
    private function normalizedChoices(array $rawChoices, int $correctIndex): array
    {
        if (! array_key_exists($correctIndex, $rawChoices) || blank($rawChoices[$correctIndex]['choice_text'] ?? null)) {
            throw ValidationException::withMessages([
                'correct_index' => 'กรุณาเลือกเฉลยที่เป็นตัวเลือกที่มีข้อความ',
            ]);
        }

        $choices = collect($rawChoices)
            ->map(fn (array $choice, int $index) => [
                'choice_text' => trim((string) ($choice['choice_text'] ?? '')),
                'is_correct' => $index === $correctIndex,
            ])
            ->filter(fn (array $choice) => $choice['choice_text'] !== '')
            ->values()
            ->all();

        if (count($choices) < 2) {
            throw ValidationException::withMessages([
                'choices' => 'ต้องมีตัวเลือกอย่างน้อย 2 ข้อ',
            ]);
        }

        if (! collect($choices)->contains(fn (array $choice) => $choice['is_correct'])) {
            throw ValidationException::withMessages([
                'correct_index' => 'กรุณาเลือกเฉลยคำตอบ',
            ]);
        }

        return $choices;
    }

    private function redirectBack(Course $course, Assessment $assessment): RedirectResponse
    {
        $tab = $assessment->type === 'exam' ? 'exams' : 'quizzes';

        return redirect()->route('courses.show', [
            'course' => $course,
            'tab' => $tab,
            'assessment' => $assessment->id,
        ]);
    }

    private function ensureAssessmentBelongsToCourse(Course $course, Assessment $assessment): void
    {
        abort_unless($course->assessments()->where('assessments.id', $assessment->id)->exists(), 404);
    }

    private function ensureQuestionBelongsToAssessment(Assessment $assessment, Question $question): void
    {
        abort_unless($question->assessment_id === $assessment->id, 404);
    }
}
