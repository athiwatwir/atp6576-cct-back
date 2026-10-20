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
exams/{exam_id}/questions/{question_id}.webp
books/{book_id}/{book_id}.webp
books/{book_id}/gallery/{uuid}.webp
books/{book_id}/video/{book_id}.{ext}
testimonials/{testimonial_id}/{testimonial_id}.webp
banners/{content_id}/{content_id}.webp
articles/{content_id}/{content_id}.webp     # รูปปกบทความ
articles/body/{uuid}.webp                   # รูปในเนื้อหาบทความ (GIF เก็บไฟล์เดิม .gif)
avatars/{user_id}/{user_id}.webp            # รูปโปรไฟล์นักเรียนจาก API
payments/{order_id}/slips/{uuid}.{ext}      # สลิปโอนเงิน เก็บไฟล์เดิม (รองรับ PDF)
documents/{course_id}/{chapter_id}/...   # เอกสารยังใช้ local public ตามเดิม
```

รูปทุกชนิดที่อัปโหลดผ่านแอดมินหรือ API แปลงเป็น WebP ก่อนเก็บ ยกเว้น GIF ในเนื้อหาบทความ (เก็บเดิมเพื่อให้ภาพเคลื่อนไหวยังเล่นได้) และสลิปโอนเงิน (เป็นหลักฐาน และรับ PDF) รูปที่ใช้ path คงที่ (เช่น `banners/{id}/{id}.webp`) ต่อท้าย URL ด้วย `?v={updated_at}` เพื่อให้ CDN ไม่แสดงรูปเก่าหลังเปลี่ยนรูป

แบนเนอร์และบทความที่อัปโหลดก่อนวันที่ 19 ต.ค. 2026 เคยเก็บเป็นไฟล์เดิม (`banners/{id}/{uuid}.png` ฯลฯ) ระบบแปลงรายการที่ยังใช้งาน (#11–#14) เป็น WebP แล้ว ไฟล์เดิมยังอยู่บน R2 ลบได้เมื่อแน่ใจว่าไม่ต้องย้อนกลับ

## API ที่เรียกใช้ได้ทั้งโปรเจค

| Method | คำอธิบาย |
|--------|----------|
| `MediaStorage::disk()` | คืน `'r2'` หรือ `'public'` |
| `MediaStorage::convertImageToWebp($file, $quality = 82)` | แปลงรูปเป็น binary WebP |
| `MediaStorage::store($file, $directory)` | อัปโหลดไฟล์ทั่วไป (ชื่อเป็น UUID) |
| `MediaStorage::storeCourseThumbnail($file, $courseId)` | แปลง WebP + อัปโหลดปกคอร์ส |
| `MediaStorage::storeInstructorImage($file, $instructorId)` | แปลง WebP + อัปโหลดรูปครู |
| `MediaStorage::storeExamThumbnail($file, $examId)` | แปลง WebP + อัปโหลดปกข้อสอบขายแยก |
| `MediaStorage::storeQuestionImage($file, $examId, $questionId)` | แปลง WebP + อัปโหลดรูปประกอบคำถามข้อสอบ |
| `MediaStorage::storeBookThumbnail($file, $bookId)` | แปลง WebP + อัปโหลดปกหนังสือ |
| `MediaStorage::storeBookImage($file, $bookId)` | แปลง WebP + อัปโหลดรูปเพิ่มเติมของหนังสือ |
| `MediaStorage::storeBookVideo($file, $bookId)` | อัปโหลดวิดีโอของหนังสือ (มีได้หนึ่งไฟล์) |
| `MediaStorage::storeCourseVideo($file, $courseId, $chapterId, $videoId)` | อัปโหลดวิดีโอบทเรียน (ชื่อ = video_id) |
| `MediaStorage::storeTestimonialCover($file, $testimonialId)` | แปลง WebP + อัปโหลดหน้าปกรีวิว |
| `MediaStorage::storeContentImage($file, $type, $contentId)` | แปลง WebP + อัปโหลดรูปแบนเนอร์ / ปกบทความ (`banners/{id}/{id}.webp`, `articles/{id}/{id}.webp`) |
| `MediaStorage::storeAvatar($file, $userId)` | แปลง WebP + อัปโหลดรูปโปรไฟล์ |
| `MediaStorage::storeImage($file, $directory)` | รูปทั่วไปชื่อ UUID แปลง WebP ยกเว้น GIF |
| `MediaStorage::url($path, $version = null)` | สร้าง public URL ใส่ `$version` (เช่น timestamp ของ `updated_at`) เพื่อต่อท้าย `?v=` |
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

### รูปประกอบคำถามข้อสอบ

หนึ่งคำถามมีได้หนึ่งรูป เก็บที่ `questions.image` ฟอร์มเพิ่มและแก้ไขคำถามของข้อสอบขายแยก ข้อสอบในคอร์ส และแบบฝึกหัดในคอร์สอัปโหลดไฟล์นี้ รับ JPG/PNG/WebP/GIF สูงสุด 4MB แล้วแปลงเป็น WebP อัปโหลดใหม่ทับ path เดิม ลบคำถาม ลบข้อสอบ หรือลบแบบทดสอบในคอร์สจะลบไฟล์ด้วย

```php
$path = MediaStorage::storeQuestionImage(
    $request->file('image'),
    $exam->id,
    $question->id
);
// => exams/15/questions/88.webp
```

API นักเรียนยังไม่ส่งรูปคำถาม เพราะข้อสอบไม่อยู่ใน storefront API

### รูปเพิ่มเติมและวิดีโอของหนังสือ

ปกหนังสือยังอยู่ที่ `products.thumbnail` รูปเพิ่มเติมและวิดีโออยู่ที่ตาราง `product_media` (`kind` เป็น `image` หรือ `video`)

- รูปเพิ่มเติมได้ไม่เกิน 20 รูป รูปละไม่เกิน 4MB แปลงเป็น WebP
- วิดีโอได้หนึ่งไฟล์ MP4, WebM หรือ MOV ไม่เกิน 500MB อัปโหลดใหม่แทนที่ไฟล์เดิม
- หน้ารายละเอียดหนังสือใน API ส่ง `images` และ `video_url` ดู `docs/student-api.md`

### แสดง URL / ลบไฟล์

```php
$url = MediaStorage::url($course->thumbnail, $course->updated_at?->getTimestamp());
MediaStorage::delete($course->thumbnail);
```

URL จาก model เช่น `thumbnail_url` ใส่ `?v={updated_at}` ให้แล้ว เพื่อไม่ให้ CDN/เบราว์เซอร์ค้างไฟล์หรือ 404 เก่าเมื่ออัปโหลดทับ path เดิม

ดิสก์ `r2` ตั้ง `throw` เป็นจริง ถ้าอัปโหลดไม่สำเร็จระบบจะแจ้ง error แทนการบันทึก path ว่างเปล่า

## หมายเหตุ

- ต้องมี PHP GD พร้อม `imagewebp`
- ปกคอร์ส / ปกข้อสอบ / ปกหนังสือ / รูปครู: รับ JPG/PNG/WebP/GIF สูงสุด 2MB แล้วแปลงเป็น WebP เสมอ
- รูปคำถามข้อสอบและรูปเพิ่มเติมของหนังสือ: สูงสุด 4MB แล้วแปลงเป็น WebP
- อัปเดตรูปปกจะทับไฟล์เดิมที่ path เดิม (`{id}.webp`)
- ถ้า R2 ยังไม่ครบ จะใช้ disk `public` แทนอัตโนมัติ
- บน Windows ต้องตั้ง `curl.cainfo` / `openssl.cafile` ใน `php.ini` ไม่เช่นนั้นอัปโหลด R2 จะล้มเหลวด้วย SSL และต้องรีสตาร์ท `php artisan serve` หลังแก้ `php.ini`
- อัปโหลดขึ้น R2 ไม่ส่ง S3 ACL เพราะ R2 ไม่รองรับ การเปิดสาธารณะใช้ custom domain (`R2_URL`)
- วิดีโอ: validation สูงสุด 500MB — ต้องตั้ง PHP ให้รองรับด้วย เช่น
  - `upload_max_filesize=512M`
  - `post_max_size=520M`
  - หรือรันด้วย `composer run serve`
