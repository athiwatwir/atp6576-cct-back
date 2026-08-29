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

## ทดสอบ

```bash
# layout พนักงาน
php artisan mail:test you@gmail.com --audience=staff --type=password --sync
php artisan mail:test you@gmail.com --audience=staff --type=notification --sync

# layout ลูกค้า
php artisan mail:test you@gmail.com --audience=customer --type=password --sync
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
