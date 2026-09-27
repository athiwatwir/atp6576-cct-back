# Document Sequence (รหัสเอกสาร)

Helper กลางสำหรับรันเลขเอกสารจากฐานข้อมูล  
อยู่ที่ `App\Support\DocumentSequence`  
ใช้ `lockForUpdate` กันเลขซ้ำตอนสร้างพร้อมกันหลาย request

## รูปแบบรหัส

| ประเภท | รูปแบบ | ตัวอย่าง | คอลัมน์ที่เก็บ |
|--------|--------|----------|----------------|
| คอร์ส | `C{dayOfYear}{seq2}` | `C26901` | `courses.code` |
| ข้อสอบ (ขายแยก) | `E{dayOfYear}{seq2}` | `E26901` | `assessments.code` |
| หนังสือ | `B{seq3}` | `B001` | `products.code` |
| ออเดอร์ | `O{ymd}{seq3}` | `O260926001` | `orders.order_no` |

### ความหมายส่วนประกอบ

| ส่วน | ความหมาย |
|------|----------|
| `{dayOfYear}` | วันของปี 3 หลัก (`001`–`366`) เช่น 26 ก.ย. = `269` |
| `{ymd}` | วันที่ `yyMMdd` เช่น 26 ก.ย. 2026 = `260926` |
| `{seq2}` | ลำดับต่อวันของปีนั้น 2 หลัก เริ่ม `01` |
| `{seq3}` | ลำดับ 3 หลัก เริ่ม `001` |

### ขอบเขตการรันเลข (`period_key`)

| ประเภท | `period_key` | พฤติกรรม |
|--------|--------------|----------|
| คอร์ส / ข้อสอบ | `dayOfYear` เช่น `269` | รันต่อเนื่องตามวันของปี (ไม่รีเซ็ตทุกปี เพื่อไม่ให้รหัสซ้ำข้ามปี) |
| หนังสือ | `global` | รันเลขทั้งระบบ ไม่ผูกวันที่ |
| ออเดอร์ | `ymd` เช่น `260926` | รีเซ็ตทุกวัน (รหัสมีวันที่อยู่แล้ว จึงไม่ชนกัน) |

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|--------|
| `app/Support/DocumentSequence.php` | สร้างรหัสถัดไป |
| `app/Enums/DocumentType.php` | ประเภทเอกสาร + prefix (`C`/`E`/`B`/`O`) |
| `app/Models/DocumentSequence.php` | Eloquent ของตารางรันเลข |
| `database/migrations/2026_09_26_144802_create_document_sequences_and_codes.php` | ตาราง `document_sequences` + คอลัมน์ `code` |

## ตาราง `document_sequences`

| คอลัมน์ | ประเภท | ความหมาย |
|---------|--------|----------|
| `doc_type` | string | `course` / `exam` / `book` / `order` |
| `period_key` | string | ช่วงรันเลข (ดูตารางด้านบน) |
| `last_number` | unsigned int | เลขล่าสุดที่ออกไปแล้ว |

Unique: `(doc_type, period_key)`

## API

| Method | คืนค่า |
|--------|--------|
| `DocumentSequence::nextCourse(?CarbonInterface $at = null)` | รหัสคอร์ส |
| `DocumentSequence::nextExam(?CarbonInterface $at = null)` | รหัสข้อสอบ |
| `DocumentSequence::nextBook()` | รหัสหนังสือ |
| `DocumentSequence::nextOrder(?CarbonInterface $at = null)` | หมายเลขออเดอร์ |

พารามิเตอร์ `$at` ใช้กำหนดวันที่อ้างอิง (ค่าเริ่มต้น = `now()`) — มีประโยชน์ตอนทดสอบหรือ backdate

## ตัวอย่างการใช้งาน

```php
use App\Support\DocumentSequence;

$courseCode = DocumentSequence::nextCourse(); // C26901
$examCode   = DocumentSequence::nextExam();   // E26901
$bookCode   = DocumentSequence::nextBook();   // B001
$orderNo    = DocumentSequence::nextOrder();  // O260926001
```

### จุดที่เรียกใช้แล้วในระบบ

| Controller | เมื่อไหร่ |
|------------|----------|
| `CourseController@store` | สร้างคอร์ส → `courses.code` |
| `ExamController@store` | สร้างข้อสอบขายแยก → `assessments.code` |
| `BookController@store` | สร้างหนังสือ → `products.code` |
| `OrderController` (`generateOrderNo`) | สร้างออเดอร์ → `orders.order_no` |

> แบบทดสอบ/ควิซในคอร์ส (`CourseAssessmentController`) **ยังไม่ออกเลข** `E...` — ใช้เฉพาะข้อสอบขายแยก

## Migration

```bash
php artisan migrate
```

คอลัมน์ `code` บน `courses` / `assessments` / `products` เป็น `nullable` + `unique`  
ข้อมูลเก่ายังเป็น `null` ได้ — รหัสจะถูกสร้างเฉพาะตอนสร้างเรคอร์ดใหม่

## หมายเหตุ

- การออกเลขทำใน transaction + `SELECT … FOR UPDATE` จึงปลอดภัยต่อ concurrent create
- อย่าแก้ `last_number` ใน DB ด้วยมือ ถ้าไม่จำเป็น — อาจทำให้รหัสชนกับของเดิม
- ถ้าต้องการรีเซ็ตเลขทดสอบใน local: ลบแถวใน `document_sequences` (อย่าทำบน production ถ้ามีเอกสารจริงแล้ว)
