# หลักสูตร / แพ็กเกจ

หน้าจัดการอยู่ที่ **การเรียนการสอน → หลักสูตร / แพ็กเกจ** (`/curriculums`)

แพ็กเกจหนึ่งชุดรวมเนื้อหาได้หลายแบบในราคาเดียว: ทั้งคอร์ส, บางบท, บางวิดีโอ, ข้อสอบที่ขายแยก, แบบฝึกหัด และหนังสือ

## ตารางที่เชื่อมเนื้อหา

| ตาราง | ใส่ได้ |
|------|--------|
| `curriculum_courses` | ทั้งคอร์ส |
| `curriculum_chapters` | บางบทของคอร์ส |
| `curriculum_videos` | บางวิดีโอ |
| `curriculum_assessments` | ข้อสอบในคอร์ส (`type = exam`) ข้อสอบขายแยก (`type = primary_exam`) และแบบฝึกหัดในคอร์ส (`type = quiz`) |
| `curriculum_products` | หนังสือ (`products.type = book`) |
| `curriculum_subjects` | วิชาหลัก (ยังไม่มีในหน้าจัดการ) |
| `curriculum_documents` | เอกสาร (ยังไม่มีในหน้าจัดการ) |

แต่ละตารางใช้คู่ `curriculum_id` + รหัสเนื้อหาเป็น primary key และมี `sort_order`

## การใช้งาน

1. เพิ่มหลักสูตร: ชื่อ, หมวดหมู่, สถานะ (ร่าง / เผยแพร่ / ปิดใช้งาน), ราคา, ราคาลด, คำอธิบาย, รูปปก
2. เปิดหน้าหลักสูตร แล้วเลือกแท็บเนื้อหา
3. กดเพิ่ม แล้วค้นหาและติ๊กรายการที่ต้องการ คอร์ส บทเรียน วิดีโอ ข้อสอบ และแบบฝึกหัดดึงผ่าน API ภายใน พร้อมตัวกรอง หน้าปก และรายละเอียด บทเรียน วิดีโอ ข้อสอบ และแบบฝึกหัดต้องเลือกคอร์สก่อนจึงจะแสดงรายการ ข้อสอบรวมทั้ง `exam` ของคอร์สนั้นและ `primary_exam` แบบฝึกหัดคือ `quiz` ของคอร์สนั้น
4. กดนำออกเพื่อถอดรายการออกจากแพ็กเกจ โดยไม่ลบต้นฉบับ

การเผยแพร่ครั้งแรกจะตั้ง `published_at` กลับเป็นร่างจะล้างค่านั้น

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|--------|
| `app/Http/Controllers/CurriculumController.php` | รายการ, สร้าง, แก้ไข, ลบ, เพิ่ม/ถอดเนื้อหา |
| `app/Models/Curriculum.php` | ความสัมพันธ์และ activity log |
| `resources/views/pages/curriculums/` | หน้าจัดการ |
| `database/migrations/2026_09_27_211500_create_curriculum_item_pivots.php` | ตารางบทเรียน, ข้อสอบ/แบบฝึกหัด, หนังสือ |

## Route

| Method | URI | Name |
|--------|-----|------|
| GET | `/curriculums` | `curriculums.index` |
| POST | `/curriculums` | `curriculums.store` |
| GET | `/curriculums/{curriculum}` | `curriculums.show` |
| PUT | `/curriculums/{curriculum}` | `curriculums.update` |
| DELETE | `/curriculums/{curriculum}` | `curriculums.destroy` |
| GET | `/curriculums/{curriculum}/catalog` | `curriculums.catalog` |
| GET | `/curriculums/{curriculum}/attached` | `curriculums.attached` |
| POST | `/curriculums/{curriculum}/items` | `curriculums.items.store` |
| DELETE | `/curriculums/{curriculum}/items` | `curriculums.items.destroy` |

`curriculums.catalog` ใช้เลือกเนื้อหาที่จะเพิ่ม `curriculums.attached` ใช้แสดงรายการที่อยู่ในหลักสูตรแล้ว ทั้งคู่รับ `type` เป็น `course`, `chapter`, `video`, `exam`, `exercise` หรือ `book`
