<?php

namespace App\Enums;

enum MediaType: string
{
    case CourseThumbnail = 'course_thumbnail';
    case CourseBanner = 'course_banner';
    case CourseVideo = 'course_video';
    case InstructorImage = 'instructor_image';
    case ExamThumbnail = 'exam_thumbnail';
    case QuestionImage = 'question_image';
    case BookThumbnail = 'book_thumbnail';
    case BookGallery = 'book_gallery';
    case BookVideo = 'book_video';
    case CurriculumThumbnail = 'curriculum_thumbnail';

    /**
     * @return array<int, mixed>
     */
    public function rules(): array
    {
        return match ($this) {
            self::CourseThumbnail, self::InstructorImage, self::ExamThumbnail, self::BookThumbnail, self::CurriculumThumbnail => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            self::BookGallery => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            self::QuestionImage => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            self::CourseBanner => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            self::CourseVideo, self::BookVideo => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:512000'],
        };
    }
}
