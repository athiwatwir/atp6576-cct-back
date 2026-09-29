# Student API สำหรับ Nuxt

API นี้ให้เว็บนักเรียน (Nuxt) ใช้สมัครสมาชิก เข้าสู่ระบบ ดูคอร์ส หลักสูตร หนังสือ แบนเนอร์ และบทความที่เปิดแสดง สร้างออเดอร์ ส่งสลิปโอนเงิน และจัดการโปรไฟล์

ฐาน URL คือ `{APP_URL}/api/v1` เช่น `http://127.0.0.1:8000/api/v1`

การยืนยันตัวตนใช้ Bearer token จาก Laravel Sanctum ส่ง header ทุกครั้งที่เรียก endpoint ที่ต้องล็อกอิน

```http
Accept: application/json
Authorization: Bearer {token}
```

Endpoint ที่เปิดสาธารณะไม่ต้องส่ง token ถ้าส่ง token ที่ยังใช้ได้ รายการสินค้าจะมีฟิลด์ `purchased` บอกว่านักเรียนคนนี้มีสิทธิ์แล้วหรือไม่

## ตั้งค่า Nuxt

`nuxt.config.ts`

```ts
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: 'http://127.0.0.1:8000/api/v1',
    },
  },
})
```

`composables/useApi.ts`

```ts
export function useApi() {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('student_token', { sameSite: 'lax' })

  const api = $fetch.create({
    baseURL: config.public.apiBase,
    onRequest({ options }) {
      const headers = new Headers(options.headers)
      headers.set('Accept', 'application/json')
      if (token.value) headers.set('Authorization', `Bearer ${token.value}`)
      options.headers = headers
    },
  })

  return { api, token }
}
```

`FRONTEND_URL` ใน `.env` ของ Laravel คือ origin ของ Nuxt ที่อนุญาตให้เรียก API ได้ ใส่ได้หลายค่าคั่นด้วยจุลภาค เช่น `http://localhost:3000,https://clickclasstutor.com` ถ้าต้องการเปิดทุก origin ระหว่างพัฒนา ให้ตั้งเป็น `*`

## รูปแบบข้อผิดพลาด

| สถานะ | ความหมาย |
|------|----------|
| 401 | ยังไม่เข้าสู่ระบบ หรือ token หมดอายุจากการออกจากระบบ |
| 403 | บัญชีไม่ใช่นักเรียน หรือถูกปิดใช้งาน |
| 404 | ไม่พบรายการ |
| 422 | ข้อมูลไม่ผ่านเงื่อนไข ดู `errors` |
| 429 | เรียกเข้าสู่ระบบหรือสมัครสมาชิกถี่เกินไป |

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["อีเมลนี้ถูกใช้แล้ว"]
  }
}
```

จำนวนเงินเป็นตัวเลขหน่วยบาท ไม่มีสัญลักษณ์สกุลเงิน

## สมัครสมาชิกและเข้าสู่ระบบ

### POST `/auth/register`

ไม่ต้องใช้ token สร้างบัญชีบทบาทนักเรียนและเปิดใช้งานทันที

```json
{
  "name": "ใหม่ ใจดี",
  "email": "new@example.com",
  "phone": "0812345678",
  "password": "password123",
  "password_confirmation": "password123"
}
```

`phone` ไม่บังคับ รหัสผ่านอย่างน้อย 8 ตัว

ตอบกลับ `201`

```json
{
  "token": "1|plain-text-token",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "ใหม่ ใจดี",
    "email": "new@example.com",
    "phone": "0812345678",
    "avatar_url": null,
    "status": "active",
    "status_label": "เปิดใช้งาน",
    "learning": { "courses": [], "curriculums": [] }
  }
}
```

เก็บ `token` ไว้ใน cookie แล้วใช้กับคำขอถัดไป token อยู่ได้จนกว่าจะออกจากระบบ และออกจากระบบได้ทีละอุปกรณ์

### POST `/auth/login`

```json
{ "email": "new@example.com", "password": "password123" }
```

ตอบกลับรูปแบบเดียวกับสมัครสมาชิก สถานะ `200` บัญชีแอดมินหรือสตาฟใช้เส้นทางนี้ไม่ได้

### POST `/auth/logout`

ต้องใช้ token ลบ token ปัจจุบัน

```json
{ "message": "ออกจากระบบแล้ว" }
```

## โปรไฟล์

### GET `/profile`

```json
{
  "data": {
    "id": 1,
    "name": "ใหม่ ใจดี",
    "email": "new@example.com",
    "phone": "0812345678",
    "avatar_url": null,
    "status": "active",
    "status_label": "เปิดใช้งาน",
    "learning": {
      "courses": [{ "id": 3, "name": "คอร์สฟิสิกส์", "slug": "physics" }],
      "curriculums": []
    }
  }
}
```

`learning` คือสิ่งที่ชำระเงินแล้วและกำลังเรียนอยู่ ใช้แสดงเมนูคอร์สของฉันได้

### POST `/profile`

อัปเดตชื่อ อีเมล เบอร์โทร และรูปโปรไฟล์ ใช้ `POST` เพราะอัปโหลดไฟล์ได้ ส่ง `multipart/form-data`

| ฟิลด์ | บังคับ | หมายเหตุ |
|------|--------|----------|
| name | ใช่ | สูงสุด 150 ตัวอักษร |
| email | ใช่ | ห้ามซ้ำ |
| phone | ไม่ | สูงสุด 30 ตัวอักษร |
| avatar | ไม่ | jpg, jpeg, png, webp ไม่เกิน 4 MB |

```ts
const form = new FormData()
form.set('name', 'ใหม่ ใจดี')
form.set('email', 'new@example.com')
form.set('phone', '0812345678')
if (file) form.set('avatar', file)
await api('/profile', { method: 'POST', body: form })
```

ถ้าไม่อัปโหลดรูป สามารถส่ง JSON ไปที่ `PUT /profile` ด้วยฟิลด์ `name`, `email`, `phone` ได้

### PUT `/profile/password`

```json
{
  "current_password": "password123",
  "password": "new-password",
  "password_confirmation": "new-password"
}
```

```json
{ "message": "เปลี่ยนรหัสผ่านแล้ว" }
```

## สินค้าที่เปิดขาย

รายการทั้งหมดแบ่งหน้า ค่าเริ่มต้น 12 รายการต่อหน้า สูงสุด 50

พารามิเตอร์ร่วมของคอร์สและหลักสูตร

| พารามิเตอร์ | ความหมาย |
|------------|----------|
| `search` | ค้นจากชื่อ |
| `featured` | `1` เฉพาะรายการแนะนำ |
| `page` | หน้าที่ต้องการ |
| `per_page` | จำนวนต่อหน้า |

หนังสือใช้ได้แค่ `search`, `page`, `per_page`

การตอบกลับของรายการ

```json
{
  "data": [],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 12, "total": 1 }
}
```

รายละเอียดห่อด้วย `{ "data": { ... } }`

คอร์สที่สถานะไม่ใช่ `published` หลักสูตรที่ยังไม่เผยแพร่ และหนังสือที่ไม่ได้เปิดขาย จะไม่ปรากฏใน API นี้

### GET `/courses` และ GET `/courses/{slug}`

```json
{
  "id": 3,
  "code": "C001",
  "slug": "physics",
  "name": "คอร์สฟิสิกส์",
  "short_description": "สรุปเนื้อหา",
  "description": "มีเฉพาะหน้ารายละเอียด",
  "thumbnail_url": "https://...",
  "price": 1500,
  "sale_price": 990,
  "effective_price": 990,
  "is_featured": true,
  "is_trial_available": false,
  "chapter_count": 8,
  "purchased": false,
  "category": { "id": 1, "name": "มัธยม", "slug": "secondary" },
  "subject": { "id": 2, "name": "ฟิสิกส์", "slug": "physics" },
  "instructor": { "id": 1, "name": "ครูเอ", "slug": "teacher-a" }
}
```

ใช้ `effective_price` เป็นราคาที่ต้องจ่าย ถ้ามี `sale_price` มากกว่า 0 ระบบใช้ราคานั้น ไม่เช่นนั้นใช้ `price`

### GET `/curriculums` และ GET `/curriculums/{slug}`

ฟิลด์ราคาและรูปเหมือนคอร์ส หน้ารายการมี `course_count` หน้ารายละเอียดมี `courses` เป็นชื่อคอร์สที่เผยแพร่แล้วในหลักสูตรนั้น

```json
"courses": [
  { "id": 3, "name": "คอร์สฟิสิกส์", "slug": "physics", "thumbnail_url": null }
]
```

### GET `/books` และ GET `/books/{slug}`

```json
{
  "id": 5,
  "code": "B001",
  "slug": "math-book",
  "name": "หนังสือคณิต",
  "description": "มีเฉพาะหน้ารายละเอียด",
  "thumbnail_url": null,
  "price": 350,
  "sale_price": null,
  "effective_price": 350,
  "stock": 4,
  "in_stock": true,
  "purchased": false
}
```

`stock` เป็น `null` แปลว่าไม่จำกัดจำนวน `purchased` ของหนังสือเป็นจริงเมื่อมีออเดอร์ที่ชำระแล้วซึ่งมีหนังสือเล่มนั้น

## แบนเนอร์และบทความ

รายการนี้เป็นสาธารณะ ไม่ต้องใช้ token ระบบส่งเฉพาะรายการที่สถานะเผยแพร่ และอยู่ในช่วงเวลาเริ่มถึงสิ้นสุด ร่าง ปิดใช้งาน และรายการที่ยังไม่ถึงเวลาหรือหมดเวลาแล้วจะไม่ปรากฏ

### GET `/banners`

พารามิเตอร์ `placement` ไม่บังคับ

| ค่า | ตำแหน่งบนเว็บ |
|----|----------------|
| `home` | หน้าแรก |
| `courses` | หน้าคอร์ส |
| `books` | หน้าหนังสือ |

ไม่ส่ง `placement` จะได้แบนเนอร์ทุกตำแหน่งที่กำลังแสดง สูงสุด 30 รายการ เรียงตามลำดับที่แอดมินจัดไว้

```json
{
  "data": [
    {
      "id": 1,
      "title": "เปิดเทอมใหม่",
      "excerpt": "ลดราคาคอร์สคณิต",
      "image_url": "https://...",
      "link_url": "https://clickclasstutor.com/courses/math",
      "placement": "home",
      "placement_label": "หน้าแรก"
    }
  ]
}
```

`link_url` เป็น `null` ได้ถ้าแบนเนอร์ไม่ต้องคลิกไปหน้าอื่น

### GET `/articles` และ GET `/articles/{slug}`

รายการใช้ `search`, `page`, `per_page` เหมือนสินค้า เรียงจากวันที่เผยแพร่ล่าสุด รายการไม่มีเนื้อหาเต็ม เพื่อให้โหลดหน้าแรกเร็ว

```json
{
  "id": 4,
  "slug": "exam-tips",
  "title": "เทคนิคทำข้อสอบ",
  "excerpt": "อ่านโจทย์ให้ครบก่อนลงมือ",
  "image_url": null,
  "published_at": "2026-09-29T09:00:00+07:00"
}
```

หน้ารายละเอียด `GET /articles/{slug}` เพิ่มฟิลด์ `content` เป็น HTML ที่ทำความสะอาดแล้ว แสดงด้วย `v-html` ได้ แท็กที่ใช้ได้คือหัวข้อ ย่อหน้า รายการ ลิงก์ รูป โค้ด และข้อความตัวหนาหรือเอียง

## สั่งซื้อและชำระเงิน

วิธีชำระของหน้าร้านคือโอนเงิน แล้วอัปโหลดสลิป แอดมินตรวจในหน้าการเงิน เมื่อสถานะเป็นชำระแล้ว ระบบเปิดสิทธิ์เรียนให้เอง เลขบัญชีที่ให้ลูกค้าโอนให้ตั้งในโปรเจกต์ Nuxt เพราะ API นี้ยังไม่เก็บข้อมูลบัญชีธนาคาร

ประเภทสินค้าในตะกร้าคือ `course`, `curriculum`, `book` คอร์สและหลักสูตรจำนวนเป็น 1 เสมอ หนังสือส่ง `quantity` ได้ 1–99

ถ้าตะกร้ามีหนังสือ ต้องส่งที่อยู่จัดส่ง คอร์สหรือหลักสูตรอย่างเดียวไม่ต้องส่งที่อยู่และไม่ต้องจัดส่ง

### POST `/checkout/quote`

คำนวณยอดก่อนสร้างออเดอร์ ต้องล็อกอิน ใช้ตรวจคูปองและแสดงยอดในหน้าชำระเงิน

```json
{
  "items": [
    { "type": "course", "id": 3 },
    { "type": "book", "id": 5, "quantity": 1 }
  ],
  "coupon_code": "SAVE10"
}
```

```json
{
  "data": {
    "items": [
      { "type": "course", "id": 3, "name": "คอร์สฟิสิกส์", "quantity": 1, "unit_price": 990, "total_price": 990 }
    ],
    "subtotal": 990,
    "discount": 99,
    "shipping": 0,
    "total": 891,
    "requires_shipping": false,
    "coupon": { "code": "SAVE10", "description": "ลด 10%" }
  }
}
```

รหัสคูปองที่ไม่ผ่านเงื่อนไขตอบ `422`

### POST `/orders`

สร้างออเดอร์ สถานะชำระเริ่มที่ `pending` ถ้ายอดสุทธิเป็น 0 ระบบตัดเป็นชำระแล้วและเปิดสิทธิ์เรียนทันที

```json
{
  "items": [
    { "type": "course", "id": 3 },
    { "type": "book", "id": 5, "quantity": 2 }
  ],
  "coupon_code": "SAVE10",
  "notes": "ส่งช่วงเย็น",
  "shipping": {
    "name": "ใหม่ ใจดี",
    "phone": "0812345678",
    "address_line1": "99/1",
    "address_line2": null,
    "subdistrict": "ศรีภูมิ",
    "district": "เมือง",
    "province": "เชียงใหม่",
    "postal_code": "50000"
  }
}
```

ที่อยู่บังคับเมื่อมีหนังสือ: `name`, `phone`, `address_line1`, `district`, `province`, `postal_code`

ตอบกลับ `201` ตัวอย่างออเดอร์

```json
{
  "data": {
    "id": 10,
    "order_no": "O260929001",
    "subtotal": 1690,
    "discount": 0,
    "shipping_amount": 0,
    "total": 1690,
    "status": "pending",
    "status_label": "รอดำเนินการ",
    "payment_status": "pending",
    "payment_status_label": "รอชำระเงิน",
    "requires_shipping": true,
    "shipping": {
      "name": "ใหม่ ใจดี",
      "phone": "0812345678",
      "address_line1": "99/1",
      "province": "เชียงใหม่",
      "postal_code": "50000",
      "status": "pending",
      "status_label": "รอจัดส่ง",
      "tracking_number": null
    },
    "payment": {
      "method": "bank_transfer",
      "method_label": "โอนเงิน",
      "status": "pending",
      "status_label": "รอชำระเงิน",
      "amount": 1690,
      "paid_at": null,
      "slip_url": null
    },
    "items": [
      { "type": "course", "id": 3, "name": "คอร์สฟิสิกส์", "quantity": 1, "unit_price": 990, "total_price": 990 }
    ],
    "created_at": "2026-09-29T02:00:00+07:00",
    "paid_at": null
  }
}
```

สถานะชำระเงินที่ใช้บนหน้าร้าน

| ค่า | ความหมาย |
|----|----------|
| `pending` | รอให้นักเรียนโอนและส่งสลิป |
| `awaiting_verification` | ส่งสลิปแล้ว รอแอดมินตรวจ |
| `paid` | ชำระแล้ว เปิดสิทธิ์เรียนแล้ว |
| `failed` | แอดมินตรวจแล้วไม่ผ่าน ส่งสลิปใหม่ได้ |
| `refunded` | คืนเงินแล้ว ส่งสลิปใหม่ไม่ได้ |

### GET `/orders` และ GET `/orders/{id}`

รายการออเดอร์ของนักเรียนที่ล็อกอินเท่านั้น ใช้ `id` จากตอนสร้างออเดอร์ ไม่ใช่ `order_no`

### POST `/orders/{id}/payment`

ส่งสลิปแบบ `multipart/form-data`

| ฟิลด์ | บังคับ | หมายเหตุ |
|------|--------|----------|
| slip | ใช่ | jpg, jpeg, png, webp, pdf ไม่เกิน 5 MB |
| note | ไม่ | ข้อความถึงแอดมิน |

```ts
const form = new FormData()
form.set('slip', file)
form.set('note', 'โอนจากกสิกร')
const order = await api(`/orders/${orderId}/payment`, { method: 'POST', body: form })
```

หลังส่งสำเร็จ `payment_status` เป็น `awaiting_verification` และ `payment.slip_url` เป็นที่อยู่ไฟล์สลิป ส่งซ้ำได้จนกว่าออเดอร์จะถูกตัดเป็นชำระแล้ว

## ลำดับที่แนะนำใน Nuxt

1. เปิดร้านด้วย `GET /courses`, `GET /curriculums`, `GET /books` โดยยังไม่ล็อกอิน
2. ให้สมัครหรือเข้าสู่ระบบ แล้วเก็บ token
3. หน้าตะกร้าเรียก `POST /checkout/quote` ทุกครั้งที่รายการหรือคูปองเปลี่ยน
4. ถ้า `requires_shipping` เป็นจริง ให้แสดงฟอร์มที่อยู่
5. `POST /orders` แล้วพาไปหน้าโอนเงิน
6. `POST /orders/{id}/payment` พร้อมสลิป
7. หน้าโปรไฟล์และประวัติใช้ `GET /profile` กับ `GET /orders`
