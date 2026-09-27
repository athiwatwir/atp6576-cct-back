# ออเดอร์ (Orders)

ระบบจัดการคำสั่งซื้อหนังสือในแอดมิน  
เมนู: **การขาย → ออเดอร์** (`/orders`)

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|--------|
| `app/Http/Controllers/OrderController.php` | CRUD ออเดอร์ + สร้างลูกค้า/payment |
| `app/Http/Requests/Orders/StoreOrderRequest.php` | Validate สร้างออเดอร์ |
| `app/Http/Requests/Orders/UpdateOrderRequest.php` | Validate แก้ไขสถานะ/ที่อยู่/พัสดุ |
| `app/Models/Order.php` | ออเดอร์ + ที่อยู่จัดส่ง + tracking |
| `app/Models/OrderItem.php` | รายการสินค้า (หนังสือ) |
| `app/Models/Payment.php` | วิธีชำระเงิน / สถานะชำระ |
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
| GET | `/orders/create` | `orders.create` |
| POST | `/orders` | `orders.store` |
| GET | `/orders/{order}` | `orders.show` |
| GET | `/orders/{order}/edit` | `orders.edit` |
| PUT | `/orders/{order}` | `orders.update` |
| DELETE | `/orders/{order}` | `orders.destroy` |

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
| `credit_card` | บัตรเครดิต |

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
- Cart / ชำระเงินจริงผ่าน gateway ยังเป็นขั้นตอนถัดไป (ดู `docs/project.md`)
