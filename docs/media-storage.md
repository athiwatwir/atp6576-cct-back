# Media Storage (Cloudflare R2 + WebP)

Helper กลางของโปรเจคอยู่ที่ `App\Support\MediaStorage`  
ใช้แปลงรูปเป็น WebP และอัปโหลดไฟล์ขึ้น Cloudflare R2 (fallback เป็น `public` ถ้ายังไม่ตั้งค่า R2)

## การตั้งค่า `.env`

```env
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
R2_URL=https://YOUR_PUBLIC_R2_OR_CDN_DOMAIN
R2_REGION=auto
R2_USE_PATH_STYLE_ENDPOINT=false
```

Disk config: `config/filesystems.php` → `disks.r2`

## โครงสร้างโฟลเดอร์บน R2

```text
courses/{course_id}/thumbnails/{course_id}.webp
courses/{course_id}/chapters/{chapter_id}/videos/{video_id}.{ext}
instructors/{instructor_id}/{instructor_id}.webp
exams/{exam_id}/{exam_id}.webp
books/{book_id}/{book_id}.webp
documents/{course_id}/{chapter_id}/...   # เอกสารยังใช้ local public ตามเดิม
```

## API ที่เรียกใช้ได้ทั้งโปรเจค

| Method | คำอธิบาย |
|--------|----------|
| `MediaStorage::disk()` | คืน `'r2'` หรือ `'public'` |
| `MediaStorage::convertImageToWebp($file, $quality = 82)` | แปลงรูปเป็น binary WebP |
| `MediaStorage::store($file, $directory)` | อัปโหลดไฟล์ทั่วไป (ชื่อเป็น UUID) |
| `MediaStorage::storeCourseThumbnail($file, $courseId)` | แปลง WebP + อัปโหลดปกคอร์ส |
| `MediaStorage::storeInstructorImage($file, $instructorId)` | แปลง WebP + อัปโหลดรูปครู |
| `MediaStorage::storeExamThumbnail($file, $examId)` | แปลง WebP + อัปโหลดปกข้อสอบขายแยก |
| `MediaStorage::storeBookThumbnail($file, $bookId)` | แปลง WebP + อัปโหลดปกหนังสือ |
| `MediaStorage::storeCourseVideo($file, $courseId, $chapterId, $videoId)` | อัปโหลดวิดีโอบทเรียน (ชื่อ = video_id) |
| `MediaStorage::url($path)` | สร้าง public URL |
| `MediaStorage::delete($path)` | ลบไฟล์ |

## ตัวอย่างการใช้งาน

### ปกคอร์ส (มีอยู่แล้วใน `CourseController`)

```php
use App\Support\MediaStorage;

$path = MediaStorage::storeCourseThumbnail(
    $request->file('thumbnail'),
    $course->id
);
// => courses/12/thumbnails/12.webp
```

### แปลง WebP แล้วอัปโหลด path เอง

```php
use App\Support\MediaStorage;
use Illuminate\Support\Facades\Storage;

$webp = MediaStorage::convertImageToWebp($request->file('avatar'));
$path = "users/{$userId}/avatar.webp";

Storage::disk(MediaStorage::disk())->put($path, $webp, [
    'visibility' => 'public',
    'ContentType' => 'image/webp',
]);
```

### อัปโหลดวิดีโอบทเรียน

```php
$path = MediaStorage::storeCourseVideo(
    $request->file('video_file'),
    $courseId,
    $chapterId,
    $videoId
);
// => courses/12/chapters/5/videos/88.mp4
```

### หน้าปกข้อสอบขายแยก

```php
$path = MediaStorage::storeExamThumbnail(
    $request->file('thumbnail'),
    $exam->id
);
// => exams/15/15.webp
```

### แสดง URL / ลบไฟล์

```php
$url = MediaStorage::url($course->thumbnail);
MediaStorage::delete($course->thumbnail);
```

## หมายเหตุ

- ต้องมี PHP GD พร้อม `imagewebp`
- ปกคอร์ส / ปกข้อสอบ / รูปครู: รับ JPG/PNG/WebP/GIF สูงสุด 2MB แล้วแปลงเป็น WebP เสมอ
- อัปเดตรูปปกจะทับไฟล์เดิมที่ path เดิม (`{id}.webp`)
- ถ้า R2 ยังไม่ครบ จะใช้ disk `public` แทนอัตโนมัติ
- วิดีโอ: validation สูงสุด 500MB — ต้องตั้ง PHP ให้รองรับด้วย เช่น
  - `upload_max_filesize=512M`
  - `post_max_size=520M`
  - หรือรันด้วย `composer run serve`
