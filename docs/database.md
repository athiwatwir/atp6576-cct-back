# Click Class Tutor - Database Design

## Stack

- Laravel 12
- PHP 8.2+
- MySQL 8+
- InnoDB
- utf8mb4

## Database Modules

1. Authentication / Users / Roles / Permissions
2. Course / Curriculum / Subject / Chapter
3. Video / Document
4. Enrollment / Learning Progress
5. Exercise / Quiz / Exam / Question / Score
6. Product / Book / Cart / Order / Payment
7. Promotion / Coupon
8. Review
9. Content / Banner / Popup / News / Article
10. Notification
11. Ranking
12. Audit Log
13. Payment Link / Manual Payment
14. Trial Learning

## Important Design Decisions

### ระดับชั้น (categories) และชนิดวิชา (subjects)

`categories` คือระดับชั้น เช่น ประถมศึกษา มัธยมศึกษาตอนต้น จัดการที่เมนู "ระดับชั้น" (`/categories`) ข้อมูลตั้งต้น 4 รายการมาจาก `CategorySeeder`

- `name`, `slug` (a-z 0-9 และขีดกลาง ห้ามซ้ำ รวมแถวที่ soft delete ถ้าเว้นว่างระบบสร้างจากชื่อ ชื่อภาษาไทยได้ `level`, `level-1`, ...), `description`, `status` (`active` / `inactive`), `sort_order` (น้อยแสดงก่อน)
- ฟอร์มวิชา ชนิดวิชา และหลักสูตร แสดงเฉพาะระดับชั้น `active` เรียงตาม `sort_order` หน้าแก้ไขยังแสดงระดับชั้นเดิมที่ถูกปิดใช้งานไว้ เพื่อไม่ให้ค่าหายตอนบันทึก
- ลบได้เฉพาะระดับชั้นที่ไม่มีวิชา หลักสูตร หรือชนิดวิชาผูกอยู่ (soft delete) ถ้ามีข้อมูลผูกอยู่ให้ปิดใช้งานแทน
- แก้ไขบันทึกใน Activity Log

`subjects` คือชนิดวิชา เช่น คณิตศาสตร์ มี `category_id` (เลือกได้ในฟอร์มชนิดวิชา) เมื่อเพิ่มหรือแก้ไขวิชาโดยไม่เลือกระดับชั้น ระบบใช้ระดับชั้นของชนิดวิชาที่เลือก

### Course

A Course contains chapters. Chapters can contain videos and documents.

### Curriculum

A Curriculum can be assembled from:

- Courses
- Subjects
- Videos
- Documents

Books are a separate product and are not attached to a curriculum. The `curriculum_products` pivot has been removed.

This supports the requirement that a curriculum can select individual learning content.

### Assessment

`assessments` represents:

- Exercise
- Quiz
- Exam

The `type` column distinguishes the assessment type.

An assessment can be attached to a Course, Chapter, or Video and can also be independent.

`questions.image` stores one optional image for a question. Admin screens for independent exams, course exams, and course quizzes upload it. The student storefront API does not return questions or question images.

### Student profile

`users` stores optional student details:

- `school_name`
- `address_line1`
- `subdistrict`
- `district`
- `province`
- `postal_code` (5 digits when present)

Admin student create/edit and the student API register/profile endpoints read and write these fields.

`users.email_verified_at` stays empty when a student registers through the API. The account can sign in before the confirmation link is opened. Accounts that already existed were marked verified. Admin-created students are verified immediately.

### Book media

`products.thumbnail` is the book cover. Extra images and the single preview video live in `product_media`:

| Column | Meaning |
| --- | --- |
| `product_id` | Book |
| `kind` | `image` or `video` |
| `path` | Stored file path |
| `sort_order` | Gallery order |

A book can have up to 20 gallery images and one video. Detail responses from the student API include `images` and `video_url`.

### Enrollment

Enrollment controls whether a student can access learning content.

It supports:

- Start date
- Expiration date
- Status
- Source
- Course
- Curriculum

### Video Progress

`video_progress` stores the last watched position so the student can continue watching from the previous position.

### Order

`orders.guest_token` (nullable, unique, 48 chars) is set for orders placed through the guest checkout API (`/api/v1/guest/orders`). It lets the buyer view, pay, and upload a slip without logging in. It is hidden from JSON and excluded from the activity log. The order still belongs to a student `user_id` (created from the email when the account did not exist).

`orders.kind` is `book` or `enrollment`. A book order is a book sale and keeps shipping. An enrollment order is a course, curriculum, or exam purchase and does not use shipping. One order is one kind. Existing orders that contain only learning items are backfilled to `enrollment`. Orders that also contain a book stay `book`.

### Payment

The payment flow is:

Order → Payment → Verify Payment → Complete Order → Grant Enrollment

Payment status must always be verified by the backend.

Price plans (details in `docs/pricing.md`): `price_plans` holds one or more prices per course, curriculum, or standalone exam (`course_id` / `curriculum_id` / `assessment_id`, `name`, `duration_days` nullable = lifetime, `price`, `sale_price`, `is_active`, `sort_order`). The old `price` / `sale_price` columns on the item are kept in sync with the cheapest active plan for listings. `order_items.price_plan_id` and `order_items.access_days` snapshot the chosen plan; on payment `enrollments.expires_at` is set from `access_days` (extended from the current expiry when still active, never shortened from lifetime).

Online payment (details in `docs/payments.md`): each Thai QR or card attempt is a new `payments` row.

| Column | Notes |
|--------|--------|
| `payment_method` | `bank_transfer`, `thai_qr`, `credit_card`, `cod` |
| `provider` | `kgp` (Thai QR), `omise` (card), `fake` (test mode), `null` for slip transfer |
| `transaction_id` | KGP `qr_id` or Omise charge id; unique with `provider` |
| `reference` | Our reference `CCT` + 10-digit payment id, sent to KGP as `reference_order`; unique |
| `expires_at` | QR expiry |
| `failure_code`, `failure_message` | Why the attempt failed (`superseded` when replaced by a newer attempt or the order was paid by another row, `amount_mismatch` when the gateway amount differs) |

Receipts (details in `docs/receipts.md`): `receipts` holds one receipt per paid order (`order_id` unique, `receipt_no` `RC{ym}{seq4}`) with a snapshot of customer, items, and amounts at payment time; the PDF is rendered from this snapshot.

Payment fees: `payment_fees` has one row per method (`payment_method` unique: `thai_qr`, `credit_card`, `bank_transfer`) with `is_enabled`, `percent`, `fixed_amount`, `min_amount`, `max_amount`, `label`, `updated_by`. When enabled, `payments.amount` = order total + fee and `payments.fee_amount` stores the fee; `orders.total_amount` is unchanged.

`payment_transactions` logs every gateway event (create QR, charge, inquiry, notify, refund notify, webhook). KGP refund notifications are stored under `payments.metadata.refunds.{refund_id}`; a confirmed full refund sets `payments.status` and `orders.payment_status` to `refunded`. `payment_links` holds the public `/pay/{token}` page token per order (`active` → `used` once the order is paid).

### Promotion / Coupon

Promotion contains campaign rules.

Coupon contains the redeemable code.

Complex promotion conditions can be stored in `promotions.rules` and must be validated server-side.

### Content Management

Articles do not use `published_at` or `expired_at` for display. A published article stays visible until its status changes. Banners still use both timestamps as a display window.

`contents.type` can represent:

- news
- article
- banner
- popup

Content can be connected to:

- Course (`content_courses`)
- Curriculum (`content_curriculums`)
- Assessment / standalone exam (`content_assessments`)
- Promotion
- Coupon

Articles in admin can attach multiple courses, curriculums, and primary exams. The student article detail API exposes published related items under `related`.

Article SEO fields on `contents` (nullable, used when `type = article`):

| Column | Notes |
|--------|--------|
| `seo_title` | Meta / OG title; API falls back to `title` |
| `seo_description` | Meta description; API falls back to `excerpt` |
| `seo_keywords` | Comma-separated keywords; API may be `null` |

`content_stats` stores impression / click events.

### Testimonials (admin reviews for storefront)

Table `testimonials` stores marketing-style reviews managed in admin (menu รีวิว / `/reviews`). Separate from student course ratings in `reviews`.

| Column | Notes |
|--------|--------|
| `name` | Reviewer display name |
| `content` | Review text (plain) |
| `image` | Optional cover path on media disk (`testimonials/{id}/{id}.webp`) |
| `status` | `draft` / `published` / `inactive` |
| `sort_order` | Lower shows first |
| soft deletes | Yes |

Public API exposes published rows only via `GET /api/v1/reviews`.

### Security

Private videos and documents should not use permanent public URLs.

Use Laravel authorization plus signed URL / CDN protection for video delivery.

## Recommended Laravel Implementation

The SQL file is useful for reviewing the overall schema.

For the actual Laravel project, convert the schema into Laravel migrations.

Recommended migration groups:

```text
database/migrations/
├── 0001_01_01_000000_create_users_table.php
├── 0001_01_01_000001_create_roles_table.php
├── ...
├── 2026_xx_xx_create_courses_table.php
├── 2026_xx_xx_create_curriculums_table.php
├── 2026_xx_xx_create_learning_tables.php
├── 2026_xx_xx_create_assessment_tables.php
├── 2026_xx_xx_create_commerce_tables.php
├── 2026_xx_xx_create_promotion_tables.php
└── 2026_xx_xx_create_content_tables.php
```

Do not use this SQL as a substitute for migrations in normal Laravel development.

## Important Next Step

Before production development, review the following business rules:

- Whether a user can have multiple active enrollments for the same Course
- Whether a Curriculum can contain the same Course more than once
- Whether an Exam can be purchased independently
- How ranking is calculated
- Exact Promotion rule structure
- Refund behavior
- Enrollment behavior after refund
- Book shipping / delivery requirements
- Whether one Order can contain Course + Book + Exam
- Refund through the payment gateway (not built yet; see `docs/payments.md`)
- Video storage / CDN provider
