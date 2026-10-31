<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Document;
use App\Support\DirectUpload;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseChapterDocumentController extends Controller
{
    public function store(Request $request, Course $course, Chapter $chapter): RedirectResponse
    {
        $this->ensureChapterBelongsToCourse($course, $chapter);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => [
                'required_without:upload_token',
                'nullable',
                'file',
                'extensions:'.implode(',', array_keys(DirectUpload::DOCUMENT_TYPES)),
                'max:'.(DirectUpload::maxBytes('document') / 1024),
            ],
            'upload_token' => ['nullable', 'string', 'size:40'],
            'is_free' => ['nullable', 'boolean'],
        ], [
            'file.required_without' => 'กรุณาเลือกไฟล์เอกสาร',
            'file.extensions' => 'รองรับเฉพาะไฟล์ '.DirectUpload::extensionsLabel('document'),
            'file.max' => 'ขนาดไฟล์ต้องไม่เกิน '.DirectUpload::maxLabel('document'),
            'file.uploaded' => 'อัปโหลดไฟล์ไม่สำเร็จ (ไฟล์อาจใหญ่เกินขีดจำกัดของเซิร์ฟเวอร์)',
        ], [
            'title' => 'ชื่อเอกสาร',
            'file' => 'ไฟล์เอกสาร',
        ]);

        if (filled($data['upload_token'] ?? null)) {
            $upload = DirectUpload::consume($data['upload_token'], (int) $request->user()->id, 'document', [
                'scope' => 'course',
                'course_id' => $course->id,
                'chapter_id' => $chapter->id,
            ]);
            $file = [
                'file_path' => $upload['key'],
                'file_name' => $upload['filename'],
                'mime_type' => $upload['mime'],
                'file_size' => $upload['size'],
            ];
        } else {
            $uploaded = $request->file('file');
            $file = [
                'file_path' => MediaStorage::storeDocument($uploaded, $course->id, $chapter->id),
                'file_name' => $uploaded->getClientOriginalName(),
                'mime_type' => $uploaded->getClientMimeType(),
                'file_size' => $uploaded->getSize(),
            ];
        }

        $document = Document::query()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            ...$file,
            'is_free' => (bool) ($data['is_free'] ?? false),
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
        MediaStorage::delete($document->file_path);
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
