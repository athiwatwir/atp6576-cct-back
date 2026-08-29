# Business Requirements Document (BRD)
# ระบบคอร์สเรียนออนไลน์ Click Class Tutor

**Project:** Click Class Tutor  
**Website:** clickclasstutor.com  
**Document Type:** Business Requirements Document (BRD)  
**Version:** 1.0  
**Status:** Draft / For Development Reference

---

# 1. ภาพรวมโครงการ

Click Class Tutor เป็นระบบการเรียนออนไลน์ที่ให้บริการคอร์สเรียนและหลักสูตรสำหรับนักเรียน โดยมีทั้งระบบหน้าบ้านสำหรับนักเรียนและผู้ใช้งานทั่วไป และระบบหลังบ้านสำหรับ Admin / Staff ในการบริหารจัดการข้อมูล การเรียน การขาย การชำระเงิน โปรโมชั่น และรายงานต่าง ๆ

ระบบต้องรองรับการเรียนผ่าน Video, เอกสารประกอบการเรียน, แบบฝึกหัด, Quiz และข้อสอบ รวมถึงสามารถติดตามความคืบหน้าการเรียน คะแนน และ Ranking ของผู้เรียนได้

ระบบต้องรองรับการขาย Course, Curriculum, แบบทดสอบ และหนังสือ รวมถึงระบบ Promotion / Coupon และการทดลองเรียน

---

# 2. วัตถุประสงค์ของระบบ

1. ให้ผู้เรียนสามารถสมัครสมาชิกและเข้าเรียนออนไลน์ได้ด้วยตนเอง
2. ให้ผู้เรียนสามารถซื้อ Course หรือ Curriculum ได้
3. รองรับการชำระเงินผ่าน Payment Gateway และการชำระเงินแบบ Manual
4. ให้ผู้เรียนสามารถเรียน Video และเอกสารประกอบการเรียนได้
5. สามารถบันทึกความคืบหน้าการเรียนและกลับมาเรียนต่อจากตำแหน่งเดิมได้
6. รองรับแบบฝึกหัด Quiz และข้อสอบ
7. สามารถเก็บคะแนนและประวัติการทำข้อสอบของผู้เรียน
8. มีระบบ Ranking เพื่อสร้างแรงจูงใจในการเรียน
9. มีระบบทดลองเรียน
10. มีระบบ Promotion และ Coupon ที่กำหนดเงื่อนไขได้
11. ป้องกันการนำบัญชีไปใช้งานร่วมกันหลายบุคคล
12. ป้องกันการดาวน์โหลด Video โดยตรง
13. ให้พนักงานสามารถจัดการนักเรียน คอร์ส การชำระเงิน และสิทธิ์การเรียนจากระบบหลังบ้าน
14. มี Dashboard และ Report สำหรับนักเรียนและผู้ดูแลระบบ
15. รองรับการประชาสัมพันธ์ Course และ Promotion ผ่าน Banner / Popup / News / Article

---

# 3. ขอบเขตระบบ

ระบบแบ่งออกเป็นส่วนหลักดังนี้

```text
1. Website / Frontend
2. Student Portal
3. Learning Management System
4. Course & Curriculum Management
5. Assessment System
6. Commerce & Payment
7. Promotion & Coupon
8. Content Management
9. Review Management
10. User / Staff / Permission Management
11. Reporting & Dashboard
12. Security & Account Protection
```

---

# 4. ประเภทผู้ใช้งาน

## 4.1 Guest

ผู้ใช้งานทั่วไปที่ยังไม่ได้สมัครสมาชิก

สามารถ:

- ดูหน้าเว็บไซต์
- ดูรายละเอียด Course
- ดู Curriculum
- ดู Promotion
- ดู Banner / Popup
- อ่านข่าว / บทความ
- ดู Course ที่เปิดทดลองเรียน
- สมัครสมาชิก
- Login

---

## 4.2 Student

ผู้เรียนที่สมัครสมาชิกแล้ว

สามารถ:

- จัดการ Profile
- ซื้อ Course
- ซื้อ Curriculum
- ซื้อแบบทดสอบ
- ซื้อหนังสือ
- ใช้ Coupon
- ชำระเงิน
- เรียน Video
- ดาวน์โหลดเอกสารที่ได้รับอนุญาต
- ทำแบบฝึกหัด
- ทำ Quiz
- ทำข้อสอบ
- ดูคะแนน
- ดูประวัติการสอบ
- ดู Ranking
- ดู Progress
- รีวิว Course
- รับ Notification
- กลับมาเรียนต่อจากตำแหน่งเดิม

---

## 4.3 Staff

พนักงานที่ดูแลการให้บริการ

สามารถ:

- จัดการนักเรียน
- สร้าง Order ให้ลูกค้า
- รับชำระเงินแบบเงินสด
- ตรวจสอบสลิป
- สร้าง Payment Link
- กำหนดสิทธิ์ Course ให้ผู้เรียน
- ตรวจสอบการสมัครเรียน
- ดูข้อมูลการชำระเงิน
- ดู Report ตามสิทธิ์ที่ได้รับ

---

## 4.4 Admin

ผู้ดูแลระบบ

สามารถจัดการระบบทั้งหมดตาม Permission

เช่น:

- User
- Staff
- Course
- Curriculum
- Video
- Document
- Assessment
- Order
- Payment
- Promotion
- Coupon
- Content
- Review
- Report
- System Setting

---

## 4.5 Instructor

ผู้สอน / ผู้สร้างเนื้อหา

สามารถจัดการข้อมูลที่ได้รับอนุญาต เช่น:

- Course
- Chapter
- Video
- Document
- Assessment
- Question
- Answer

ทั้งนี้ต้องควบคุมด้วย Permission

---

# 5. ระบบสมัครสมาชิกและ Login

## 5.1 Registration

ผู้เรียนสามารถสมัครสมาชิกด้วย:

- Email
- Google Account
- Apple Account
- LINE

ระบบต้องตรวจสอบ Account ซ้ำก่อนสร้างผู้ใช้งานใหม่

---

## 5.2 Login

รองรับ:

- Email / Password
- Google
- Apple
- LINE

ระบบต้องบันทึกข้อมูลการ Login และ Session เพื่อใช้ควบคุมความปลอดภัย

---

# 6. ระบบจัดการผู้ใช้งาน

Admin / Staff สามารถ:

- ค้นหานักเรียน
- ดูข้อมูลนักเรียน
- แก้ไขข้อมูล
- ระงับบัญชี
- เปิดใช้งานบัญชี
- Reset Password ตามสิทธิ์
- ดู Course ที่นักเรียนมีสิทธิ์
- ดูวันหมดอายุของสิทธิ์
- ดูประวัติการซื้อ
- ดูประวัติการชำระเงิน
- ดูประวัติการเรียน
- ดูประวัติการสอบ

---

# 7. Course Management

ระบบต้องรองรับการสร้างและจัดการ Course

ข้อมูลหลัก:

- ชื่อ Course
- Slug
- รายละเอียด
- Thumbnail
- Banner
- ราคา
- ราคาพิเศษ
- หมวดหมู่
- วิชา
- ผู้สอน
- สถานะ
- Course แนะนำ
- Course ใหม่
- เปิดทดลองเรียน
- วันที่เผยแพร่

สถานะ Course:

```text
Draft
Published
Inactive
```

---

# 8. Chapter / บทเรียน

Course สามารถประกอบด้วยหลาย Chapter

ตัวอย่าง:

```text
Course: คณิตศาสตร์ ป.6

Chapter 1
Chapter 2
Chapter 3
```

แต่ละ Chapter สามารถประกอบด้วย:

- Video
- Document
- แบบฝึกหัด
- Quiz
- ข้อสอบ

สามารถกำหนดลำดับการเรียนได้

---

# 9. Video Management

ระบบต้องรองรับการจัดการ Video

ข้อมูล:

- ชื่อ Video
- Description
- Thumbnail
- Duration
- ลำดับ
- Chapter
- สถานะ
- Video Preview
- Video สำหรับสมาชิก
- Video สำหรับทดลองเรียน

ระบบต้องสามารถกำหนดว่า Video ใดเป็น:

- Free / Trial
- Member Only
- Required
- Optional

---

# 10. Document Management

รองรับเอกสารประกอบการเรียน เช่น:

- PDF
- Worksheet
- แบบฝึกหัด
- เอกสารประกอบการเรียน

สามารถกำหนดสิทธิ์การเข้าถึงตาม Course / Curriculum / Enrollment

---

# 11. Curriculum Management

Curriculum คือหลักสูตรที่รวม Course หรือเนื้อหาหลายรายการเข้าด้วยกัน

ตัวอย่าง:

```text
หลักสูตรเตรียมสอบเข้า ม.1

├── คณิตศาสตร์
│   ├── Course A
│   └── Course B
├── วิทยาศาสตร์
│   └── Course C
└── แบบทดสอบ
```

การสร้าง Curriculum ต้องสามารถ:

- เลือก Course
- เลือก Subject
- เลือก Video
- เลือก Document
- กำหนดลำดับ
- กำหนดราคา
- กำหนดระยะเวลาใช้งาน

โดยสามารถเลือก Video มาใส่ใน Curriculum แยกจาก Course ได้

---

# 12. ระบบสมัครเรียน / Enrollment

ผู้เรียนสามารถได้รับสิทธิ์จาก:

- ซื้อ Course
- ซื้อ Curriculum
- Promotion
- Trial
- Staff เพิ่มสิทธิ์
- Admin เพิ่มสิทธิ์

ต้องกำหนดระยะเวลาการใช้งานได้ เช่น:

```text
3 เดือน
6 เดือน
12 เดือน
กำหนดวันเอง
```

ข้อมูลสำคัญ:

- วันเริ่มใช้งาน
- วันหมดอายุ
- สถานะ
- แหล่งที่มาของสิทธิ์

---

# 13. Learning Progress

ระบบต้องบันทึกความคืบหน้าการเรียน

แสดงข้อมูล เช่น:

```text
Course Progress
72%

เรียนแล้ว 18 / 25 บท
```

สามารถแสดงใน Student Dashboard

---

# 14. Video Resume

ผู้เรียนสามารถกลับมาเรียน Video ต่อจากตำแหน่งเดิมได้

ตัวอย่าง:

```text
Video ความยาว 60 นาที

ดูไปแล้ว 32:15 นาที

ครั้งถัดไป:
Resume 32:15
```

พฤติกรรมคล้ายระบบ Streaming เช่น Netflix

---

# 15. Assessment Management

ระบบรองรับ:

- แบบฝึกหัด
- Quiz
- ข้อสอบ
- Mock Exam
- แบบทดสอบวัดความรู้

สามารถสร้าง Assessment ได้ทั้ง:

1. ผูกกับ Course
2. ผูกกับ Chapter
3. ผูกกับ Video
4. เป็น Assessment อิสระ

---

# 16. Assessment อิสระ

สามารถสร้างข้อสอบที่ไม่จำเป็นต้องอยู่ใน Course

ผู้เรียนสามารถ:

- ซื้อข้อสอบ
- ได้ข้อสอบจาก Promotion
- ได้ข้อสอบฟรี
- ได้ข้อสอบแถมมากับ Course

---

# 17. Question Management

รองรับประเภทคำถาม เช่น:

- Single Choice
- Multiple Choice
- True / False
- Text Answer

สามารถกำหนด:

- คะแนน
- เฉลย
- ลำดับ
- คำอธิบายเฉลย

---

# 18. การทำข้อสอบ

เมื่อผู้เรียนเริ่มทำข้อสอบ ระบบต้อง:

1. สร้าง Attempt
2. บันทึกเวลาเริ่ม
3. บันทึกคำตอบ
4. ตรวจสอบเวลาสอบ
5. Submit
6. คำนวณคะแนน
7. บันทึกผลสอบ
8. อัปเดต Ranking

---

# 19. ประวัติการสอบ

ผู้เรียนสามารถดู:

- วันที่สอบ
- จำนวนข้อ
- คะแนน
- เปอร์เซ็นต์
- ผ่าน / ไม่ผ่าน
- จำนวนครั้งที่สอบ
- รายละเอียดคำตอบตามสิทธิ์ที่กำหนด

---

# 20. Ranking

ระบบต้องมี Ranking สำหรับสร้างแรงจูงใจในการเรียน

สามารถจัด Ranking ตาม:

- Course
- Assessment
- คะแนนรวม
- รายวัน
- รายสัปดาห์
- รายเดือน
- ทั้งหมด

ควรกำหนด Privacy ของ Ranking เช่น แสดงเฉพาะชื่อเล่น / ชื่อย่อได้

---

# 21. Trial Learning

ระบบต้องรองรับทดลองเรียน

สามารถกำหนด:

- Course ที่ทดลองได้
- Video ที่ทดลองได้
- จำนวนบท
- วันหมดอายุ
- สิทธิ์การเข้าถึง

ตัวอย่าง:

```text
ทดลองเรียนฟรี
3 Video
7 วัน
```

---

# 22. ระบบซื้อ Course / Curriculum

ผู้เรียนสามารถเลือกซื้อ:

- Course
- Curriculum
- Assessment
- หนังสือ
- สินค้าอื่น ๆ

สามารถซื้อหลายรายการใน Order เดียวได้

---

# 23. ระบบตะกร้าสินค้า

ผู้เรียนสามารถ:

- เพิ่มสินค้า
- ลบสินค้า
- แก้จำนวน
- ใช้ Coupon
- ตรวจสอบราคา
- Checkout

---

# 24. Order Management

ระบบต้องสร้าง Order สำหรับการซื้อทุกครั้ง

ข้อมูล:

- Order Number
- ผู้ซื้อ
- รายการ
- ราคา
- ส่วนลด
- ค่าส่ง
- ยอดสุทธิ
- Payment Status
- Order Status

---

# 25. Payment Gateway

รองรับ Payment Gateway

ตัวอย่างประเภท:

- Credit / Debit Card
- PromptPay
- Online Banking
- Payment Gateway อื่น ๆ

ระบบต้องตรวจสอบสถานะการชำระเงินจาก Backend / Gateway

---

# 26. Manual Payment

รองรับการชำระเงินแบบ:

- เงินสด
- โอนธนาคาร

Staff สามารถสร้าง Order และบันทึกการชำระเงินจาก Backend

หลังตรวจสอบแล้วสามารถเปิดสิทธิ์การเรียนให้ผู้เรียนได้

---

# 27. Payment Link

Staff สามารถสร้าง Payment Link ให้ลูกค้าได้

Flow:

```text
Staff สร้าง Order
        ↓
สร้าง Payment Link
        ↓
ส่ง Link ให้ลูกค้า
        ↓
ลูกค้าชำระเงิน
        ↓
ระบบตรวจสอบ
        ↓
Payment สำเร็จ
        ↓
เปิด Enrollment
```

---

# 28. หนังสือและสินค้า

ระบบต้องรองรับการขายหนังสือ

ข้อมูล:

- ชื่อหนังสือ
- รายละเอียด
- รูปภาพ
- ราคา
- Stock
- สถานะ

สามารถซื้อพร้อม Course ได้

---

# 29. Promotion

ระบบต้องรองรับ Promotion

ประเภทส่วนลด:

- ลดเป็น %
- ลดเป็นจำนวนเงิน
- ลดสูงสุดไม่เกินจำนวนที่กำหนด
- ซื้อครบจำนวน
- ซื้อ Course ที่กำหนด
- Promotion เฉพาะกลุ่มผู้ใช้งาน

---

# 30. Promotion Conditions

สามารถกำหนดเงื่อนไข Promotion ได้ เช่น:

- จำนวนครั้งที่ใช้งาน
- จำนวนผู้ใช้งาน
- วันเริ่ม
- วันสิ้นสุด
- Course ที่ร่วมรายการ
- Curriculum ที่ร่วมรายการ
- User Group
- ยอดซื้อขั้นต่ำ
- ต้องเรียน Video บางรายการก่อน
- ต้องมีประวัติการซื้อ
- ใช้งานได้เฉพาะผู้เรียนที่กำหนด

---

# 31. Coupon

ระบบต้องรองรับ Coupon Code

ตัวอย่าง:

```text
WELCOME100
SCHOOL20
MATH50
```

สามารถกำหนด:

- ส่วนลด
- วันเริ่ม
- วันหมดอายุ
- จำนวนการใช้ทั้งหมด
- จำนวนการใช้ต่อ User
- Course ที่ใช้ได้
- Curriculum ที่ใช้ได้
- ยอดซื้อขั้นต่ำ

---

# 32. Content Management

ระบบจัดการ Content สำหรับหน้าเว็บไซต์

รองรับ:

- Banner
- Popup
- News
- Article
- Announcement
- Promotion
- Course Promotion

สามารถกำหนด:

- Title
- รูปภาพ
- เนื้อหา
- Link
- วันเริ่มแสดง
- วันสิ้นสุด
- ตำแหน่งแสดงผล
- กลุ่มผู้ใช้งาน
- เงื่อนไขการแสดงผล

---

# 33. Banner / Popup

สามารถสร้าง Banner / Popup เพื่อประชาสัมพันธ์:

- Course ใหม่
- Course แนะนำ
- Promotion
- Coupon
- ข่าวสาร
- Campaign

สามารถเชื่อมโยงไปยัง:

- Course
- Curriculum
- Promotion
- Coupon

ตัวอย่าง:

```text
Popup
"เปิดเทอม ลด 20%"

[ดู Course]
[ใช้ Coupon]
```

---

# 34. Content Campaign

สามารถจัดการ Campaign การประชาสัมพันธ์

ตัวอย่าง:

```text
Back to School Campaign

Banner
+
Popup
+
Coupon
+
Promotion
+
Course
```

สามารถกำหนดวันเริ่มต้นและวันสิ้นสุด Campaign

---

# 35. Content Statistics

ระบบควรเก็บสถิติของ Banner / Popup

เช่น:

- Impression
- Click
- CTR

ตัวอย่าง:

```text
Banner A
Views: 10,500
Clicks: 850
CTR: 8.09%
```

---

# 36. Review Management

นักเรียนสามารถรีวิว Course / Curriculum

ข้อมูล:

- Rating
- Comment
- วันที่รีวิว

Admin สามารถ:

- Approve
- Reject
- Hide
- Delete

ระบบควรสามารถกำหนดได้ว่า Review จะแสดงหน้าเว็บไซต์ทันทีหรือรอ Admin ตรวจสอบก่อน

---

# 37. Student Dashboard

Student Dashboard ต้องออกแบบให้ใช้งานง่าย โดยเฉพาะบน iPad

ข้อมูลที่ควรแสดง:

```text
Course ของฉัน
        ↓
Progress
        ↓
เรียนต่อ
        ↓
ข้อสอบล่าสุด
        ↓
คะแนน
        ↓
Ranking
        ↓
Course ที่แนะนำ
```

ควรมี:

- Continue Learning
- Progress
- Course ที่กำลังเรียน
- Course ที่ใกล้หมดอายุ
- ผลสอบล่าสุด
- คะแนนรวม
- Ranking
- Notification

---

# 38. Responsive / iPad

Frontend ต้องรองรับ:

- Desktop
- Tablet
- iPad
- Mobile

โดยเน้น iPad เป็นอุปกรณ์หลักสำหรับการเรียน

UI ควร:

- ปุ่มใหญ่
- อ่านง่าย
- Navigation ชัดเจน
- ลดขั้นตอน
- รองรับ Touch
- Video Player ใช้งานง่าย

สำหรับกลุ่มเด็กประถม ควรออกแบบ UI ให้:

- ใช้งานง่าย
- สีสันเหมาะสม
- Icon เข้าใจง่าย
- ตัวอักษรอ่านง่าย
- ลดข้อมูลที่ซับซ้อน

---

# 39. Dark Mode

Frontend รองรับ Dark Mode

ผู้เรียนสามารถเลือก:

```text
Light
Dark
System
```

---

# 40. Notification

ระบบแจ้งเตือน เช่น:

- สมัครเรียนสำเร็จ
- Payment สำเร็จ
- Course ใกล้หมดอายุ
- Course หมดอายุ
- มี Course ใหม่
- Promotion ใหม่
- ผลสอบออกแล้ว
- ข่าวสารสำคัญ

---

# 41. Report Dashboard — Student

ผู้เรียนสามารถดู:

- Course Progress
- จำนวน Video ที่เรียน
- เวลาที่เรียน
- Course ที่เรียนจบ
- คะแนนสอบ
- คะแนนเฉลี่ย
- จำนวนข้อสอบที่ทำ
- Ranking
- Course ที่กำลังเรียน

---

# 42. Report Dashboard — Admin

Admin Dashboard ควรแสดง:

- จำนวนสมาชิก
- สมาชิกใหม่
- จำนวน Course
- จำนวนผู้เรียน
- ยอดขาย
- จำนวน Order
- รายได้
- Payment
- Course ยอดนิยม
- Course ที่มีผู้เรียนมากที่สุด
- Promotion Performance
- Coupon Usage
- Assessment Statistics

สามารถเลือกช่วงเวลา:

```text
Today
7 Days
30 Days
This Month
This Year
Custom Range
```

---

# 43. Financial Management

ระบบจัดการด้านการเงิน

รองรับ:

- Order
- Payment
- Refund
- Discount
- Coupon
- Promotion
- Manual Payment
- Payment Gateway
- รายงานยอดขาย

ควรสามารถ Export ข้อมูลได้ เช่น:

```text
Excel / CSV
```

---

# 44. Staff Management

Admin สามารถจัดการ Staff

ข้อมูล:

- ชื่อ
- Email
- เบอร์โทร
- Role
- Status

สามารถกำหนด Permission แยกตามหน้าที่

ตัวอย่าง:

```text
Staff Sales
→ Order / Payment

Staff Content
→ Banner / Popup / News

Staff Course
→ Course / Video

Staff Support
→ Student
```

---

# 45. Role & Permission

ระบบต้องรองรับ Permission แบบละเอียด

ตัวอย่าง:

```text
course.view
course.create
course.update
course.delete

student.view
student.update

order.view
order.create
order.update

payment.view
payment.approve

promotion.view
promotion.create

report.view
```

---

# 46. Audit Log

ระบบต้องเก็บประวัติการทำงานของ Admin / Staff

เช่น:

```text
ใคร
ทำอะไร
กับข้อมูลอะไร
เมื่อไร
จาก IP อะไร
```

ตัวอย่าง:

```text
Admin A
แก้ราคา Course คณิตศาสตร์
จาก 1,500 → 1,200
เวลา 10:30
```

---

# 47. Video Security

ระบบต้องมีมาตรการป้องกันการนำ Video ไปใช้งานภายนอก

แนวทาง:

```text
Private Storage
      ↓
CDN
      ↓
HLS Streaming
      ↓
Signed URL
      ↓
ตรวจสอบสิทธิ์
      ↓
Video Player
```

ไม่ควรเปิด URL Video แบบ Public โดยตรง

ควรมี:

- Signed URL
- URL Expiration
- Enrollment Check
- Token Validation
- Rate Limit
- Session Validation

---

# 48. Account Sharing Protection

ระบบต้องป้องกันการใช้บัญชีร่วมกัน

สามารถตรวจสอบ:

- จำนวน Session
- Device
- IP
- User Agent
- Login History

สามารถกำหนดจำนวน Device สูงสุด เช่น:

```text
Maximum Active Devices = 2
```

หากเกินจำนวนที่กำหนด ระบบสามารถ:

- แจ้งเตือน
- ปิด Session เก่า
- ระงับการเข้าใช้งานชั่วคราวตาม Policy

---

# 49. Course Access Control

ก่อนเข้า Course / Video / Document / Assessment ระบบต้องตรวจสอบ:

```text
User Login
+
Enrollment
+
Enrollment Status
+
Expiration Date
+
Content Permission
```

หากหมดอายุให้ปิดสิทธิ์ทันที

---

# 50. ระบบค้นหา

ควรมี Search สำหรับ:

- Course
- Curriculum
- Subject
- Instructor
- Assessment
- Book
- Article

สามารถ Filter ตาม:

- หมวดหมู่
- ราคา
- ระดับ
- วิชา
- ประเภท
- สถานะ

---

# 51. ระบบแนะนำ Course

ระบบสามารถแนะนำ Course เช่น:

- Course ยอดนิยม
- Course ใหม่
- Course ที่เกี่ยวข้อง
- Course ที่นักเรียนสนใจ
- Course ตามประวัติการเรียน
- Course ตาม Subject

---

# 52. Wishlist / Favorite

แนะนำให้มีระบบ Favorite Course

นักเรียนสามารถ:

- เพิ่ม Course ที่สนใจ
- ลบ Favorite
- ดูรายการ Favorite

สามารถนำข้อมูลไปใช้ทำ Marketing ได้

---

# 53. Course Expiration Notification

ก่อนสิทธิ์ Course หมดอายุ ระบบควรแจ้งเตือน เช่น:

```text
เหลือ 7 วัน
เหลือ 3 วัน
เหลือ 1 วัน
```

เพื่อให้ผู้เรียนสามารถต่ออายุได้

---

# 54. Refund / Cancellation

ควรกำหนด Business Rule สำหรับ:

- ยกเลิก Order
- Refund
- ยกเลิก Enrollment
- คืนเงิน

โดย Admin สามารถอนุมัติตามเงื่อนไขของบริษัท

---

# 55. File / Media Management

ระบบควรมี Media Management สำหรับ:

- Course Thumbnail
- Banner
- Popup
- Instructor Image
- Article Image
- Document
- Video Thumbnail

ควรแยก Public Media และ Private Media

---

# 56. API

Backend ควรออกแบบ API สำหรับ Frontend

กลุ่ม API เช่น:

```text
/auth
/users
/courses
/curriculums
/videos
/documents
/enrollments
/progress
/assessments
/orders
/payments
/promotions
/coupons
/reviews
/notifications
/reports
```

API ต้องมี Authentication และ Authorization

---

# 57. Queue / Background Jobs

งานที่ใช้เวลานานควรทำผ่าน Queue เช่น:

- ส่ง Notification
- Email
- Report
- Import Data
- Export Data
- Video Processing
- สร้าง Thumbnail
- สรุป Ranking

---

# 58. Backup & Recovery

ระบบต้องมี:

- Database Backup
- File Backup
- Backup Schedule
- Recovery Procedure

ควรกำหนด Retention ของ Backup ตามนโยบายบริษัท

---

# 59. Logging & Monitoring

ระบบควรเก็บ:

- Application Log
- Error Log
- Login Log
- Payment Log
- API Log
- Audit Log

เพื่อใช้ตรวจสอบปัญหาและความปลอดภัย

---

# 60. Non-Functional Requirements

## Performance

ระบบควรตอบสนองได้รวดเร็วและรองรับผู้ใช้งานพร้อมกันตาม Capacity ที่กำหนด

## Security

ต้องมี:

- HTTPS
- Authentication
- Authorization
- Rate Limiting
- Input Validation
- CSRF Protection
- XSS Protection
- SQL Injection Protection
- Secure File Access

## Scalability

ควรออกแบบให้สามารถเพิ่ม:

- Server
- Database
- Storage
- CDN

ได้ในอนาคต

---

# 61. Business Flow หลัก

## สมัครสมาชิก

```text
Guest
 ↓
Register
 ↓
Email / Google / Apple / LINE
 ↓
Create Student Account
 ↓
Login
```

## ซื้อ Course

```text
เลือก Course
 ↓
Cart
 ↓
Coupon / Promotion
 ↓
Checkout
 ↓
Payment
 ↓
Payment Success
 ↓
Enrollment
 ↓
เริ่มเรียน
```

## เรียน Course

```text
My Course
 ↓
Course
 ↓
Chapter
 ↓
Video
 ↓
บันทึก Progress
 ↓
Resume ครั้งถัดไป
```

## ทำข้อสอบ

```text
Assessment
 ↓
Start
 ↓
ทำข้อสอบ
 ↓
Submit
 ↓
Calculate Score
 ↓
Save Result
 ↓
Ranking
```

## Manual Payment

```text
Staff
 ↓
สร้าง Order
 ↓
รับเงิน / ตรวจสอบ Slip
 ↓
Approve Payment
 ↓
Grant Enrollment
 ↓
Student เริ่มเรียน
```

---

# 62. Recommended Additional Features

ฟังก์ชันต่อไปนี้แนะนำให้พิจารณาเพิ่มเติมเพื่อให้ระบบสมบูรณ์:

1. Wishlist / Favorite Course
2. Course Recommendation
3. Notification Center
4. Course Expiration Reminder
5. Refund Management
6. Certificate หลังเรียนจบ
7. Student Achievement / Badge
8. Learning Streak
9. Export Report
10. Email Notification
11. LINE Notification
12. Search และ Filter
13. FAQ / Help Center
14. Contact Support
15. Instructor Dashboard
16. Media Library
17. System Settings
18. Backup / Recovery
19. Activity Log
20. Campaign Analytics

---

# 63. Certificate

แนะนำให้รองรับ Certificate เมื่อเรียน Course / Curriculum ครบตามเงื่อนไข

สามารถกำหนด:

- ต้องเรียนครบกี่ %
- ต้องสอบผ่านหรือไม่
- คะแนนขั้นต่ำ
- ชื่อผู้เรียน
- Course
- วันที่จบ
- Certificate Number

---

# 64. Achievement

ระบบสามารถสร้าง Achievement เพื่อเพิ่ม Engagement

ตัวอย่าง:

```text
เรียนครบ 10 Video
สอบผ่านครั้งแรก
เรียนครบ 100%
ทำข้อสอบ 10 ครั้ง
ได้คะแนนเกิน 90%
```

---

# 65. Acceptance Criteria ระดับระบบ

ระบบถือว่าพร้อมส่งมอบเมื่อ:

1. ผู้เรียนสามารถสมัครสมาชิกได้
2. ผู้เรียนสามารถ Login ได้ทุก Provider ที่กำหนด
3. Admin สามารถสร้าง Course ได้
4. Admin สามารถสร้าง Curriculum ได้
5. Course สามารถมี Video และ Document ได้
6. ผู้เรียนสามารถซื้อ Course ได้
7. Payment สามารถตรวจสอบสถานะได้
8. Enrollment ถูกสร้างหลังการชำระเงินสำเร็จ
9. ผู้เรียนสามารถเรียน Video ได้ตามสิทธิ์
10. ระบบบันทึก Video Progress ได้
11. ผู้เรียนสามารถ Resume Video ได้
12. ผู้เรียนสามารถทำ Assessment ได้
13. ระบบคำนวณคะแนนได้
14. ผู้เรียนสามารถดูประวัติผลสอบได้
15. Ranking สามารถแสดงผลได้
16. Promotion / Coupon ทำงานตามเงื่อนไข
17. Trial สามารถจำกัดสิทธิ์ได้
18. Account Sharing Protection ทำงานตาม Policy
19. Admin สามารถจัดการ Banner / Popup / News / Article ได้
20. Admin สามารถดู Dashboard / Report ได้
21. Staff สามารถสร้าง Order และ Manual Payment ได้
22. ระบบมี Audit Log
23. Video ไม่สามารถเข้าถึงผ่าน Public URL โดยตรง
24. Frontend ใช้งานได้บน iPad และ Mobile
25. ระบบมี Light / Dark Mode

---

# 66. ขอบเขตที่ควรแยกเป็น Phase

## Phase 1 — Core Learning Platform

- Authentication
- User
- Role / Permission
- Course
- Curriculum
- Subject
- Chapter
- Video
- Document
- Enrollment
- Learning Progress
- Video Resume
- Trial Learning
- Student Dashboard

## Phase 2 — Assessment & Commerce

- Exercise
- Quiz
- Exam
- Question
- Answer
- Score
- Ranking
- Product / Book
- Cart
- Order
- Payment
- Payment Gateway
- Manual Payment
- Payment Link
- Promotion
- Coupon
- Review

## Phase 3 — Management, Marketing & Reporting

- Admin Dashboard
- Student Report
- Sales Report
- Learning Report
- Content Management
- Banner
- Popup
- News
- Article
- Campaign
- Content Statistics
- Notification
- Staff Management
- Advanced Permission
- Audit Log
- Advanced Security
- Account Sharing Protection
- Video Security
- Recommendation
- Certificate / Achievement

---

# 67. สรุปภาพรวมระบบ

```text
                    CLICK CLASS TUTOR
                           │
          ┌────────────────┴────────────────┐
          │                                 │
       FRONTEND                          BACKEND
          │                                 │
   ┌──────┼──────┐             ┌────────────┼────────────┐
   │      │      │             │            │            │
 Student Course Content      Learning    Commerce     Management
   │      │      │             │            │            │
   │      │      │             │            │            │
   │      │      └── Banner    │            │            │
   │      │          Popup     │            │            │
   │      │          News      │            │            │
   │      │          Article   │            │            │
   │      │                    │            │            │
   │      └── Curriculum       │            │            │
   │                           │            │            │
   └── Dashboard ──────────────┤            │            │
                               │            │            │
                         Video / Document   Order       Staff
                         Progress           Payment     Permission
                         Assessment         Promotion   Report
                         Ranking             Coupon      Audit
```

---

# 68. Technology Reference

Backend:

```text
Laravel 12
PHP
MySQL
REST API
Queue
Cache
```

Frontend:

```text
Responsive Web
iPad First
Mobile Support
Dark Mode
```

Admin:

```text
Laravel
TailAdmin
```

Video:

```text
Object Storage
CDN
HLS
Signed URL
```

---

# 69. เอกสารที่เกี่ยวข้อง

เอกสารนี้เป็น **Business Requirements Document (BRD)** สำหรับใช้เป็นหลักในการวิเคราะห์และพัฒนาระบบ

เอกสารระดับ Technical ที่ควรจัดทำต่อจาก BRD:

```text
01-business-requirements.md
02-system-requirements.md
03-database.md
04-api.md
05-backend.md
06-frontend.md
07-security.md
08-deployment.md
```

BRD เป็นเอกสารระดับ Business Requirement ส่วนรายละเอียด Database, API, Laravel Architecture และ Implementation ควรจัดทำแยกเป็น Technical Specification เพื่อให้ทีมพัฒนาสามารถนำไป Implement ได้อย่างเป็นระบบ
