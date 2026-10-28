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
courses/{course_id}/chapters/{chapter_id}/videos/{uuid}.{ext}      # อัปโหลดตรงขึ้น R2 (ปัจจุบัน)
courses/{course_id}/chapters/{chapter_id}/videos/{video_id}.{ext}  # อัปโหลดผ่าน PHP (เมื่อไม่ได้ใช้ R2 และไฟล์เก่า)
videos/{uuid}.{ext}                                                # วิดีโอเดี่ยวของหลักสูตร (อัปโหลดตรง)
videos/{video_id}.{ext}                                            # วิดีโอเดี่ยวผ่าน PHP
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
| `MediaStorage::storeCourseThumbnail($file, $courseId)` | แปลง WebP + อัปโหลดปกวิชา |
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
| `MediaStorage::temporaryUrl($path, $minutes = 240)` | presigned URL ของ R2 ที่หมดอายุตามเวลา ไฟล์บน disk `public` หรือ URL ภายนอกคืนลิงก์ปกติ |

### วิดีโอบทเรียนใน Student API

วิดีโอบทเรียนไม่ส่งลิงก์ R2 หรือ CDN ให้เว็บนักเรียน `Video::streamUrl($courseId, $userId)` สร้างลิงก์ลงลายเซ็นไปที่ `GET /api/v1/stream/videos/{video}` (`VideoStreamController`) ซึ่งตรวจสิทธิ์ (`App\Support\VideoAccess`) และ `Sec-Fetch-Dest: video` ทุกคำขอ แล้วอ่านไฟล์จาก R2 ทีละช่วงไม่เกิน 2 MB ส่งต่อให้ตัวเล่น รายละเอียดดู `docs/student-api.md`

- ข้อมูลวิดีโอวิ่งผ่านเซิร์ฟเวอร์ Laravel ทุกไบต์ ต้องมี PHP-FPM worker พอสำหรับคนดูพร้อมกัน (แต่ละคนเปิดคำขอสั้นๆ ทีละ 2 MB) และคิดแบนด์วิดท์ขาออกของเซิร์ฟเวอร์ด้วย
- `php artisan serve` รับทีละคำขอ ตอนดูวิดีโอใน dev คำขอ API อื่นจะรอคิว
- ลายเซ็นผูกกับ URL เต็ม `APP_URL` ต้องตรงกับโดเมนและพอร์ตที่เรียกจริง ไม่อย่างนั้นได้ `403`
- ถ้า bucket R2 ยังเปิดสาธารณะผ่าน custom domain (`R2_URL`) ให้ปิด path วิดีโอ (`courses/*/chapters/*/videos/*`, `videos/*`) ที่ Cloudflare ไม่อย่างนั้นคนที่รู้ path ยังโหลดตรงจาก CDN ได้
| `MediaStorage::delete($path)` | ลบไฟล์ |

## ตัวอย่างการใช้งาน

### ปกวิชา (มีอยู่แล้วใน `CourseController`)

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

### อัปโหลดวิดีโอบทเรียนและวิดีโอเดี่ยวของหลักสูตร (อัปโหลดตรงขึ้น R2)

เมื่อใช้ R2 หน้าเพิ่มวิดีโอในบทเรียน หน้าแก้ไขวิดีโอ (เปลี่ยนไฟล์) และหน้าอัปโหลดวิดีโอในหลักสูตร ส่งไฟล์จากเบราว์เซอร์ขึ้น R2 โดยตรงแบบ multipart ไฟล์ไม่ผ่าน PHP จึงไม่ติด `upload_max_filesize` / `post_max_size` และขีดจำกัดขนาดคำขอของ Cloudflare/nginx รองรับสูงสุด 5GB

ขั้นตอน (`resources/js/video-upload.js`, `App\Support\VideoDirectUpload`, `VideoUploadController`):

1. `POST /video-uploads` ส่ง `scope` (`course` หรือ `standalone`), `course_id`, `chapter_id`, `filename`, `size` ระบบตรวจนามสกุล (mp4, webm, mov) และขนาด สร้าง multipart upload บน R2 ที่ key `.../{uuid}.{ext}` แล้วคืน `token`, `part_size`, `part_count` ถ้าไม่ได้ใช้ R2 คืน `{"direct": false}` และหน้าเว็บส่งไฟล์ผ่าน PHP แบบเดิม (สูงสุด 500MB)
2. `POST /video-uploads/{token}/sign` ขอ presigned URL ทีละไม่เกิน 50 part (อายุ 60 นาที ถ้าหมดอายุ R2 ตอบ 403 แล้วหน้าเว็บขอ URL ใหม่ให้เอง)
3. เบราว์เซอร์ `PUT` แต่ละ part ขึ้น R2 พร้อมกัน 4 part part ที่ล้มเหลวลองใหม่ได้ 8 ครั้ง (รอ 1, 2, 4 ... สูงสุด 30 วินาที และรอให้กลับมาออนไลน์ก่อน) ไม่ต้องเริ่มทั้งไฟล์ใหม่
4. `POST /video-uploads/{token}/complete` ส่ง ETag ทุก part ระบบรวมไฟล์ ตรวจขนาด และตรวจว่าเบราว์เซอร์เล่นได้ (ดูหัวข้อตรวจไฟล์วิดีโอด้านล่าง) ถ้าไม่ผ่าน ลบไฟล์ทิ้งและตอบ 422 พร้อมเหตุผล
5. หน้าเว็บส่งฟอร์มเดิม (`courses.chapters.videos.store`, `courses.chapters.videos.update`, `curriculums.videos.store`) พร้อม `upload_token` แทน `video_file` ระบบสร้างหรืออัปเดตวิดีโอด้วย key นั้น token ใช้ได้ครั้งเดียว เฉพาะผู้ใช้ที่เริ่มอัปโหลด และเฉพาะบทเรียน (หรือหลักสูตร) เดียวกับตอนเริ่ม เปลี่ยนไฟล์แล้วลบไฟล์เดิมบน R2 หลังบันทึก
6. ล้มเหลวหรือบันทึกไม่สำเร็จ หน้าเว็บเรียก `DELETE /video-uploads/{token}` เพื่อยกเลิก multipart หรือลบไฟล์ที่รวมแล้ว ระหว่างอัปโหลดถ้าปิดหน้า เบราว์เซอร์จะเตือนก่อน

ข้อมูล session ของการอัปโหลดเก็บใน cache 24 ชั่วโมง multipart ที่ค้างโดยไม่ได้ยกเลิก R2 ลบให้เองหลัง 7 วัน (ค่าตั้งต้นของ R2)

ตั้งค่าใน `.env` (ดู `config/media.php`):

```env
MEDIA_VIDEO_MAX_MB=5120          # ขนาดสูงสุด (5GB)
MEDIA_VIDEO_PART_MB=32           # ขนาดต่อ part (R2 ต้องการ part เท่ากันยกเว้น part สุดท้าย ขั้นต่ำ 5MB)
MEDIA_VIDEO_UPLOAD_TTL_HOURS=24  # อายุ token อัปโหลด
```

#### ตรวจไฟล์วิดีโอ (จอดำ / ไฟล์ไม่สมบูรณ์)

`App\Support\VideoInspector` อ่านโครงสร้าง MP4/MOV (ส่วนหัวของแต่ละ atom และ `moov` เท่านั้น ไม่ดาวน์โหลดทั้งไฟล์) ใช้ทั้งการอัปโหลดตรงขึ้น R2 และอัปโหลดผ่าน PHP ปฏิเสธไฟล์ที่เบราว์เซอร์จะแสดงเป็นจอดำหรือเล่นไม่ได้:

| กรณี | ข้อความ |
|------|--------|
| ไม่มี `moov` (บันทึกหรือ export ไม่จบ ไฟล์เสีย) | ไฟล์วิดีโอไม่สมบูรณ์ |
| ไม่มี track ภาพ (มีแต่เสียง) | ไฟล์นี้ไม่มีภาพวิดีโอ |
| H.265/HEVC (`hvc1`, `hev1`, Dolby Vision) | codec H.265 (HEVC) |
| Apple ProRes (`apcn`, `apch`, `ap4h` ...) | codec Apple ProRes |
| MPEG-4 Part 2 (`mp4v`) | codec MPEG-4 Part 2 |
| H.264 High 10 / 4:2:2 / 4:4:4 | H.264 แบบ ... |

ผ่านได้: H.264 Baseline/Main/High (8-bit), AV1, VP9 และ WebM (ตรวจเฉพาะหัวไฟล์ EBML) ถ้าไม่ได้กรอกความยาว ระบบใช้ความยาวจากไฟล์ (`mvhd`) ตอนแก้ไขวิดีโอแล้วเปลี่ยนไฟล์ ถ้าไม่ได้แก้ช่องความยาว ระบบใช้ความยาวของไฟล์ใหม่

แปลงไฟล์ด้วย HandBrake (preset "Fast 1080p30") หรือ ffmpeg:

```bash
ffmpeg -i input.mov -c:v libx264 -pix_fmt yuv420p -profile:v high -crf 20 -c:a aac -b:a 160k -movflags +faststart output.mp4
```

#### ต้องตั้ง CORS ของ bucket R2

ต้องตั้งครั้งเดียวใน Cloudflare Dashboard → R2 → bucket `clickclass` → Settings → CORS Policy ใส่ทุก origin ที่เปิดหลังบ้าน (key R2 ของระบบไม่มีสิทธิ์ตั้ง CORS เอง)

```json
[
  {
    "AllowedOrigins": ["http://localhost:8000", "http://127.0.0.1:8000", "https://ADMIN_DOMAIN"],
    "AllowedMethods": ["PUT", "GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

ถ้าไม่ได้ตั้ง หรือไม่มี origin ของหน้าเว็บ เบราว์เซอร์จะส่ง part ไม่ได้ ไฟล์ไม่เกิน 500MB หน้าเว็บส่งผ่าน PHP แบบเดิมให้แทน ไฟล์ใหญ่กว่านั้นหน้าเว็บแจ้ง "อัปโหลดขึ้น R2 ไม่ได้ ตรวจสอบอินเทอร์เน็ต หรือการตั้ง CORS" ถ้าไม่มี `ETag` ใน `ExposeHeaders` หน้าเว็บแจ้งว่า R2 ไม่ส่ง ETag

### อัปโหลดวิดีโอบทเรียนผ่าน PHP (เมื่อไม่ได้ใช้ R2)

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

หนึ่งคำถามมีได้หนึ่งรูป เก็บที่ `questions.image` ฟอร์มเพิ่มและแก้ไขคำถามของข้อสอบขายแยก ข้อสอบในวิชา และแบบฝึกหัดในวิชาอัปโหลดไฟล์นี้ รับ JPG/PNG/WebP/GIF สูงสุด 4MB แล้วแปลงเป็น WebP อัปโหลดใหม่ทับ path เดิม ลบคำถาม ลบข้อสอบ หรือลบแบบทดสอบในวิชาจะลบไฟล์ด้วย

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
- ปกวิชา / ปกข้อสอบ / ปกหนังสือ / รูปครู: รับ JPG/PNG/WebP/GIF สูงสุด 2MB แล้วแปลงเป็น WebP เสมอ
- รูปคำถามข้อสอบและรูปเพิ่มเติมของหนังสือ: สูงสุด 4MB แล้วแปลงเป็น WebP
- อัปเดตรูปปกจะทับไฟล์เดิมที่ path เดิม (`{id}.webp`)
- ถ้า R2 ยังไม่ครบ จะใช้ disk `public` แทนอัตโนมัติ
- บน Windows ต้องตั้ง `curl.cainfo` / `openssl.cafile` ใน `php.ini` ไม่เช่นนั้นอัปโหลด R2 จะล้มเหลวด้วย SSL และต้องรีสตาร์ท `php artisan serve` หลังแก้ `php.ini`
- อัปโหลดขึ้น R2 ไม่ส่ง S3 ACL เพราะ R2 ไม่รองรับ การเปิดสาธารณะใช้ custom domain (`R2_URL`)
- วิดีโอบทเรียนและวิดีโอในหลักสูตรบน R2 อัปโหลดตรง สูงสุด 5GB ไม่ใช้ค่า PHP ด้านล่าง (ดูหัวข้ออัปโหลดตรงขึ้น R2)
- วิดีโอที่อัปโหลดผ่าน PHP (ไม่ได้ใช้ R2 และวิดีโอหนังสือ): validation สูงสุด 500MB — ต้องตั้ง PHP ให้รองรับด้วย เช่น
  - `upload_max_filesize=512M`
  - `post_max_size=520M`
  - หรือรันด้วย `composer run serve` (ส่ง `-d` ค่าข้างบนให้) ถ้ารัน `php artisan serve` ตรง ๆ จะใช้ค่าใน `php.ini` (ค่าตั้งต้นของ PHP คือ 2M / 8M ทำให้อัปโหลดวิดีโอเกิน 2MB ไม่ได้)
  - ไฟล์เกิน `post_max_size` PHP จะทิ้งข้อมูลทั้งคำขอก่อนถึง Laravel จึงไม่มีอะไรใน `laravel.log` ระบบตอบ 413 พร้อมข้อความภาษาไทยที่บอกค่าขีดจำกัด และ log ของ `php artisan serve` จะมี `POST Content-Length of ... exceeds the limit`
  - แก้ `php.ini` แล้วต้องปิดและเปิด `php artisan serve` ใหม่
