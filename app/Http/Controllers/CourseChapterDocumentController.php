<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CourseChapterDocumentController extends Controller
{
    public function store(Request $request, Course $course, Chapter $chapter): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
            'is_free' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::query()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'is_free' => (bool) ($data['is_free'] ?? false),
            'status' => $data['status'],
        ]);

        $sortOrder = ((int) $chapter->documents()->max('chapter_documents.sort_order')) + 1;

        $chapter->documents()->attach($document->id, [
            'sort_order' => $sortOrder,
        ]);

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'เพิ่มเอกสารเรียบร้อยแล้ว');
    }

    public function destroy(Course $course, Chapter $chapter, Document $document): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);
        abort_unless($chapter->documents()->where('documents.id', $document->id)->exists(), 404);

        $chapter->documents()->detach($document->id);

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('courses.show', ['course' => $course, 'tab' => 'chapters', 'chapter' => $chapter->id])
            ->with('success', 'ลบเอกสารเรียบร้อยแล้ว');
    }

    private function ensureChapterBelongsToCourse(Course $course, Chapter $chapter): void
    {
        abort_unless($chapter->course_id === $course->id, 404);
    }
}
