# Email Service (Gmail)

ระบบส่งอีเมลกลางของ Click Class Tutor แยก layout เป็น 2 แบบ

- **Staff** — พนักงาน / หลังบ้าน (แจ้งรหัสผ่าน, แจ้งเตือนภายใน)
- **Customer** — ลูกค้า / นักเรียน (ข่าวสาร, สถานะชำระเงิน, รหัสผ่าน, ลืมรหัสผ่าน)

## Gmail setup

1. เปิด [Google Account → Security](https://myaccount.google.com/security)
2. เปิด **2-Step Verification**
3. สร้าง **App password** สำหรับ Mail
4. ใส่ค่าใน `.env`

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_FROM_ADDRESS="your-gmail@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_SUPPORT_ADDRESS="your-gmail@gmail.com"
```

จากนั้น:

```bash
php artisan config:clear
php artisan queue:work
```

อีเมลถูก **queue** เป็นค่าเริ่มต้น เพราะ `QUEUE_CONNECTION=database`

## SMTP ของโฮสติ้ง (ค่าที่ใช้อยู่)

ตอนนี้ส่งผ่าน SMTP ของโฮสติ้ง `119.59.104.20:587` (STARTTLS) ในนาม `no-reply@clickclasstutor.com`

```env
MAIL_HOST=119.59.104.20
MAIL_PORT=587
MAIL_USERNAME=no-reply@clickclasstutor.com
MAIL_SMTP_PEER_NAME=ns27.hostinglotus.net
```

`MAIL_SMTP_PEER_NAME` ต้องตรงกับชื่อในใบรับรอง TLS ของเซิร์ฟเวอร์ SMTP ปัจจุบันของ DirectAdmin คือ `server-119-59-113-162.da.direct` (IP `119.59.113.162`) ถ้าใส่ชื่อเก่าเช่น `ns27.hostinglotus.net` จะส่งไม่ได้ด้วย `Peer certificate subjectAltName did not match` ถ้าโฮสติ้งย้ายเซิร์ฟเวอร์ ให้ตรวจ CN/SAN ใหม่แล้วแก้ค่านี้ อย่าปิดการตรวจใบรับรอง

## วิธีเรียกใช้

```php
use App\Services\Mail\MailService;

public function __construct(private MailService $mail) {}
```

### พนักงาน

```php
// แจ้งรหัสผ่านพนักงาน
$this->mail->sendStaffPassword($user, 'TempPass@123');

// แจ้งเตือนทั่วไป
$this->mail->sendStaffNotification(
    to: $user,
    subject: 'มีออเดอร์ใหม่รอตรวจสอบ',
    message: "ออเดอร์ #ORD-001 รอตรวจสลิป",
    actionUrl: url('/orders/1'),
    actionText: 'เปิดออเดอร์',
);
```

### แจ้งเตือนสมัครใหม่ / ออเดอร์ใหม่

`App\Support\AdminNotifier` ส่งอีเมล staff ไปที่อีเมลที่ตั้งใน **ตั้งค่าระบบ** (`/settings/system`) คีย์ `admin_notification_emails` (หลายอีเมลได้) พร้อมสร้างแจ้งเตือนในกระดิ่งหลังบ้าน ถ้าไม่ตั้งอีเมลไว้จะมีแค่กระดิ่ง

### ลูกค้า / นักเรียน

```php
$this->mail->sendCustomerPassword($user, 'Student@123');

$this->mail->sendCustomerEmailVerification($user, $verifyUrl);

$this->mail->sendCustomerForgotPassword($user, $resetUrl);

$this->mail->sendCustomerPaymentStatus(
    to: $user,
    status: 'paid',
    orderNo: 'ORD-001',
    amount: '1,500.00 บาท',
    message: 'ชำระเงินสำเร็จ ระบบเปิดสิทธิ์เรียนให้แล้ว',
    statusLabel: 'ชำระเงินสำเร็จ',
);

$this->mail->sendCustomerAnnouncement(
    to: $user,
    subject: 'วิชาใหม่เปิดแล้ว',
    message: 'คณิตศาสตร์ ป.6 พร้อมเรียน',
);
```

### แนบไฟล์

`MailService::send()` / `sendCustomer()` รับ `attachments: [['name' => 'a.pdf', 'mime' => 'application/pdf', 'data' => $binary]]` ไฟล์ถูกเก็บเป็น base64 ใน `TemplatedMail` จึงเข้าคิวได้

### ส่งแบบกำหนดเอง

```php
use App\Enums\MailAudience;

$this->mail->send(
    audience: MailAudience::Customer,
    to: 'student@example.com',
    subject: 'หัวข้ออีเมล',
    view: 'emails.customer.notification',
    data: ['message' => 'เนื้อหา'],
);

// ส่งทันทีไม่เข้าคิว
$this->mail->sendNow(
    audience: MailAudience::Staff,
    to: $user,
    subject: 'ด่วน',
    view: 'emails.staff.notification',
    data: ['message' => 'ข้อความ'],
);
```

### สั่งซื้อแบบไม่สมัครสมาชิก

`App\Support\GuestCheckout` **ไม่ส่งอีเมลตอนสั่งซื้อ** ข้อมูลบัญชีผู้ใช้งานส่งหลังชำระเงินสำเร็จเท่านั้น

- อีเมลใหม่: ระบบสร้างบัญชีด้วยรหัสผ่านสุ่มที่ไม่มีใครรู้ และตั้ง `users.credentials_pending_at` ว่ายังรอส่งข้อมูลบัญชี
- อีเมลเป็นบัญชีนักเรียนอยู่แล้ว: ใช้บัญชีเดิม ไม่เปลี่ยนรหัสผ่าน เพราะใครก็พิมพ์อีเมลคนอื่นตอนสั่งซื้อได้

ถ้ารหัสผ่านของบัญชีที่รอส่งถูกตั้งทางอื่นก่อนชำระ (เช่น แอดมินรีเซ็ต) ระบบล้าง `credentials_pending_at` และจะไม่ทับรหัสผ่านนั้น (`User::booted()`)

### ชำระเงินสำเร็จ

เมื่อออเดอร์เปลี่ยนเป็นชำระแล้วครั้งแรกผ่าน `StudentPurchase::review()` (QR พร้อมเพย์, บัตร, แอดมินอนุมัติสลิปในหน้าการเงิน, ออเดอร์ยอด 0) ระบบส่ง `emails.customer.purchase` "ผลการสั่งซื้อ #..." ให้เจ้าของออเดอร์ มีเลขคำสั่งซื้อ เลขที่ใบเสร็จ ยอดที่จ่ายจริง (รวมค่าธรรมเนียม) รายการ (วิชา หลักสูตร ข้อสอบ มีบรรทัดช่วงสิทธิ์ใต้ชื่อ เช่น "เรียนได้ 30 วัน · 3 พ.ย. 2569 – 3 ธ.ค. 2569" หรือ "เรียนได้ไม่จำกัดเวลา · เริ่ม 3 พ.ย. 2569" จาก `order_items.metadata.access` ผ่าน `App\Support\AccessPeriod` ต่ออายุขณะสิทธิ์ยังใช้ได้เริ่มจากวันหมดอายุเดิม) และปุ่มเข้าสู่ระบบ (`{STUDENT_APP_URL}/login`) แนบใบเสร็จรับเงิน PDF (`receipt-{เลขที่}.pdf` ดู `docs/receipts.md`) ถ้าบัญชียังรอส่งข้อมูลบัญชี (`credentials_pending_at` สร้างจากการสั่งซื้อแบบ guest) ระบบตั้งรหัสผ่านใหม่และใส่ "ข้อมูลบัญชีผู้ใช้งาน" (อีเมลและรหัสผ่าน) ในอีเมลนี้ (รหัสผ่านแบบง่าย ดูหัวข้อรหัสผ่านที่ส่งให้นักเรียน) แล้วล้างสถานะรอส่ง ถ้าส่งไม่สำเร็จ rollback รหัสผ่านและยังรอส่งเหมือนเดิม บัญชีอื่นไม่มีรหัสผ่านในอีเมล ส่งหลังตอบ request แล้ว (`dispatch()->afterResponse()`) webhook ของ gateway จึงไม่ช้าลง ถ้าส่งไม่สำเร็จบันทึก error ใน log การชำระที่แอดมินบันทึกเองในหน้านักเรียนยังใช้อีเมลเดิมของขั้นตอนนั้น (มีรหัสผ่านเมื่อซื้อครั้งแรก)

### ลิงก์ในอีเมลของนักเรียน

ปุ่มเข้าสู่ระบบในอีเมลนักเรียนทุกฉบับ (ผลการสั่งซื้อ, รหัสผ่าน) ชี้ไปที่ `{STUDENT_APP_URL}/login` ของเว็บ Nuxt และลิงก์ยืนยันอีเมลชี้ไปที่ `{STUDENT_APP_URL}/verify-email?...` สร้างจาก `App\Support\StudentApp` ต้องตั้ง `STUDENT_APP_URL` บน production ไม่เช่นนั้นลิงก์อาจชี้ไป `localhost` หรือเว็บหลังบ้าน (ดู `docs/student-api.md`) อีเมลของผู้ใช้งานหลังบ้านยังชี้ไปที่ `/login` ของ Laravel

### รหัสผ่านที่ส่งให้นักเรียน

รหัสผ่านในอีเมล "ผลการสั่งซื้อ" (บัญชีจากการสั่งซื้อแบบ guest และการซื้อครั้งแรกที่แอดมินบันทึกชำระในหน้านักเรียน) สร้างด้วย `App\Support\SimplePassword` เป็นตัวอักษรพิมพ์ใหญ่ 1 ตัว (A–Z ยกเว้น O เพราะสับสนกับเลข 0) ตามด้วยตัวเลข 5 ตัว เช่น `K48217` อีเมลแนะนำให้เปลี่ยนรหัสผ่านหลังเข้าสู่ระบบ รหัสผ่านของผู้ใช้งานหลังบ้าน (สร้าง / รีเซ็ตในหน้าผู้ใช้งาน) ยังเป็นแบบสุ่ม 12 ตัว

แอดมินตั้งรหัสผ่านนักเรียนได้ในฟอร์มเพิ่มและแก้ไขนักเรียน

- **ฟอร์ม:** มีช่องเดียว พิมพ์เองหรือกด "สุ่มรหัส" ได้ รหัสที่สุ่มเป็นรูปแบบเดียวกับ `SimplePassword` และต้องมีอย่างน้อย 6 ตัวอักษร
- **ตอนสร้าง:** ถ้าเว้นว่าง ระบบออกรหัสให้ตอนชำระเงินครั้งแรกเหมือนเดิม
- **ซื้อครั้งแรกที่แอดมินบันทึกชำระ** (`StudentPurchase::pay()`): ถ้านักเรียนมีรหัสที่ระบบเก็บสำเนาไว้แล้ว (`users.issued_password`) ระบบใช้รหัสนั้นต่อและส่งในอีเมลผลการซื้อ ไม่ออกรหัสใหม่ทับ
  - กรณีนี้รวมถึงรหัสที่แอดมินตั้งไว้ และรหัสที่นักเรียนตั้งเองตอนสมัครสมาชิก
  - ระบบออกรหัส `SimplePassword` ใหม่เฉพาะเมื่อยังไม่มีสำเนารหัส

แอดมินเปิดดูรหัสผ่านปัจจุบันของนักเรียนได้ในกล่อง "ข้อมูลเข้าสู่ระบบ" (`x-students.login-credentials`) ซึ่งอยู่ทั้งในหน้านักเรียน (`/students/{student}`) และหน้ารายละเอียดสมัครเรียน (`/enrollments/{order}`) รวมถึงรหัสที่นักเรียนตั้งหรือเปลี่ยนเอง

- **ดูรหัสผ่าน:** `POST /students/{student}/login-password` ระบบเก็บสำเนาเข้ารหัสใน `users.issued_password` ดู `docs/database.md`
- **บัญชีที่ตั้งรหัสก่อนเปิดระบบนี้:** ยังไม่มีสำเนาจนกว่านักเรียนจะเข้าสู่ระบบสำเร็จครั้งถัดไป ระหว่างนั้นแอดมินกด "สร้างรหัสผ่านใหม่" ได้ (`POST /students/{student}/login-password/reset`)
  - ได้รหัสแบบ `SimplePassword` และรหัสเดิมใช้ไม่ได้ทันที
  - ลบ token ของนักเรียนทุกอุปกรณ์
  - ไม่ส่งอีเมล แอดมินต้องแจ้งนักเรียนเอง
- **บัญชีที่ยังรอรับรหัสผ่านหลังชำระเงิน** (`credentials_pending_at`) สร้างรหัสใหม่ไม่ได้
- **Activity Log:** ทุกครั้งที่เปิดดูบันทึกเป็น `student.password_viewed` ทุกครั้งที่สร้างใหม่บันทึกเป็น `student.password_reset` และไม่บันทึกตัวรหัสผ่าน

## ทดสอบ

```bash
# layout พนักงาน
php artisan mail:test you@gmail.com --audience=staff --type=password --sync
php artisan mail:test you@gmail.com --audience=staff --type=notification --sync

# layout ลูกค้า
php artisan mail:test you@gmail.com --audience=customer --type=password --sync
php artisan mail:test you@gmail.com --audience=customer --type=verify-email --sync
php artisan mail:test you@gmail.com --audience=customer --type=forgot-password --sync
php artisan mail:test you@gmail.com --audience=customer --type=payment --sync
php artisan mail:test you@gmail.com --audience=customer --type=announcement --sync
```

## ไฟล์สำคัญ

```text
app/Services/Mail/MailService.php
app/Mail/TemplatedMail.php
app/Enums/MailAudience.php
config/cct_mail.php
resources/views/emails/layouts/staff.blade.php
resources/views/emails/layouts/customer.blade.php
resources/views/emails/staff/*
resources/views/emails/customer/*
```
