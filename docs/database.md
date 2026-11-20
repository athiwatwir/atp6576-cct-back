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
- หน้ารายการ (`/categories`) เรียงตาม `sort_order` แล้ว `name` หน้าละ 15 รายการ ลากไอคอนหน้าแถวเพื่อเรียงลำดับ หน้าเว็บส่ง `PUT /categories/reorder` พร้อม `id` ของแถวที่แสดงอยู่ตามลำดับใหม่ ระบบสลับเฉพาะตำแหน่งของระดับชั้นเหล่านั้นในลำดับรวม แล้วเรียง `sort_order` ใหม่ตั้งแต่ 1 (`CategoryController::reorder`) ระดับชั้นที่ไม่ได้แสดงอยู่ (หน้าอื่นหรือนอกผลกรอง) คงตำแหน่งเดิม แต่ละครั้งบันทึก `category.reordered` หนึ่งรายการใน Activity Log
- ฟอร์มวิชา ชนิดวิชา และหลักสูตร แสดงเฉพาะระดับชั้น `active` เรียงตาม `sort_order` หน้าแก้ไขยังแสดงระดับชั้นเดิมที่ถูกปิดใช้งานไว้ เพื่อไม่ให้ค่าหายตอนบันทึก
- ลบได้เฉพาะระดับชั้นที่ไม่มีวิชา หลักสูตร หรือชนิดวิชาผูกอยู่ (soft delete) ถ้ามีข้อมูลผูกอยู่ให้ปิดใช้งานแทน
- แก้ไขบันทึกใน Activity Log

`subjects` คือชนิดวิชา เช่น คณิตศาสตร์ มี `category_id` (เลือกได้ในฟอร์มชนิดวิชา) เมื่อเพิ่มหรือแก้ไขวิชาโดยไม่เลือกระดับชั้น ระบบใช้ระดับชั้นของชนิดวิชาที่เลือก

### Course

A Course contains chapters. Chapters can contain videos and documents.

Trial videos: when the course has `is_trial_available` turned on, students who have not bought it can watch videos marked `videos.is_free`.

- `videos.trial_seconds` (nullable) limits how many seconds a trial viewer can watch (`Video::trialLimitSeconds()`). `null`, or a value equal to or longer than `duration_seconds`, means the whole clip.
- **Unit in the admin form:** minutes (`trial_minutes`), in steps of 0.5. For example, 1.5 means 90 seconds. The value is converted to seconds before saving (`CourseVideoUpload::trialSeconds()`).
- **Where admins set it:**
  - The "เปิดให้ทดลองเรียน" checkbox in the video upload form (it also applies to standalone curriculum videos).
  - The video edit form on the course page. Turning trial off clears the value.
- **Enforcement:**
  - The limit is applied by the student web player using `watch_limit_seconds` from the API.
  - `PUT .../progress` stores a trial viewer's position up to that limit only and never marks the video complete (`VideoAccess::watchLimit()`).
  - The stream link does not cut the file by time.

The admin course list (`/courses`) shows courses by `sort_order` and then `id`, 50 per page.

- To reorder, drag the handle at the front of a row. The page sends `PUT /courses/reorder` with the `id` values of the rows on screen, in their new order. The server swaps only the positions of those courses within the overall order and then renumbers `sort_order` from 1 (`CourseController::reorder`). Courses not on screen, such as those on other pages or outside the current filter, keep their positions. Each reorder writes one `course.reordered` entry to the Activity Log.
- A new course is placed at the end (`max(sort_order) + 1`).
- Click the star to toggle `is_featured` immediately (`PUT /courses/{course}/featured`). This is recorded in the Activity Log as a normal update of the course.

`courses.status`: `draft` / `published` / `tfh` / `inactive` (`Course::STATUSES`)

- `published`: shown in the student API and sellable from both the storefront and the admin.
- `tfh`: sellable only from the admin (create order on the student page, `StudentPurchase`). The student API hides it from everyone except students who hold an active enrollment for it (`Course::scopeLearnableBy()`):
  - It does not appear in the catalog, curriculum detail, or articles.
  - Course detail (`GET /courses/{slug}`), video streaming, progress, rating, and course exams work only for students with an active enrollment; everyone else gets `404` or `403`. The detail response for a `tfh` course has no `price_plans`, because it cannot be renewed on the storefront.
  - `checkout/quote` and `orders` reject it with `422`.
  - It appears in `learning.courses` of the profile for students who have access.
  - Orders that contain a `tfh` course are hidden from `GET /orders`, and `/orders/{order}/*`, `/orders/{order}/payment/*`, and `/guest/orders/{token}/*` return `404` (`Order::scopeVisibleInStudentApi()`, `Order::isHiddenFromStudentApi()`). Admins can still send the `/pay/{token}` payment link, which is a backend web page and not part of the API.

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
- `school_province`: the province where the student studies, which can differ from the address `province`.
  - It must be one of the 77 Thai province names in `App\Support\ThaiProvinces`. The admin form shows them as suggestions.
  - Input is normalized before validation: a leading `จังหวัด` or `จ.` is removed, and `กทม.`, `กรุงเทพฯ` and `อยุธยา` become the full names.
  - Checkout (`POST /orders` and `POST /guest/orders`, field `province`) writes this column, not the address `province`.
- `address_line1`
- `subdistrict`
- `district`
- `province`
- `postal_code` (5 digits when present)

Admin student create/edit and the student API register/profile endpoints read and write these fields.

A student needs an email or a phone. At least one is required in admin create/edit and in API register/profile. Either one can be used to log in.

A student without an email:
- **Emails:** gets no verification email and no purchase email.
- **Email verification:** `email_verified_at` stays empty. When an admin later adds an email, it is marked verified.
- **First purchase paid from admin:** the system still issues a password. Admins read it from the "ข้อมูลเข้าสู่ระบบ" box on the student page and pass the phone and password to the student.

`users.phone` is also a login name for the student website (`POST /auth/login`).

- **Normalized on save** (`User` mutator, `App\Support\Phone`):
  - Only digits are kept.
  - A Thai number written with `66` or `+66` at the front is stored with a leading `0`. For example, `+66 81-234-5678` becomes `0812345678`.
  - A value with no digits becomes `null`.
  - Migration `2026_11_12_130000_normalize_user_phones` converted existing rows the same way.
- **Validated** (`App\Rules\StudentPhone`) on student register, student profile update, and admin student create/edit:
  - It must look like a phone number, with 9–15 digits.
  - It must not belong to another student. Staff accounts are not checked.
- **Guest checkout:** a new account skips a phone that another student already uses, and the order still goes through.
- **Duplicates:** older data or admin book orders can still produce two students with the same phone. Login then signs in the account whose password matches.
- **Admin search:** student lists also match the normalized phone, so searching `081-234-5678` finds `0812345678`.

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

`users.credentials_pending_at` (nullable timestamp) marks an account created by guest checkout whose login details have not been emailed yet. Its password is a random value nobody knows. On the first successful payment the system sets a new password, emails it in the purchase email, and clears this column. Changing the password any other way also clears it, so the system never overwrites a password someone set. Hidden from JSON and the activity log.

`users.issued_password` and `users.issued_password_at` hold an encrypted copy (`Crypt::encryptString`, using `APP_KEY`) of the student's current password, so admins can view it.

- **When it is written** (`User::setVisiblePassword()` / `User::rememberPasswordCopy()`):
  - The student registers (`POST /auth/register`).
  - The student changes their own password (`PUT /profile/password`).
  - The student logs in successfully (`POST /auth/login`). This also captures accounts that set their password before this feature.
  - An admin sets a password in the student form.
  - The system issues a password after the first payment.
  - An admin clicks "สร้างรหัสผ่านใหม่".
- **When it is cleared:** whenever the password changes through any other path, so the copy never goes stale (`User::booted()`).
- **Staff:** passwords of backend users are never copied.
- **If decryption fails** (for example because `APP_KEY` changed), it is treated as having no copy.
- **Privacy:** both columns are hidden from JSON and the activity log.
- **Not in the student API.**
- **If `APP_KEY` changes:** all stored copies can no longer be decrypted, and admins must issue new passwords instead.

`orders.kind` is `book` or `enrollment`. A book order is a book sale and keeps shipping. An enrollment order is a course, curriculum, or exam purchase and does not use shipping. One order is one kind. Existing orders that contain only learning items are backfilled to `enrollment`. Orders that also contain a book stay `book`.

### Payment

The payment flow is:

Order → Payment → Verify Payment → Complete Order → Grant Enrollment

Payment status must always be verified by the backend.

Coupons hold all discount conditions and gifts. The details are in `docs/coupons.md`.

- **Conditions:** `coupons.student_group`, `coupons.purchase_type` (`any` / `new` / `renewal`), `coupons.required_video_seconds`, `coupon_required_*`
- **Gifts:** `coupon_gift_*`
- **Gift grant record:** `coupon_usages.gifts_granted_at`
- **Gift book orders:** a gift book order is a book order with a 0 total that sets `orders.gift_for_order_id`.
- **Gift exams:** a gift exam is an `enrollments` row with `source = coupon_gift`.
- **Old promotion tables:** the `promotions*` tables are no longer used by the admin.

Price plans (details in `docs/pricing.md`): `price_plans` holds one or more prices per course, curriculum, or standalone exam (`course_id` / `curriculum_id` / `assessment_id`, `name`, `duration_days` nullable = lifetime, `price`, `sale_price`, `is_active`, `sort_order`). The old `price` / `sale_price` columns on the item are kept in sync with the cheapest active plan for listings. `order_items.price_plan_id` and `order_items.access_days` snapshot the chosen plan; on payment `enrollments.expires_at` is set from `access_days` (extended from the current expiry when still active, never shortened from lifetime).

Online payment (details in `docs/payments.md`): each Thai QR or card attempt is a new `payments` row.

| Column | Notes |
|--------|--------|
| `payment_method` | `bank_transfer`, `thai_qr`, `credit_card`, `cod`, `truemoney`, `shopeepay`, `rabbit_linepay`, `wechat_pay` |
| `provider` | `kgp` (Thai QR), `omise` (card), `fake` (test mode), `null` for slip transfer |
| `transaction_id` | KGP `qr_id` or Omise charge id; unique with `provider` |
| `reference` | Our reference `CCT` + 10-digit payment id, sent to KGP as `reference_order`; unique |
| `expires_at` | QR expiry |
| `failure_code`, `failure_message` | Why the attempt failed (`superseded` when replaced by a newer attempt or the order was paid by another row, `amount_mismatch` when the gateway amount differs) |

Receipts (details in `docs/receipts.md`): `receipts` holds one receipt per paid order (`order_id` unique, `receipt_no` `RC{ym}{seq4}`) with a snapshot of customer, items, and amounts at payment time; the PDF is rendered from this snapshot.

Payment methods and fees: `payment_fees` has one row per customer payment method (`payment_method` unique: `thai_qr`, `credit_card`, `truemoney`, `shopeepay`, `rabbit_linepay`, `wechat_pay`, `bank_transfer`) with `is_active` (customers can choose this method), `is_enabled` (charge a fee), `percent`, `fixed_amount`, `min_amount`, `max_amount`, `label`, `updated_by`. A method without a row is inactive. When a fee is enabled, `payments.amount` = order total + fee and `payments.fee_amount` stores the fee; `orders.total_amount` is unchanged. E-wallet payments use `provider = omise` and keep `source_type`, `platform`, `flow`, `authorize_uri`, `qr_image` in `payments.metadata`.

`payment_transactions` logs every gateway event (create QR, charge, wallet, inquiry, notify, refund notify, webhook) with the JSON payload from the payment provider in `payload`. The latest create response is also mirrored on `payments.metadata.gateway_response`, and the latest callback on `payments.metadata.last_callback`. KGP refund notifications are stored under `payments.metadata.refunds.{refund_id}`; a confirmed full refund sets `payments.status` and `orders.payment_status` to `refunded`. `payment_links` holds the public `/pay/{token}` page token per order (`active` → `used` once the order is paid).

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
