# ออเดอร์ (Orders)

แอดมินแยกรายการขายหนังสือกับรายการสมัครเรียน (ซื้อวิชา หลักสูตร หรือข้อสอบ)

- **การขาย → ออเดอร์หนังสือ** (`/orders`) แสดงเฉพาะ `orders.kind = book` และจัดการที่อยู่จัดส่งกับพัสดุ
- **การขาย → สมัครเรียน** (`/enrollments`) แสดงเฉพาะ `orders.kind = enrollment` ไม่มีจัดส่ง

หน้ารายการ หน้ารายละเอียด และหน้าแก้ไขของสองชนิดนี้แยกกัน

- **หนังสือ:** ใช้ `/orders/{order}` และ `/orders/{order}/edit`
- **สมัครเรียน:** ใช้ `/enrollments/{order}` และ `/enrollments/{order}/edit` (`EnrollmentOrderController`, `resources/views/pages/enrollments/*`)
- เปิด URL ผิดชนิดแล้ว ระบบ redirect ไปหน้าที่ถูกต้อง
- ลิงก์ออเดอร์ในหน้าอื่น (แดชบอร์ด การเงิน คูปอง นักเรียน) ใช้ `Order::adminUrl()`
- การบันทึก ลบ ลิงก์ชำระเงิน และใบเสร็จ ยังใช้ route `orders.*` ร่วมกัน
- กล่องการชำระเงินอยู่ใน `pages/orders/partials/payment-card.blade.php`

หน้ารายการสมัครเรียน

- กรองตามประเภทรายการ (`type` = `course` / `curriculum` / `exam`) สถานะชำระเงิน และสถานะออเดอร์
- ค้นหาจากเลขออเดอร์ ชื่อนักเรียน อีเมล เบอร์โทร หรือชื่อรายการ
- แต่ละรายการแสดงประเภท แพ็กเกจ และช่วงสิทธิ์เรียน (`order_items.metadata.access`)
- ปุ่ม "สมัครเรียน" เปิดหน้าต่างเลือกนักเรียน ค้นหาจากชื่อ อีเมล หรือเบอร์โทร (`GET /enrollments/students?search=` แสดงสูงสุด 10 คน ล่าสุดก่อน) กดชื่อแล้วไปหน้าสร้างออเดอร์ของนักเรียนคนนั้น (`/students/{student}/orders/create`) แล้วชำระเงินตามขั้นตอนเดิม ถ้ายังไม่มีบัญชีกด "เพิ่มนักเรียนใหม่" ไปหน้าสร้างนักเรียนก่อน

หน้าสร้างออเดอร์ของนักเรียน (`/students/{student}/orders/create`)

- **หน้าต่างเลือกรายการ:** กด "เพิ่มรายการ" เพื่อเปิด
  - กรองตามประเภท (ทั้งหมด / วิชา / หลักสูตร / ข้อสอบ) ระดับชั้น และชนิดวิชา ชนิดวิชาใช้ได้เฉพาะวิชา
  - ค้นหาจากชื่อ รหัส ระดับชั้น หรือชนิดวิชา
  - ติ๊กได้หลายรายการพร้อมกัน รายการที่อยู่ในออเดอร์แล้วจะติ๊กซ้ำไม่ได้
- **ข้อมูลที่ใช้:** มาจาก `StudentPurchase::catalog()` แต่ละรายการมี `code`, `category`, `subject`, `price` และ `plans`
- **รายการที่เลือก:** แสดงเป็นรายการพร้อมแพ็กเกจและราคา รายการที่มีแพ็กเกจเดียวระบบเลือกแพ็กเกจให้ ถ้ามีหลายแพ็กเกจต้องเลือกเอง
- **ส่งฟอร์ม:** ปุ่ม "ถัดไป: ชำระเงิน" กดได้เมื่อมีอย่างน้อย 1 รายการ ฟอร์มยังส่ง `items[n][type|id|plan_id]` เหมือนเดิม

หน้ารายละเอียดสมัครเรียน

- รายการที่สมัคร พร้อมแพ็กเกจและช่วงสิทธิ์เรียน
- สิทธิ์เรียนที่ออเดอร์นี้สร้าง (`enrollments`) พร้อมสถานะ วันเริ่ม วันหมดอายุ และจำนวนวันที่เหลือ
- หัวข้อและชื่อแท็บเป็น "ชื่อนักเรียน เลขออเดอร์"
- ข้อมูลนักเรียน (รวมโรงเรียนและจังหวัด) การชำระเงิน และประวัติออเดอร์
- ปุ่ม **สร้างลิงก์ชำระเงินออนไลน์** ที่แถบปุ่มบนและในกล่องการชำระเงิน (เมื่อยังไม่ชำระ) ดู `docs/payments.md`
- กล่อง "ข้อมูลเข้าสู่ระบบ" แสดงอีเมลและปุ่ม "ดูรหัสผ่าน" (คัดลอกได้) และสร้างรหัสผ่านใหม่ได้ (กล่องเดียวกับหน้านักเรียน) ดู `docs/email.md`

ออเดอร์หนึ่งใบเป็นชนิดเดียว หน้าร้านรับรายการที่เลือกที่ปนหนังสือกับวิชาหรือหลักสูตรไม่ได้

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|--------|
| `app/Http/Controllers/OrderController.php` | CRUD ออเดอร์ + สร้างลูกค้า/payment |
| `app/Http/Requests/Orders/StoreOrderRequest.php` | Validate สร้างออเดอร์ |
| `app/Http/Requests/Orders/UpdateOrderRequest.php` | Validate แก้ไขสถานะ/ที่อยู่/พัสดุ |
| `app/Models/Order.php` | ออเดอร์ + ที่อยู่จัดส่ง + tracking |
| `app/Models/OrderItem.php` | รายการสินค้า (หนังสือ) |
| `app/Models/Payment.php` | วิธีชำระเงิน / สถานะชำระ |
| `app/Enums/OrderKind.php` | `book` ขายหนังสือ / `enrollment` สมัครเรียน |
| `app/Enums/OrderStatus.php` | สถานะออเดอร์ |
| `app/Enums/PaymentStatus.php` | สถานะชำระเงิน |
| `app/Enums/PaymentMethod.php` | วิธีชำระเงิน |
| `app/Enums/ShippingStatus.php` | สถานะจัดส่ง |
| `resources/views/pages/orders/*` | UI รายการ / สร้าง / ดู / แก้ไข |
| `resources/views/components/common/thai-address.blade.php` | ช่องที่อยู่ไทย |
| `resources/views/partials/thai-address-scripts.blade.php` | CDN jquery.Thailand.js |
| `database/migrations/2026_09_26_071126_add_shipping_fields_to_orders_table.php` | คอลัมน์จัดส่ง/พัสดุ |

## สิ่งที่ทำได้

- สร้างออเดอร์หนังสือ (หลายรายการ) — เลือกจาก modal ที่แสดงหน้าปก / ชื่อ / ราคา
- เลือกลูกค้าที่มีอยู่ **หรือ** กรอกข้อมูลลูกค้าใหม่ (สร้าง/ผูกบัญชี `student` ตามอีเมล)
- กรอกที่อยู่จัดส่ง (autocomplete ตำบล/อำเภอ/จังหวัด/รหัสไปรษณีย์)
- ระบุวิธีชำระเงิน: เก็บเงินปลายทาง / โอนเงิน / บัตรเครดิต
- ติดตามสถานะออเดอร์, ชำระเงิน, จัดส่ง และหมายเลขพัสดุ

## Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/orders` | `orders.index` |
| GET | `/enrollments` | `enrollments.index` |
| GET | `/enrollments/students` | `enrollments.students` |
| GET | `/enrollments/{order}` | `enrollments.show` |
| GET | `/enrollments/{order}/edit` | `enrollments.edit` |
| GET | `/orders/create` | `orders.create` |
| POST | `/orders` | `orders.store` |
| GET | `/orders/{order}` | `orders.show` |
| GET | `/orders/{order}/edit` | `orders.edit` |
| PUT | `/orders/{order}` | `orders.update` |
| DELETE | `/orders/{order}` | `orders.destroy` |
| POST | `/orders/{order}/payment-link` | `orders.payment-link` |
| POST | `/orders/{order}/payments/{payment}/refresh` | `orders.payments.refresh` |
| POST | `/orders/{order}/payments/{payment}/simulate` | `orders.payments.simulate` (โหมดทดสอบ) |

## ชนิดออเดอร์ (`orders.kind`)

| ค่า | ความหมาย |
|-----|----------|
| `book` | ขายหนังสือ มีที่อยู่จัดส่งและพัสดุ |
| `enrollment` | สมัครเรียน จากวิชา หลักสูตร หรือข้อสอบ ไม่จัดส่ง |

ออเดอร์เก่าที่มีทั้งหนังสือและรายการเรียนยังเป็น `book` ออเดอร์ที่มีแต่รายการเรียนถูกปรับเป็น `enrollment` ตอนเพิ่มคอลัมน์

การสร้างออเดอร์หนังสือในแอดมินเป็น `book` การสร้างออเดอร์จากหน้านักเรียนเป็น `enrollment`

ออเดอร์หนังสือแถมจากคูปองเป็น `book` ยอด 0 บาท มี `gift_for_order_id` ชี้ออเดอร์ต้นทาง แสดงป้าย "ของแถมจากคูปอง" ในรายการ ดู `docs/coupons.md`

ออเดอร์จากเว็บนักเรียนสั่งได้ทั้งแบบเข้าสู่ระบบ และแบบไม่สมัครสมาชิก (ส่งแค่อีเมล ระบบสร้างบัญชีนักเรียนและส่งรหัสผ่านทางอีเมล) แบบหลังมี `orders.guest_token` และมีบันทึก `storefront.guest_order` ใน Activity Log ดู `docs/student-api.md` หัวข้อสั่งซื้อโดยไม่ต้องสมัครสมาชิก

## สถานะ

### ออเดอร์ (`orders.status`)

| ค่า | ความหมาย |
|-----|----------|
| `pending` | รอดำเนินการ |
| `processing` | กำลังจัดเตรียม |
| `shipped` | จัดส่งแล้ว |
| `completed` | สำเร็จ |
| `cancelled` | ยกเลิก |

### ชำระเงิน (`orders.payment_status` / `payments.status`)

| ค่า | ความหมาย |
|-----|----------|
| `pending` | รอชำระเงิน |
| `awaiting_verification` | รอตรวจสอบ |
| `paid` | ชำระแล้ว |
| `failed` | ชำระไม่สำเร็จ |
| `refunded` | คืนเงินแล้ว |

### วิธีชำระเงิน (`payments.payment_method`)

| ค่า | ความหมาย |
|-----|----------|
| `cod` | เก็บเงินปลายทาง |
| `bank_transfer` | โอนเงิน |
| `credit_card` | บัตรเครดิต (ออนไลน์ผ่าน Omise) |
| `thai_qr` | QR พร้อมเพย์ (ออนไลน์ผ่าน KGP) |
| `truemoney` | TrueMoney Wallet (ออนไลน์ผ่าน Omise) |
| `shopeepay` | ShopeePay (ออนไลน์ผ่าน Omise) |
| `rabbit_linepay` | Rabbit LINE Pay (ออนไลน์ผ่าน Omise) |
| `wechat_pay` | WeChat Pay (ออนไลน์ผ่าน Omise) |

หน้ายืนยันชำระเงินหลังสร้างออเดอร์นักเรียน (`/students/{student}/orders/{order}/payment`) ให้แอดมินเลือกวิธีชำระแบบจัดกลุ่ม: โอน/ออฟไลน์, ชำระออนไลน์ (QR/บัตร), และ e-Wallet

- **โอนเงิน / COD:** กดยืนยันแล้วระบบบันทึกว่าชำระแล้วและส่งอีเมล (ไม่เรียก gateway)
- **QR / บัตร / e-Wallet:** สร้างลิงก์ชำระเงินแล้ว redirect ไป `/pay/{token}?method=...` ให้ชำระจริง (`StudentController::pay` → `OnlinePayment::linkFor`)

และมีปุ่ม **สร้างลิงก์ชำระเงินออนไลน์** (`POST /orders/{order}/payment-link` partial `payment-link`) ส่งให้นักเรียนจ่ายเองผ่าน `/pay/{token}` ปุ่มเดียวกันอยู่ในหน้าสมัครเรียน (`/enrollments/{order}` ทั้งแถบปุ่มบนและกล่องการชำระเงิน) และหน้าออเดอร์หนังสือ การชำระออนไลน์จริงและปุ่มตรวจสถานะ ดู `docs/payments.md`

รายการวิชา หลักสูตร และข้อสอบในออเดอร์เก็บแพ็กเกจที่ซื้อ (`order_items.price_plan_id`, `order_items.access_days`) และชื่อรายการต่อท้ายด้วยชื่อแพ็กเกจ ดู `docs/pricing.md`

### จัดส่ง (`orders.shipping_status`)

| ค่า | ความหมาย |
|-----|----------|
| `pending` | รอจัดส่ง |
| `ready` | พร้อมส่ง |
| `shipped` | กำลังจัดส่ง |
| `delivered` | ส่งถึงแล้ว |
| `failed` | จัดส่งไม่สำเร็จ |
| `not_required` | ไม่ต้องจัดส่ง |

## หน้าแสดงออเดอร์ — Action ด่วน

ที่ `orders.show` มีปุ่มเปิด modal:

| Action | Route | รายละเอียด |
|--------|-------|------------|
| อัปเดตสถานะจัดส่ง | `PUT /orders/{order}/shipping-status` | เลือกสถานะจัดส่งอย่างเดียว |
| ติดตามพัสดุ | `PUT /orders/{order}/tracking` | เลือกบริษัทขนส่ง (dropdown) + หมายเลขพัสดุ |
| แก้ไขข้อมูลลูกค้าและที่อยู่จัดส่ง | `PUT /orders/{order}/customer` (`orders.customer`) | ดูรายละเอียดด้านล่าง |

## ใบปะหน้าพัสดุ (A6)

`GET /orders/{order}/shipping-label` (`orders.shipping-label`, `ShippingLabelController`, `App\Support\ShippingLabels`, `resources/views/pdf/shipping-label.blade.php`) สร้าง PDF ขนาด A6 (105 × 148 มม.) เปิดในแท็บใหม่ให้พิมพ์

- **ปุ่ม:** "ปริ้นใบปะหน้า" ที่หน้ารายละเอียดออเดอร์หนังสือ และ "ใบปะหน้า" ในแต่ละแถวของรายการออเดอร์หนังสือ
- **ใช้ได้กับ:** ออเดอร์หนังสือที่ต้องจัดส่ง (`requires_shipping`) เท่านั้น ออเดอร์อื่นตอบ `404`

ข้อมูลบนใบ

- **ผู้ส่ง:** ชื่อ ที่อยู่ เบอร์โทร และโลโก้
- **เลขออเดอร์และวันที่สั่ง**
- **ผู้รับ:** ชื่อ เบอร์โทร และที่อยู่ ตัวอักษรใหญ่
  - คำนำหน้าที่อยู่: กรุงเทพฯ ใช้ แขวง / เขต จังหวัดอื่นใช้ ต. / อ. / จ.
  - รหัสไปรษณีย์แยกช่องทีละหลัก
- **แถบเก็บเงินปลายทาง:** แสดงยอดเมื่อวิธีชำระเป็น `cod` และยังไม่ชำระ
- **บาร์โค้ด Code 128 ของเลขออเดอร์** และบริษัทขนส่งกับเลขพัสดุ ถ้ามี ถ้าไม่มี แสดงสถานะชำระเงินแทน
- **รายการสินค้าและจำนวนชิ้น:** แสดงไม่เกิน `SHIPPING_LABEL_MAX_ITEMS` รายการ (ค่าเริ่มต้น 4) ที่เหลือแสดงเป็น "และอีก N รายการ" เพื่อให้จบใน 1 หน้า
- **หมายเหตุและเวลาพิมพ์**

ผู้ส่งตั้งค่าใน `config/shipping_label.php` ด้วย `SHIPPING_SENDER_NAME`, `SHIPPING_SENDER_PHONE` และ `SHIPPING_SENDER_ADDRESS` ถ้าเว้นว่างจะใช้ค่าเดียวกับใบเสร็จ (`RECEIPT_COMPANY_NAME`, `RECEIPT_PHONE`, `RECEIPT_COMPANY_ADDRESS`) โลโก้ใช้ `RECEIPT_LOGO` ฟอนต์และ mPDF ตั้งค่าร่วมกับใบเสร็จที่ `App\Support\ThaiPdf`

ตอนสั่งพิมพ์ เลือกขนาดกระดาษ A6 และสเกล 100% (ไม่ย่อให้พอดีหน้า)

แก้ไขข้อมูลลูกค้าและที่อยู่จัดส่ง (ปุ่มที่การ์ด "ข้อมูลลูกค้าและที่อยู่จัดส่ง")

- **บัญชีลูกค้า:** แก้ชื่อ อีเมล และเบอร์โทรได้ ซึ่งเปลี่ยนข้อมูลใน `users` ของลูกค้าด้วย
  - อีเมลต้องไม่ซ้ำกับบัญชีอื่น
  - เว้นอีเมลว่างจะคงอีเมลเดิมไว้
- **ที่อยู่จัดส่ง:** แก้ชื่อผู้รับ เบอร์โทร และที่อยู่ พร้อมช่วยกรอกตำบล อำเภอ จังหวัด และรหัสไปรษณีย์ แก้เฉพาะออเดอร์นี้
- **ปุ่ม "ใช้ชื่อและเบอร์ลูกค้าเป็นผู้รับ":** คัดลอกชื่อและเบอร์ของลูกค้าไปใส่ช่องผู้รับ
- **บัญชีผู้ใช้งานหลังบ้าน:** แก้จากหน้านี้ไม่ได้ แก้ได้เฉพาะที่อยู่จัดส่ง
- **ข้อจำกัด:** ใช้กับออเดอร์หนังสือเท่านั้น ออเดอร์สมัครเรียนตอบ `404`
- **Activity Log:** บันทึกตามการแก้ไขของ `orders` และ `users`

บริษัทขนส่งกำหนดใน `App\Enums\ShippingCarrier` (Kerry, Flash, J&T, ไปรษณีย์ไทย ฯลฯ)  
ถ้าเลือก **อื่นๆ** จะมีช่องกรอกชื่อเอง

เมื่อบันทึกพัสดุขณะสถานะเป็น `pending`/`ready` ระบบจะเปลี่ยนเป็น `shipped` ให้อัตโนมัติ

## สร้างออเดอร์ — ลูกค้า 2 แบบ

| โหมด | พฤติกรรม |
|------|----------|
| `existing` | เลือก `user_id` จากรายชื่อในระบบ |
| `manual` | กรอกชื่อ / เบอร์ / อีเมล → หา user ตามอีเมล หรือสร้างใหม่ + role `student` |

ชื่อผู้รับและเบอร์โทรใช้ร่วมกับที่อยู่จัดส่งในส่วนเดียวกัน

## ที่อยู่จัดส่ง + Autocomplete

ใช้ [jquery.Thailand.js](https://github.com/katanyoo/react-th-address) (fork จาก earthchie) ผ่าน CDN

### ช่องที่ใช้

| ฟิลด์ | ความหมาย |
|-------|----------|
| `shipping_address_line1` | ที่อยู่ (บ้านเลขที่ / ซอย / ถนน) |
| `shipping_subdistrict` | ตำบล/แขวง |
| `shipping_district` | อำเภอ/เขต |
| `shipping_province` | จังหวัด |
| `shipping_postal_code` | รหัสไปรษณีย์ |

ไม่มีช่อง `shipping_address_line2` ในฟอร์ม (ระบบบันทึกเป็น `null`)

### วิธีใช้ในหน้าอื่น

1. ใส่คอมโพเนนต์

```blade
<x-common.thai-address
    :address-line1="old('shipping_address_line1', '')"
    :subdistrict="old('shipping_subdistrict', '')"
    :district="old('shipping_district', '')"
    :province="old('shipping_province', '')"
    :postal-code="old('shipping_postal_code', '')"
/>
```

2. โหลดสคริปต์ (ต้องมี `@stack('scripts')` ใน layout)

```blade
@push('scripts')
    @include('partials.thai-address-scripts')
@endpush
```

พิมพ์ตำบล / อำเภอ / จังหวัด / รหัสไปรษณีย์ แล้วเลือกรายการ — ระบบจะเติมช่องที่เกี่ยวข้องให้อัตโนมัติ

## ติดตามพัสดุ

แก้ที่หน้า `orders.edit` / `orders.show`

| ฟิลด์ | ความหมาย |
|-------|----------|
| `shipping_carrier` | บริษัทขนส่ง (Kerry, Flash, Thailand Post ฯลฯ) |
| `tracking_number` | หมายเลขพัสดุ |
| `shipped_at` | เวลาส่ง |
| `delivered_at` | เวลาถึง |

เมื่อตั้ง `shipping_status = shipped` ระบบจะ stamp `shipped_at`  
เมื่อ `delivered` จะ stamp `delivered_at` และอาจอัปเดตออเดอร์เป็น `completed`

## หมายเหตุธุรกิจ

- รายการสินค้าตอนนี้โฟกัส **หนังสือ** (`products.type = book`)
- ออเดอร์ที่ `payment_status = paid` ลบไม่ได้จากแอดมิน
- เลขออเดอร์รูปแบบ `ORD-yymmdd-XXXXX`
- ชำระผ่าน gateway (QR พร้อมเพย์ / บัตร) ดู `docs/payments.md` ตอนนี้ยังเป็นโหมดทดสอบจนกว่าจะได้คีย์
