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

`MAIL_SMTP_PEER_NAME` ต้องตรงกับชื่อในใบรับรอง TLS ของเซิร์ฟเวอร์ ใบรับรองปัจจุบันออกให้ `ns27.hostinglotus.net` (Let's Encrypt) ถ้าใส่ `mail.clickclasstutor.com` จะส่งไม่ได้ด้วยข้อความ `certificate verify failed` ถ้าโฮสติ้งเปลี่ยนเซิร์ฟเวอร์หรือใบรับรอง ให้ตรวจชื่อใหม่แล้วแก้ค่านี้ อย่าปิดการตรวจใบรับรอง

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
    subject: 'คอร์สใหม่เปิดแล้ว',
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

ส่งทันที (ไม่เข้าคิว) จาก `App\Support\GuestCheckout`

| กรณี | template | เนื้อหา |
|------|------|------|
| อีเมลใหม่ ระบบสร้างบัญชีให้ | `emails.customer.guest-account` | อีเมล รหัสผ่านสุ่ม เลขคำสั่งซื้อ ยอด รายการ ปุ่มเข้าสู่ระบบ (`{STUDENT_APP_URL}/login`) |
| อีเมลเป็นบัญชีนักเรียนอยู่แล้ว | `emails.customer.notification` | แจ้งว่ามีคำสั่งซื้อด้วยอีเมลนี้ ให้เข้าสู่ระบบด้วยบัญชีเดิม ไม่มีรหัสผ่าน และไม่เปลี่ยนรหัสผ่าน |

ถ้าส่งไม่สำเร็จ ออเดอร์ยังถูกสร้าง API ตอบ `account_email_sent: false` และบันทึก error ใน log

ระบบไม่ส่งรหัสผ่านให้บัญชีที่มีอยู่แล้ว เพราะใครก็พิมพ์อีเมลคนอื่นตอนสั่งซื้อได้ เจ้าของบัญชีใช้รหัสผ่านเดิม

### ชำระเงินสำเร็จ

เมื่อออเดอร์เปลี่ยนเป็นชำระแล้วครั้งแรกผ่าน `StudentPurchase::review()` (QR พร้อมเพย์, บัตร, แอดมินอนุมัติสลิปในหน้าการเงิน, ออเดอร์ยอด 0) ระบบส่ง `emails.customer.purchase` "ผลการสั่งซื้อ #..." ให้เจ้าของออเดอร์ มีเลขคำสั่งซื้อ เลขที่ใบเสร็จ ยอดที่จ่ายจริง (รวมค่าธรรมเนียม) รายการ และปุ่มเข้าสู่ระบบ (`{STUDENT_APP_URL}/login`) แนบใบเสร็จรับเงิน PDF (`receipt-{เลขที่}.pdf` ดู `docs/receipts.md`) ไม่มีรหัสผ่าน ส่งหลังตอบ request แล้ว (`dispatch()->afterResponse()`) webhook ของ gateway จึงไม่ช้าลง ถ้าส่งไม่สำเร็จบันทึก error ใน log การชำระที่แอดมินบันทึกเองในหน้านักเรียนยังใช้อีเมลเดิมของขั้นตอนนั้น (มีรหัสผ่านเมื่อซื้อครั้งแรก)

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
