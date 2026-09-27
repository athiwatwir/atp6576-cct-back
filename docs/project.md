# Click Class Tutor — Backend Project

**Project:** Click Class Tutor  
**Website:** [clickclasstutor.com](https://clickclasstutor.com)  
**Repository:** `atp6576-cct-back`  
**Role:** Admin backend / API foundation  
**Status:** Foundation (schema + models) — application features not yet implemented

---

## 1. ภาพรวม

Click Class Tutor เป็นระบบคอร์สเรียนออนไลน์ มีหน้าบ้านสำหรับนักเรียน และหลังบ้านสำหรับ Admin / Staff ในการจัดการคอร์ส การเรียน การขาย การชำระเงิน โปรโมชัน และรายงาน

repo นี้คือ **ระบบหลังบ้าน** สร้างบน Laravel 12 และ TailAdmin  
ใช้เป็นฐานสำหรับ:

- จัดการข้อมูลหลัก (Course, Curriculum, User, Order, Payment, Content)
- ออกสิทธิ์การเรียน (Enrollment)
- ตรวจสอบการชำระเงิน
- เตรียม REST API ให้ frontend / student portal เรียกใช้

รายละเอียดความต้องการทางธุรกิจอยู่ที่ [business-requirements-click-class-tutor.md](business-requirements-click-class-tutor.md)  
รายละเอียด schema อยู่ที่ [database.md](database.md) และ [click_class_tutor_database.sql](click_class_tutor_database.sql)

---

## 2. ขอบเขตของ repo นี้

| รวม | ยังไม่ใช่หน้าที่หลักของ repo นี้ |
| --- | --- |
| Admin dashboard | Student-facing website / iPad learning UI |
| Staff operations | Public marketing site |
| Course / content management | Payment gateway vendor integration (ยังไม่เลือก) |
| Order / payment verification | Video CDN / HLS pipeline |
| Enrollment / permission | Mobile native app |
| REST API สำหรับ frontend | |

ระบบแบ่งผู้ใช้ตาม BRD:

- **Guest** — ดูเนื้อหาสาธารณะ สมัคร / login
- **Student** — ซื้อคอร์ส เรียน ทำข้อสอบ ดู progress
- **Staff** — สร้าง order, ตรวจสลิป, เปิดสิทธิ์เรียน
- **Admin** — จัดการระบบทั้งหมดตาม permission
- **Instructor** — จัดการเนื้อหาที่ได้รับอนุญาต

---

## 3. Technology Stack

| Layer | Choice |
| --- | --- |
| Language | PHP 8.2+ |
| Framework | Laravel 12 |
| Database | MySQL 8+, InnoDB, utf8mb4 |
| Admin UI | TailAdmin (Laravel + Blade) |
| CSS | Tailwind CSS v4 |
| JS | Alpine.js, Vite 7 |
| Queue | Database driver (เริ่มต้น) |
| Cache | Database driver (เริ่มต้น) |
| Session | Database driver |
| Tests | Pest 4 |
| Code style | Laravel Pint |

ค่าเริ่มต้นที่ตั้งไว้แล้ว:

```text
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

งานที่นาน (notification, ranking, export, video processing) ควรไปที่ queue ตาม BRD ข้อ 57

---

## 4. สถานะปัจจุบัน

งานที่ทำแล้ว:

- Laravel 12 + TailAdmin starter
- `APP_KEY` และ `composer install`
- แปลง schema เป็น Laravel migrations แล้ว migrate
- Eloquent models ครบตารางหลัก พร้อม relationships / casts / SoftDeletes

งานที่ยังเป็น TailAdmin demo (ยังไม่ใช่โดเมน Click Class Tutor):

- Routes ใน `routes/web.php` ยังเป็นหน้า ecommerce / calendar / UI kit
- ยังไม่มี auth จริง (signin/signup เป็นหน้า view)
- ยังไม่มี REST API
- ยังไม่มี policy / permission middleware
- ยังไม่มี seeder ของ roles, admin user, sample course

---

## 5. Architecture

เป้าหมายของ backend:

```text
Admin (Blade / TailAdmin)
        │
        ▼
Laravel 12  ── Eloquent / Policies / Jobs
        │
        ├── MySQL
        ├── Queue / Cache / Session
        └── REST API ──► Student frontend / iPad
```

แนวทางสำคัญจาก BRD ที่ backend ต้องยึด:

1. **Enrollment เป็นแหล่งสิทธิ์** — เข้า Course / Video / Document / Assessment ได้เมื่อ login + enrollment active + ยังไม่หมดอายุ
2. **Payment ต้อง verify ที่ backend** — อย่าเชื่อสถานะจาก frontend  
   `Order → Payment → Verify → Complete Order → Grant Enrollment`
3. **Video ห้าม public URL ถาวร** — private storage + signed URL / HLS + ตรวจสิทธิ์ทุกครั้ง
4. **Promotion / Coupon ตรวจเงื่อนไขที่ server** — `promotions.rules` เป็น JSON แต่ต้อง validate ใน PHP
5. **Account sharing** — จำกัด session / device ผ่าน `user_sessions`

---

## 6. Directory Map

```text
atp6576-cct-back/
├── app/
│   ├── Helpers/                 # TailAdmin menu helper (จะถูกแทนด้วยเมนูจริง)
│   ├── Http/Controllers/        # ยังเป็น demo dashboard
│   ├── Models/                  # Eloquent ครบตารางโดเมน
│   └── View/Components/         # TailAdmin Blade components
├── bootstrap/
├── config/
├── database/
│   ├── migrations/              # Laravel default + CCT schema
│   ├── factories/
│   └── seeders/
├── docs/
│   ├── project.md               # เอกสารนี้
│   ├── business-requirements-click-class-tutor.md
│   ├── database.md
│   └── click_class_tutor_database.sql
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/                   # TailAdmin pages
├── routes/
│   ├── web.php                  # demo routes
│   └── console.php
├── tests/
├── artisan
├── composer.json
└── package.json
```

---

## 7. Database

แหล่งความจริงของ schema คือ migrations ไม่ใช่ไฟล์ SQL  
SQL ใช้สำหรับอ่านภาพรวมเท่านั้น

กลุ่ม migration:

```text
database/migrations/
├── 0001_01_01_000000_create_users_table.php
├── 0001_01_01_000001_create_cache_table.php
├── 0001_01_01_000002_create_jobs_table.php
├── 2026_08_29_160000_create_roles_and_permissions_tables.php
├── 2026_08_29_160100_create_courses_tables.php
├── 2026_08_29_160200_create_curriculums_and_learning_tables.php
├── 2026_08_29_160300_create_assessment_tables.php
├── 2026_08_29_160400_create_commerce_tables.php
├── 2026_08_29_160500_create_promotion_tables.php
└── 2026_08_29_160600_create_content_tables.php
```

ตาราง Laravel เดิมที่คงไว้เพราะแอปใช้ driver แบบ database:

- `sessions`
- `cache` / `cache_locks`
- `jobs` / `job_batches` / `failed_jobs`
- `password_reset_tokens`

โมดูลโดเมน:

| Module | ตารางหลัก |
| --- | --- |
| Auth | `users`, `roles`, `permissions`, `social_accounts`, `user_sessions` |
| Catalog | `categories`, `subjects`, `instructors`, `courses`, `chapters` |
| Media | `videos`, `documents`, `chapter_videos`, `chapter_documents` |
| Curriculum | `curriculums`, `curriculum_courses`, `curriculum_chapters`, `curriculum_subjects`, `curriculum_videos`, `curriculum_assessments`, `curriculum_products`, `curriculum_documents` |
| Learning | `enrollments`, `video_progress`, `learning_progress`, `trial_access` |
| Assessment | `assessments`, `questions`, `question_choices`, `assessment_attempts`, `assessment_answers`, `rankings` |
| Commerce | `products`, `carts`, `cart_items`, `orders`, `order_items`, `payments`, `payment_transactions` |
| Manual pay | `payment_links`, `payment_slips` |
| Promo | `promotions`, `coupons`, `coupon_usages` |
| CMS | `contents`, `content_stats` |
| Other | `reviews`, `notifications`, `audit_logs` |

จุดที่ต้องระวังตอนเขียน Eloquent:

- `Curriculum` ใช้ `$table = 'curriculums'` (ไม่ใช่ `curricula`)
- `VideoProgress` → `video_progress`
- `LearningProgress` → `learning_progress`
- `TrialAccess` → `trial_access`
- `User::appNotifications()` สำหรับตารางแจ้งเตือนในแอป เพราะ `notifications()` ถูก trait `Notifiable` จองไว้แล้ว

รายละเอียดความสัมพันธ์และ design decision ดูที่ [database.md](database.md)

---

## 8. Models

โมเดลอยู่ที่ `app/Models` ครบตารางที่มี `id`  
ตาราง pivot ที่เป็นแค่ FK ใช้ `belongsToMany` + `withPivot` ไม่ได้แยก model

ตัวอย่างความสัมพันธ์หลัก:

```text
User ── roles / enrollments / orders / carts / attempts
Course ── chapters ── videos / documents
Curriculum ── courses / subjects / videos / documents
Order ── items / payments ── slips / transactions
Assessment ── questions ── choices
           ── attempts ── answers
```

ยังไม่มี Form Request, Policy, Observer, หรือ Service class  
logic ธุรกิจควรแยกออกจาก controller เมื่อเริ่ม implement ไม่ใส่ใน model จนหนาเกินไป

---

## 9. Core Flows (backend)

### 9.1 ซื้อแล้วเปิดสิทธิ์เรียน

```text
Cart → Checkout → Order (pending)
                → Payment
                → Backend verify (gateway หรือ staff ตรวจสลิป)
                → Order paid
                → Create Enrollment
                → Student เข้าเรียนได้
```

### 9.2 เรียนวิดีโอ

```text
Authorize enrollment
 → Issue signed URL
 → Play
 → Save video_progress (position_seconds)
 → Resume จากตำแหน่งเดิม
 → Update learning_progress
```

### 9.3 ข้อสอบ

```text
Start attempt → Save answers → Submit
 → Score → Pass/fail → Ranking
```

### 9.4 Manual payment / payment link

```text
Staff สร้าง Order
 → รับเงินสด / สร้าง Payment Link / อัปโหลดสลิป
 → Approve
 → Grant enrollment
```

---

## 10. Security Baseline

ต้องทำให้ได้ก่อนขึ้น production ตาม BRD ข้อ 47–49 และ 60:

- HTTPS
- Auth + authorization (policy / permission เช่น `course.view`, `payment.approve`)
- ตรวจ enrollment ทุกครั้งก่อนส่ง video / document / assessment
- Signed URL มีวันหมดอายุ ไม่ใส่ไฟล์ส่วนตัวใน `public/`
- CSRF / XSS / SQL injection ตามที่ Laravel มีอยู่แล้ว อย่า bypass
- Rate limit login และ video URL
- จำกัดจำนวน device ผ่าน `user_sessions`
- เก็บ `audit_logs` สำหรับงาน Admin / Staff
- Payment status เชื่อเฉพาะ backend / webhook ที่ verify signature แล้ว

---

## 11. Local Setup

ความต้องการ:

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

ตั้งค่า `.env`:

```env
APP_NAME=ClickClassTutor
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

```bash
php artisan migrate
composer run dev
```

`composer run dev` เปิดพร้อมกัน: `artisan serve`, queue listener, log, Vite

ทดสอบ:

```bash
composer run test
```

อย่า commit `.env`  
อย่ารัน SQL dump แทน migrations ในสภาพแวดล้อมปกติ

---

## 12. Implementation Phases

ตัดจาก BRD ข้อ 66 ให้เข้ากับ repo นี้:

### Phase 1 — Core admin + learning access

- Auth จริง (email + social: Google / Apple / LINE)
- Role / permission
- CRUD: category, subject, instructor, course, chapter, video, document, curriculum
- Enrollment + trial
- Video progress / learning progress
- แทนที่ TailAdmin demo menu ด้วยเมนูโดเมน
- Student/admin ไม่ต้องครบ UI หน้าบ้านใน repo นี้ แต่ API เข้าถึงเนื้อหาตามสิทธิ์ต้องมี

### Phase 2 — Assessment & commerce

- Assessment / question / attempt / score / ranking
- Cart / order / payment (gateway + manual + payment link)
- Product / book
- Promotion / coupon (validate ที่ server)
- Review moderate

### Phase 3 — Operations

- Dashboard / report
- CMS: banner, popup, news, article + content_stats
- Notification
- Audit log
- Account sharing policy
- Video signed URL / CDN
- Export Excel/CSV

ฟีเจอร์ที่ BRD แนะนำแต่ยังไม่อยู่ใน schema: wishlist, certificate, achievement, media library แยกตาราง — เพิ่มเมื่อมี design ชัด

---

## 13. Open Business Rules

ยังต้องตัดสินก่อน implement เชิงลึก (จาก [database.md](database.md)):

- User มี enrollment active ของ Course เดียวกันได้มากกว่า 1 รายการหรือไม่
- Curriculum ใส่ Course ซ้ำได้หรือไม่
- Exam ซื้อแยกได้เสมอหรือไม่ (schema รองรับ `is_independent`)
- สูตร ranking (รายวัน / สัปดาห์ / เดือน / all-time)
- โครงสร้าง `promotions.rules`
- Refund แล้ว enrollment ถูกยกเลิกทันทีหรือไม่
- หนังสือต้องมีที่อยู่จัดส่ง / tracking หรือไม่
- Order เดียวมี Course + Book + Exam ได้ — schema รองรับแล้ว ต้องล็อก flow checkout
- ผู้ให้บริการ Payment Gateway
- ผู้ให้บริการ video storage / CDN

---

## 14. Related Documents

| File | ระดับ |
| --- | --- |
| [business-requirements-click-class-tutor.md](business-requirements-click-class-tutor.md) | Business requirements |
| [database.md](database.md) | Database design |
| [click_class_tutor_database.sql](click_class_tutor_database.sql) | Schema snapshot |
| `project.md` | ภาพรวม repo / architecture (เอกสารนี้) |

เอกสาร technical ที่ควรมีต่อเมื่อเริ่มลงมือทำฟีเจอร์:

```text
docs/api.md
docs/backend.md
docs/security.md
docs/deployment.md
```

---

## 15. สิ่งที่ควรทำถัดไป

1. Seed roles (`admin`, `staff`, `instructor`, `student`) และ permission เริ่มต้น
2. ทำ authentication จริง แทนหน้า TailAdmin signin
3. วางโครงสร้าง `app/Policies` + middleware ตาม permission
4. เปลี่ยน sidebar จาก demo เป็นเมนูจัดการคอร์ส / order / นักเรียน
5. เพิ่ม `routes/api.php` ตามกลุ่มใน BRD ข้อ 56
6. แยก service สำหรับ payment verification และ enrollment grant
7. ล็อก video/document หลัง authorization + signed URL
8. ตั้งค่า Gmail ใน `.env` ตาม [email.md](email.md) แล้วทดสอบ `php artisan mail:test`
