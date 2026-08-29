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

### Course

A Course contains chapters. Chapters can contain videos and documents.

### Curriculum

A Curriculum can be assembled from:

- Courses
- Subjects
- Videos
- Documents

This supports the requirement that a curriculum can select individual learning content.

### Assessment

`assessments` represents:

- Exercise
- Quiz
- Exam

The `type` column distinguishes the assessment type.

An assessment can be attached to a Course, Chapter, or Video and can also be independent.

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

### Payment

The payment flow is:

Order → Payment → Verify Payment → Complete Order → Grant Enrollment

Payment status must always be verified by the backend.

### Promotion / Coupon

Promotion contains campaign rules.

Coupon contains the redeemable code.

Complex promotion conditions can be stored in `promotions.rules` and must be validated server-side.

### Content Management

`contents.type` can represent:

- news
- article
- banner
- popup

Content can be connected to:

- Course
- Curriculum
- Promotion
- Coupon

`content_stats` stores impression / click events.

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
- Payment Gateway provider
- Video storage / CDN provider
