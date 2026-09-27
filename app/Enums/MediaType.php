<?php

namespace App\Enums;

enum MediaType: string
{
    case CourseThumbnail = 'course_thumbnail';
    case CourseBanner = 'course_banner';
    case CourseVideo = 'course_video';
    case InstructorImage = 'instructor_image';
    case ExamThumbnail = 'exam_thumbnail';
    case BookThumbnail = 'book_thumbnail';

    /**
     * @return array<int, mixed>
     */
    public function rules(): array
    {
        return match ($this) {
            self::CourseThumbnail, self::InstructorImage, self::ExamThumbnail, self::BookThumbnail => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            self::CourseBanner => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            self::CourseVideo => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:512000'],
        };
    }
}
