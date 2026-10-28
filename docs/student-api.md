# Student API สำหรับ Nuxt

API นี้ให้เว็บนักเรียน (Nuxt) ใช้สมัครสมาชิก เข้าสู่ระบบ ดูวิชา หลักสูตร หนังสือ แบนเนอร์ บทความ และรีวิวที่เปิดแสดง เลือกแพ็กเกจราคาตามระยะเวลาเรียน สร้างออเดอร์ ชำระเงินด้วย QR พร้อมเพย์หรือบัตร ส่งสลิปโอนเงิน และจัดการโปรไฟล์

ฐาน URL คือ `{APP_URL}/api/v1` เช่น `http://127.0.0.1:8000/api/v1`

คำเรียกในระบบ: "วิชา" คือ course (`item_type` `course`, ฟิลด์ `course_*`) "ชนิดวิชา" คือ subject และ "ระดับชั้น" คือ category ชื่อฟิลด์และค่าใน JSON ยังเป็นภาษาอังกฤษเหมือนเดิม ข้อความ error ภาษาไทยใช้คำว่า "วิชา" เช่น `ไม่พบวิชานี้`

`category` ในวิชาและหลักสูตรมาจากหน้า "ระดับชั้น" ในหลังบ้าน แอดมินแก้ `name` และ `slug` ได้ ควรใช้ `slug` เป็นค่าคงที่ในเว็บ (เช่น ตัวกรองหรือ URL) แทน `name` ระดับชั้นที่ถูกปิดใช้งานยังส่งมากับวิชาเดิมที่ผูกไว้ API ยังไม่มี endpoint รายการระดับชั้นแยก

ข้อสอบที่ขายแยกอยู่ในรายการ `GET /catalog` ด้วย `item_type` เป็น `exam` API นี้ยังไม่ส่งคำถามหรือรูปประกอบคำถาม ทั้งข้อสอบขายแยก ข้อสอบในวิชา และแบบฝึกหัด จำนวนรายการที่กำลังใช้งานบนเมนูแอดมินไม่รวมใน API นี้

ทุกคำขอต้องส่งคีย์ของเว็บนักเรียนใน header `X-Api-Key` ค่าเดียวกับ `STOREFRONT_API_KEY` ใน `.env` ของ Laravel ถ้าไม่ส่งหรือค่าไม่ตรง ระบบตอบ 401

การยืนยันตัวตนของนักเรียนใช้ Bearer token จาก Laravel Sanctum ส่ง header เพิ่มเมื่อเรียก endpoint ที่ต้องล็อกอิน

```http
Accept: application/json
X-Api-Key: {STOREFRONT_API_KEY}
Authorization: Bearer {token}
```

Endpoint ที่เปิดสาธารณะไม่ต้องส่ง token ถ้าส่ง token ที่ยังใช้ได้ รายการสินค้าจะมีฟิลด์ `purchased` บอกว่านักเรียนคนนี้มีสิทธิ์แล้วหรือไม่ คำขอสาธารณะยังต้องส่ง `X-Api-Key` และถูกจำกัด 60 ครั้งต่อนาทีต่อ IP สมัครสมาชิก เข้าสู่ระบบ ส่งลิงก์ยืนยันซ้ำ และสั่งซื้อแบบไม่สมัครสมาชิกถูกจำกัด 10 ครั้งต่อนาที แยกนับแต่ละ endpoint

## ตั้งค่า Nuxt

`nuxt.config.ts`

```ts
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: 'http://127.0.0.1:8000/api/v1',
      apiKey: '',
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
      headers.set('X-Api-Key', config.public.apiKey)
      if (token.value) headers.set('Authorization', `Bearer ${token.value}`)
      options.headers = headers
    },
  })

  return { api, token }
}
```

ตั้งค่าคีย์ฝั่ง Nuxt ด้วย `NUXT_PUBLIC_API_KEY` ให้ตรงกับ `STOREFRONT_API_KEY` คีย์นี้กันคำขอที่ไม่ได้มาจากเว็บนักเรียน เบราว์เซอร์ต้องส่งเอง จึงอ่านได้จากหน้าเว็บ

`FRONTEND_URL` ใน `.env` ของ Laravel คือ origin ของ Nuxt ที่อนุญาตให้เรียก API ได้ ใส่ได้หลายค่าคั่นด้วยจุลภาค เช่น `http://localhost:3000,https://clickclasstutor.com` ถ้าต้องการเปิดทุก origin ระหว่างพัฒนา ให้ตั้งเป็น `*`

## รูปแบบข้อผิดพลาด

| สถานะ | ความหมาย |
|------|----------|
| 401 | ไม่ได้ส่ง `X-Api-Key` หรือคีย์ไม่ตรง หรือยังไม่เข้าสู่ระบบ หรือ token หมดอายุจากการออกจากระบบ |
| 403 | บัญชีไม่ใช่นักเรียน หรือถูกปิดใช้งาน |
| 404 | ไม่พบรายการ |
| 422 | ข้อมูลไม่ผ่านเงื่อนไข ดู `errors` |
| 429 | เรียก API ถี่เกิน 60 ครั้งต่อนาที หรือสมัครสมาชิก เข้าสู่ระบบ ส่งลิงก์ยืนยันซ้ำ สั่งซื้อแบบไม่สมัครสมาชิก หรือส่งสลิปแบบไม่สมัครสมาชิกถี่เกิน 10 ครั้งต่อนาที (แต่ละ endpoint นับแยกกัน ไม่รวมกับการเปิดหน้าอื่น) |

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

ไม่ต้องใช้ token ต้องส่ง `X-Api-Key` สร้างบัญชีบทบาทนักเรียนแล้วเข้าสู่ระบบได้ทันที แม้ยังไม่กดลิงก์ยืนยันอีเมล

ระบบส่งอีเมลหัวข้อ `ยืนยันอีเมล` ปุ่มในอีเมลเปิดหน้าเว็บนักเรียน ไม่ใช่ API

```text
{STUDENT_APP_URL}/verify-email?id=12&hash={sha1 ของอีเมล}&expires={unix timestamp}&signature={HMAC-SHA256}
```

ลิงก์หมดอายุใน 60 นาที `signature` ลงด้วย `APP_KEY` ของ Laravel แก้ค่าใดในลิงก์ไม่ได้ หน้า `/verify-email` ของ Nuxt อ่าน query ทั้งสี่ค่าแล้วเรียก `POST /auth/email/verify` (ดูด้านล่าง) จากนั้นแสดงผลในหน้าเดียวกัน

`STUDENT_APP_URL` คือที่อยู่เว็บ Nuxt ถ้าไม่ตั้ง ระบบใช้ค่าแรกของ `FRONTEND_URL` ที่ไม่ใช่ `*`

อีเมลยืนยันส่งทันที ไม่รอคิว

```json
{
  "name": "ใหม่ ใจดี",
  "email": "new@example.com",
  "phone": "0812345678",
  "school_name": "โรงเรียนตัวอย่าง",
  "address_line1": "99/1 หมู่ 2",
  "subdistrict": "ศรีภูมิ",
  "district": "เมืองเชียงใหม่",
  "province": "เชียงใหม่",
  "postal_code": "50200",
  "password": "password123",
  "password_confirmation": "password123"
}
```

`phone`, `school_name` และที่อยู่ไม่บังคับ รหัสไปรษณีย์ถ้าส่งต้องเป็นตัวเลข 5 หลัก รหัสผ่านอย่างน้อย 8 ตัว

ตอบกลับ `201` ยังไม่มี token

```json
{
  "message": "สมัครสมาชิกแล้ว เข้าสู่ระบบได้ทันที",
  "email": "new@example.com"
}
```

### POST `/auth/email/resend`

ส่งลิงก์ยืนยันอีกครั้ง ไม่ต้องใช้ token ต้องส่ง `X-Api-Key` ถูกจำกัด 10 ครั้งต่อนาที

```json
{ "email": "new@example.com" }
```

ตอบกลับ `200` ข้อความเดียวกันไม่ว่าจะมีบัญชีนี้หรือยืนยันไปแล้ว

```json
{ "message": "ถ้าอีเมลนี้ยังไม่ยืนยัน ระบบได้ส่งลิงก์ยืนยันให้แล้ว" }
```

ระบบส่งอีเมลใหม่เฉพาะบัญชีนักเรียนที่ยังไม่ยืนยันและยังเปิดใช้งาน

### POST `/auth/email/verify`

เรียกจากหน้า `/verify-email` ของ Nuxt ไม่ต้องใช้ token ต้องส่ง `X-Api-Key` ถูกจำกัด 10 ครั้งต่อนาที ส่งค่าจาก query ของลิงก์ในอีเมล

```json
{ "id": 12, "hash": "3192ba24...", "expires": 1793000358, "signature": "0d9f1d71..." }
```

ตอบกลับ `200`

```json
{ "message": "ยืนยันอีเมลเรียบร้อยแล้ว", "status": "verified", "email": "new@example.com" }
```

`status` เป็น `already_verified` (ข้อความ `อีเมลนี้ยืนยันไว้แล้ว`) เมื่อกดลิงก์ซ้ำ ลิงก์หมดอายุ ลายเซ็นไม่ตรง หรือบัญชีไม่ใช่นักเรียน ตอบ `422`

```json
{ "message": "ลิงก์ยืนยันหมดอายุหรือไม่ถูกต้อง กรุณาขอลิงก์ใหม่", "status": "invalid" }
```

ถ้าผู้ใช้ล็อกอินอยู่ ให้เรียก `GET /profile` ใหม่หลังยืนยัน เพื่อให้ `email_verified` เป็น `true`

### GET `/auth/email/verify/{id}/{hash}` (ลิงก์รูปแบบเดิม)

ใช้กับอีเมลที่ส่งออกไปก่อนเปลี่ยนเป็นลิงก์หน้าเว็บเท่านั้น ระบบไม่สร้างลิงก์แบบนี้แล้ว เปิดจากลิงก์ในอีเมล ไม่ต้องส่ง `X-Api-Key` ต้องมี query `expires` และ `signature` ยืนยันแล้วเปลี่ยนเส้นทางไปที่ `{STUDENT_APP_URL}/verify-email?status=success` หรือ `?status=invalid`

### POST `/auth/login`

```json
{ "email": "new@example.com", "password": "password123" }
```

ตอบกลับ `200` แม้ `email_verified` จะเป็น `false`

```json
{
  "token": "1|plain-text-token",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "ใหม่ ใจดี",
    "email": "new@example.com",
    "phone": "0812345678",
    "school_name": "โรงเรียนตัวอย่าง",
    "address": {
      "address_line1": "99/1 หมู่ 2",
      "subdistrict": "ศรีภูมิ",
      "district": "เมืองเชียงใหม่",
      "province": "เชียงใหม่",
      "postal_code": "50200"
    },
    "avatar_url": null,
    "status": "active",
    "status_label": "เปิดใช้งาน",
    "email_verified": true,
    "learning": { "courses": [], "curriculums": [] }
  }
}
```

เก็บ `token` ไว้ใน cookie แล้วใช้กับคำขอถัดไป token อยู่ได้จนกว่าจะออกจากระบบ และออกจากระบบได้ทีละอุปกรณ์

บัญชีแอดมินหรือสตาฟใช้เส้นทางนี้ไม่ได้ นักเรียนที่แอดมินสร้างให้ถูกนับว่ายืนยันอีเมลแล้ว นักเรียนที่สมัครเองยังเข้าสู่ระบบได้ก่อนกดลิงก์ และ `email_verified` เป็น `false` จนกว่าจะยืนยัน

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
    "school_name": "โรงเรียนตัวอย่าง",
    "address": {
      "address_line1": "99/1 หมู่ 2",
      "subdistrict": "ศรีภูมิ",
      "district": "เมืองเชียงใหม่",
      "province": "เชียงใหม่",
      "postal_code": "50200"
    },
    "avatar_url": null,
    "status": "active",
    "status_label": "เปิดใช้งาน",
    "email_verified": true,
    "learning": {
      "courses": [{ "id": 3, "name": "วิชาฟิสิกส์", "slug": "physics", "thumbnail_url": null, "expires_at": "2026-11-16T10:00:00+07:00" }],
      "curriculums": [],
      "exams": []
    }
  }
}
```

`learning` คือสิ่งที่ชำระเงินแล้วและยังไม่หมดอายุ ใช้แสดงเมนูวิชาของฉันได้ `expires_at` คือวันหมดสิทธิ์ตามแพ็กเกจที่ซื้อ `null` = เรียนได้ไม่จำกัดเวลา

### POST `/profile`

อัปเดตชื่อ อีเมล เบอร์โทร และรูปโปรไฟล์ ใช้ `POST` เพราะอัปโหลดไฟล์ได้ ส่ง `multipart/form-data`

| ฟิลด์ | บังคับ | หมายเหตุ |
|------|--------|----------|
| name | ใช่ | สูงสุด 150 ตัวอักษร |
| email | ใช่ | ห้ามซ้ำ |
| phone | ไม่ | สูงสุด 30 ตัวอักษร |
| school_name | ไม่ | ชื่อโรงเรียน สูงสุด 255 ตัวอักษร |
| address_line1 | ไม่ | บ้านเลขที่ หมู่ ซอย ถนน |
| subdistrict | ไม่ | ตำบล/แขวง |
| district | ไม่ | อำเภอ/เขต |
| province | ไม่ | จังหวัด |
| postal_code | ไม่ | ตัวเลข 5 หลัก |
| avatar | ไม่ | jpg, jpeg, png, webp ไม่เกิน 4 MB |

```ts
const form = new FormData()
form.set('name', 'ใหม่ ใจดี')
form.set('email', 'new@example.com')
form.set('phone', '0812345678')
form.set('school_name', 'โรงเรียนตัวอย่าง')
form.set('address_line1', '99/1 หมู่ 2')
form.set('subdistrict', 'ศรีภูมิ')
form.set('district', 'เมืองเชียงใหม่')
form.set('province', 'เชียงใหม่')
form.set('postal_code', '50200')
if (file) form.set('avatar', file)
await api('/profile', { method: 'POST', body: form })
```

ถ้าไม่อัปโหลดรูป สามารถส่ง JSON ไปที่ `PUT /profile` ด้วยฟิลด์ `name`, `email`, `phone`, `school_name` และที่อยู่ได้

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

วิชา หลักสูตร และข้อสอบขายแยกอยู่ในเส้นเดียวกัน `GET /catalog` แต่ละรายการใช้ `item_type` เป็น `course`, `curriculum` หรือ `exam` หนังสือยังแยกที่ `/books`

รายการทั้งหมดแบ่งหน้า ค่าเริ่มต้น 12 รายการต่อหน้า สูงสุด 50

พารามิเตอร์ของ `GET /catalog`

| พารามิเตอร์ | ความหมาย |
|------------|----------|
| `search` | ค้นจากชื่อ |
| `type` | กรองได้ `course`, `curriculum`, `exam` หรือหลายค่าคั่นด้วยจุลภาค ไม่ส่งคือทั้งสามประเภท |
| `featured` | `1` เฉพาะวิชาและหลักสูตรที่แนะนำ ข้อสอบไม่มีสถานะแนะนำ จึงไม่ถูกส่งเมื่อเปิดตัวกรองนี้ |
| `page` | หน้าที่ต้องการ |
| `per_page` | จำนวนต่อหน้า |

เรียงรายการแนะนำก่อน แล้วตามลำดับที่แอดมินจัดไว้ และชื่อ

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

วิชาที่สถานะไม่ใช่ `published` หลักสูตรที่ยังไม่เผยแพร่ ข้อสอบที่ไม่ได้เปิดขายแยก และหนังสือที่ไม่ได้เปิดขาย จะไม่ปรากฏใน API นี้ ข้อสอบในวิชาและแบบฝึกหัดไม่ถูกส่งในรายการนี้

ตะกร้ารับ `course`, `curriculum`, `exam`, `book`

### แพ็กเกจราคา (`price_plans`)

วิชา หลักสูตร และข้อสอบขายแยกมีได้หลายราคาตามระยะเวลาเรียน ทุกรายการในรายการสินค้าและหน้ารายละเอียดมี `price_plans` เรียงตามที่แอดมินจัด ส่งเฉพาะแพ็กเกจที่เปิดขาย

```json
"price_plans": [
  { "id": 11, "name": "30 วัน", "duration_days": 30, "price": 3000, "sale_price": null, "effective_price": 3000 },
  { "id": 12, "name": "แพ็กคุ้ม", "duration_days": 60, "price": 5000, "sale_price": 4500, "effective_price": 4500 },
  { "id": 13, "name": "ไม่จำกัดเวลา", "duration_days": null, "price": 9000, "sale_price": null, "effective_price": 9000 }
]
```

| ฟิลด์ | ความหมาย |
|------|----------|
| `id` | ส่งเป็น `plan_id` ตอน `POST /checkout/quote` และ `POST /orders` |
| `name` | ชื่อที่แสดง ถ้าแอดมินไม่ตั้งชื่อ ระบบส่ง "30 วัน" หรือ "ไม่จำกัดเวลา" |
| `duration_days` | จำนวนวันที่เรียนได้หลังชำระ `null` = ไม่จำกัดเวลา |
| `effective_price` | ราคาที่ต้องจ่ายของแพ็กเกจนั้น |

`price`, `sale_price`, `effective_price` ระดับรายการคือราคาของแพ็กเกจที่ถูกที่สุด ใช้แสดง "เริ่มต้น ฿..." ในการ์ด ถ้า `price_plans` มีมากกว่าหนึ่งรายการ ให้ผู้ซื้อเลือกแพ็กเกจก่อนเพิ่มลงตะกร้า

เมื่อส่ง token มาด้วย `purchased` เป็นจริงเฉพาะสิทธิ์ที่ยังไม่หมดอายุ และ `access_expires_at` คือวันหมดสิทธิ์ (`null` เมื่อยังไม่ซื้อ หรือซื้อแบบไม่จำกัดเวลา ดูคู่กับ `purchased`) ซื้อซ้ำขณะสิทธิ์ยังเหลือจะต่ออายุจากวันหมดอายุเดิม

### GET `/catalog`

```json
{
  "data": [
    {
      "item_type": "course",
      "id": 3,
      "code": "C001",
      "slug": "physics",
      "name": "วิชาฟิสิกส์",
      "short_description": "สรุปเนื้อหา",
      "thumbnail_url": "https://...",
      "price": 1500,
      "sale_price": 990,
      "effective_price": 990,
      "price_plans": [
        { "id": 11, "name": "30 วัน", "duration_days": 30, "price": 1500, "sale_price": 990, "effective_price": 990 },
        { "id": 12, "name": "90 วัน", "duration_days": 90, "price": 2500, "sale_price": null, "effective_price": 2500 }
      ],
      "is_featured": true,
      "purchased": false,
      "access_expires_at": null,
      "category": { "id": 1, "name": "มัธยม", "slug": "secondary" },
      "subject": { "id": 2, "name": "ฟิสิกส์", "slug": "physics" },
      "instructor": { "id": 1, "name": "ครูเอ", "slug": "teacher-a" },
      "chapter_count": 8,
      "course_count": null,
      "duration_minutes": null,
      "is_trial_available": false
    },
    {
      "item_type": "curriculum",
      "id": 4,
      "code": null,
      "slug": "physics-pack",
      "name": "แพ็กเกจฟิสิกส์",
      "short_description": null,
      "thumbnail_url": null,
      "price": 2500,
      "sale_price": null,
      "effective_price": 2500,
      "price_plans": [
        { "id": 21, "name": "ไม่จำกัดเวลา", "duration_days": null, "price": 2500, "sale_price": null, "effective_price": 2500 }
      ],
      "is_featured": false,
      "purchased": false,
      "access_expires_at": null,
      "category": null,
      "subject": null,
      "instructor": null,
      "chapter_count": null,
      "course_count": 2,
      "duration_minutes": null,
      "is_trial_available": null
    },
    {
      "item_type": "exam",
      "id": 9,
      "code": "E001",
      "slug": "physics-exam",
      "name": "ข้อสอบฟิสิกส์",
      "short_description": null,
      "thumbnail_url": null,
      "price": 490,
      "sale_price": null,
      "effective_price": 490,
      "price_plans": [
        { "id": 31, "name": "30 วัน", "duration_days": 30, "price": 490, "sale_price": null, "effective_price": 490 }
      ],
      "is_featured": false,
      "purchased": false,
      "access_expires_at": null,
      "category": null,
      "subject": null,
      "instructor": null,
      "chapter_count": null,
      "course_count": null,
      "duration_minutes": 90,
      "is_trial_available": null
    }
  ]
}
```

`type` ที่ไม่ใช่สามค่านี้ตอบ `422`

### GET `/courses/{slug}`

```json
{
  "item_type": "course",
  "id": 3,
  "code": "C001",
  "slug": "physics",
  "name": "วิชาฟิสิกส์",
  "short_description": "สรุปเนื้อหา",
  "description": "มีเฉพาะหน้ารายละเอียด",
  "thumbnail_url": "https://...",
  "price": 1500,
  "sale_price": 990,
  "effective_price": 990,
  "price_plans": [
    { "id": 11, "name": "30 วัน", "duration_days": 30, "price": 1500, "sale_price": 990, "effective_price": 990 },
    { "id": 12, "name": "90 วัน", "duration_days": 90, "price": 2500, "sale_price": null, "effective_price": 2500 }
  ],
  "is_featured": true,
  "is_trial_available": false,
  "chapter_count": 8,
  "purchased": true,
  "access_expires_at": "2026-11-16T10:00:00+07:00",
  "category": { "id": 1, "name": "มัธยม", "slug": "secondary" },
  "subject": { "id": 2, "name": "ฟิสิกส์", "slug": "physics" },
  "instructor": { "id": 1, "name": "ครูเอ", "slug": "teacher-a" },
  "chapters": [
    {
      "id": 1,
      "title": "บทที่ 1",
      "description": "แนะนำเนื้อหา",
      "videos": [
        {
          "id": 4,
          "title": "คลิปทดลอง",
          "description": null,
          "duration_seconds": 180,
          "is_trial": true,
          "video_url": "https://api.example.com/api/v1/stream/videos/4?c=3&expires=1792986704&u=12&signature=...",
          "progress": {
            "status": "in_progress",
            "position_seconds": 75,
            "progress_percent": 41.67,
            "last_watched_at": "2026-10-10T13:00:00+07:00"
          }
        }
      ],
      "documents": [
        {
          "id": 2,
          "title": "ใบงานบทที่ 1",
          "description": null,
          "file_name": "worksheet.pdf",
          "file_size": 120000,
          "is_trial": false,
          "file_url": null
        }
      ],
      "exams": []
    }
  ],
  "exams": [
    {
      "id": 9,
      "name": "ข้อสอบท้ายวิชา",
      "slug": "physics-quiz",
      "description": null,
      "duration_minutes": 30,
      "question_count": 10,
      "chapter_id": null
    }
  ]
}
```

ราคาที่ต้องจ่ายคือ `effective_price` ของแพ็กเกจที่เลือกใน `price_plans` ถ้าแพ็กเกจมี `sale_price` มากกว่า 0 ระบบใช้ราคานั้น ไม่เช่นนั้นใช้ `price` ส่วน `effective_price` ระดับวิชาคือราคาเริ่มต้น

`chapters` และ `exams` มีเฉพาะหน้ารายละเอียด `video_url` ส่งเมื่อซื้อวิชาแล้วและสิทธิ์ยังไม่หมดอายุ หรือเมื่อ `is_trial_available` เป็นจริงและคลิปนั้นเปิดทดลองเรียน มิฉะนั้นเป็น `null`

`video_url` เป็นลิงก์สตรีมผ่าน Laravel (`GET /api/v1/stream/videos/{video}`) ที่ลงลายเซ็นแล้ว ผูกกับวิชา (`c`) และผู้ใช้ที่เรียก API (`u`, เป็น 0 เมื่อไม่ได้ล็อกอินและดูคลิปทดลอง) ใช้ได้ 4 ชั่วโมงนับจากเวลาที่เรียก API ไม่ใช่ลิงก์ไฟล์บน R2 หรือ CDN อย่าเก็บไว้ใช้ภายหลัง

ใช้ได้เฉพาะเป็น `src` ของแท็ก `<video>` (ไม่ต้องใส่ `crossorigin`) ระบบตรวจทุกคำขอดังนี้

| ตรวจ | ไม่ผ่าน |
|------|---------|
| ลายเซ็นและเวลาหมดอายุ (แก้ `u`, `c` หรือ `expires` ไม่ได้) | `403` |
| ผู้ใช้ยังมีสิทธิ์ดู (ซื้อแล้วและไม่หมดอายุ หรือคลิปทดลองของวิชาที่เปิดทดลองเรียน) | `403` |
| header `Sec-Fetch-Dest` เป็น `video` (เบราว์เซอร์ใส่เองเมื่อโหลดจาก `<video>`) ถ้าเบราว์เซอร์ไม่ส่ง header นี้ ต้องมี `Referer` จาก `STUDENT_APP_URL` หรือ `FRONTEND_URL` | `403` เช่น เปิดลิงก์ในแท็บใหม่ (`document`) หรือดาวน์โหลดตรง |
| ต้องมี header `Range` | `416` |

ตอบ `206` ครั้งละไม่เกิน 2 MB (`Content-Range` บอกช่วงจริง) ตัวเล่นขอช่วงถัดไปเอง และส่ง `Cache-Control: private, no-store` ถ้าตัวเล่นแจ้ง error เพราะลิงก์หมดอายุหรือถูกปฏิเสธ ให้เรียก `GET /courses/{slug}` ใหม่แล้วเล่นต่อจากเวลาเดิม

ข้อจำกัด: ป้องกันการเปิดลิงก์จากแท็บ Network และการโหลดไฟล์ทั้งก้อนได้ แต่คนที่คัดลอก request พร้อม header ทั้งหมดไปใช้ในโปรแกรมอื่นยังดึงทีละช่วงได้จนกว่าลิงก์จะหมดอายุ และกันการอัดหน้าจอไม่ได้ ถ้าต้องการป้องกันจริงต้องใช้ HLS แบบเข้ารหัสหรือ DRM

แต่ละบทมี `documents` และ `exams` ข้อสอบที่ไม่ได้ผูกกับบทใดบทหนึ่งอยู่ที่ `exams` ระดับวิชา `file_url` ของเอกสารส่งเมื่อซื้อวิชาแล้ว หรือเมื่อวิชาเปิดทดลองเรียนและเอกสารนั้นเป็นเอกสารทดลอง มิฉะนั้นเป็น `null`

`progress` ของแต่ละคลิปเป็นของผู้ใช้ที่ส่ง token ถ้ายังไม่ล็อกอินหรือยังไม่เคยกดดู `status` เป็น `unwatched` และ `position_seconds` เป็น 0

| status | ความหมาย |
|--------|----------|
| `unwatched` | ยังไม่ได้ดู |
| `in_progress` | ดูแล้ว แต่ยังไม่จบ ใช้ `position_seconds` เปิดต่อจากนาทีเดิม |
| `completed` | ผ่านแล้ว ดูจบหรือดูอย่างน้อย 90% |

### PUT `/courses/{slug}/videos/{video}/progress`

ต้องใช้ token เรียกระหว่างดูวิดีโอ เป็นระยะ ตอนกดหยุด และตอนคลิปจบ เพื่อให้กลับมาแล้วเล่นต่อจากจุดเดิมได้

| ฟิลด์ | บังคับ | หมายเหตุ |
|------|--------|----------|
| position_seconds | ใช่ | วินาทีที่ดูถึง เป็นจำนวนเต็ม 0 ถึง 86400 |
| duration_seconds | ไม่ | ความยาวจริงจากตัวเล่น ถ้าไม่ส่ง ระบบใช้ความยาวที่บันทึกไว้กับคลิป |
| completed | ไม่ | ส่ง `true` เมื่อคลิปเล่นจบ ระบบนับว่าผ่านแล้วเมื่อค่านี้เป็นจริง หรือเมื่อดูถึง 90% |

ตอบ `404` เมื่อไม่พบวิชาหรือคลิปไม่ได้อยู่ในวิชานั้น ตอบ `403` เมื่อยังไม่มีสิทธิ์ดูคลิปนั้น

```json
{
  "data": {
    "video_id": 4,
    "status": "in_progress",
    "position_seconds": 75,
    "progress_percent": 41.67,
    "last_watched_at": "2026-10-10T13:00:00+07:00"
  }
}
```

### GET `/curriculums/{slug}`

ฟิลด์ราคา `price_plans`, `purchased`, `access_expires_at` และรูปเหมือนวิชา และมี `item_type` เป็น `curriculum` ซื้อหลักสูตรแล้ว วิชาและข้อสอบในหลักสูตรได้สิทธิ์ตามระยะเวลาของแพ็กเกจหลักสูตร หน้ารายละเอียดมี `courses` เป็นชื่อวิชาที่เผยแพร่แล้วในหลักสูตรนั้น แพ็กเกจหลักสูตรไม่มีหนังสือ หนังสือซื้อแยกผ่าน `/books`

```json
"courses": [
  { "id": 3, "name": "วิชาฟิสิกส์", "slug": "physics", "thumbnail_url": null }
]
```

### GET `/exams/{slug}`

รายละเอียดข้อสอบขายแยก ไม่มีคำถาม

```json
{
  "item_type": "exam",
  "id": 9,
  "code": "E001",
  "slug": "physics-exam",
  "name": "ข้อสอบฟิสิกส์",
  "description": "แนวข้อสอบกลางภาค",
  "thumbnail_url": null,
  "price": 490,
  "sale_price": null,
  "effective_price": 490,
  "price_plans": [
    { "id": 31, "name": "30 วัน", "duration_days": 30, "price": 490, "sale_price": null, "effective_price": 490 }
  ],
  "duration_minutes": 90,
  "question_count": 40,
  "purchased": false,
  "access_expires_at": null
}
```

`duration_minutes` คือเวลาทำข้อสอบต่อครั้ง ส่วน `price_plans[].duration_days` คือจำนวนวันที่มีสิทธิ์ทำข้อสอบ

### GET `/books` และ GET `/books/{slug}`

```json
{
  "id": 5,
  "code": "B001",
  "slug": "math-book",
  "name": "หนังสือคณิต",
  "description": "มีเฉพาะหน้ารายละเอียด",
  "thumbnail_url": null,
  "images": ["https://.../gallery/a.webp"],
  "video_url": "https://.../video/5.mp4",
  "price": 350,
  "sale_price": null,
  "effective_price": 350,
  "stock": 4,
  "in_stock": true,
  "purchased": false
}
```

`description`, `images` และ `video_url` มีเฉพาะหน้ารายละเอียด `video_url` เป็น `null` เมื่อยังไม่มีวิดีโอ `images` เป็นรูปเพิ่มเติมนอกจากปก `stock` เป็น `null` แปลว่าไม่จำกัดจำนวน `purchased` ของหนังสือเป็นจริงเมื่อมีออเดอร์ที่ชำระแล้วซึ่งมีหนังสือเล่มนั้น

## แบนเนอร์ บทความ และรีวิว

รายการนี้เป็นสาธารณะ ไม่ต้องใช้ token แต่ต้องส่ง `X-Api-Key` แบนเนอร์ส่งเมื่อสถานะเผยแพร่และอยู่ในช่วงเวลาเริ่มถึงสิ้นสุด บทความและรีวิวส่งเมื่อสถานะเป็นเผยแพร่ ร่างและรายการที่ปิดใช้งานจะไม่ปรากฏ

### GET `/banners`

พารามิเตอร์ `placement` ไม่บังคับ

| ค่า | ตำแหน่งบนเว็บ |
|----|----------------|
| `home` | หน้าแรก |
| `courses` | หน้าวิชา |
| `books` | หน้าหนังสือ |

ไม่ส่ง `placement` จะได้แบนเนอร์ทุกตำแหน่งที่กำลังแสดง สูงสุด 30 รายการ เรียงตามลำดับที่แอดมินจัดไว้

```json
{
  "data": [
    {
      "id": 1,
      "title": "เปิดเทอมใหม่",
      "excerpt": "ลดราคาวิชาคณิต",
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

บทความไม่มีวันเริ่มหรือวันสิ้นสุด แสดงเมื่อสถานะเป็นเผยแพร่ รายการใช้ `search`, `page`, `per_page` เหมือนสินค้า เรียงจากรายการล่าสุด รายการไม่มีเนื้อหาเต็ม เพื่อให้โหลดหน้าแรกเร็ว

```json
{
  "id": 4,
  "slug": "exam-tips",
  "title": "เทคนิคทำข้อสอบ",
  "excerpt": "อ่านโจทย์ให้ครบก่อนลงมือ",
  "image_url": null,
  "seo": {
    "title": "เทคนิคทำข้อสอบ | Click Class Tutor",
    "description": "อ่านโจทย์ให้ครบก่อนลงมือ",
    "keywords": "ข้อสอบ, เทคนิค",
    "image_url": null
  }
}
```

ฟิลด์ `seo` มีทั้งในรายการและหน้ารายละเอียด สำหรับ `useSeoMeta` / Open Graph บน Nuxt ค่าที่ส่งเป็นค่าพร้อมใช้แล้ว ถ้าแอดมินไม่ใส่ SEO title จะใช้หัวข้อบทความ ถ้าไม่ใส่ SEO description จะใช้คำเกริ่น (อาจเป็น `null`) `image_url` ใน `seo` คือรูปปกบทความ

หน้ารายละเอียด `GET /articles/{slug}` เพิ่มฟิลด์ `content` เป็น HTML ที่ทำความสะอาดแล้ว แสดงด้วย `v-html` ได้ แท็กที่ใช้ได้คือหัวข้อ ย่อหน้า รายการ ลิงก์ รูป โค้ด และข้อความตัวหนาหรือเอียง และมี `related` สำหรับวิชา หลักสูตร และข้อสอบขายแยกที่แอดมินผูกไว้ (เลือกได้หลายรายการ) ส่งเฉพาะรายการที่เผยแพร่อยู่แล้ว ใช้ `slug` ไปเปิดหน้ารายละเอียดสินค้าได้

```json
{
  "id": 4,
  "slug": "exam-tips",
  "title": "เทคนิคทำข้อสอบ",
  "excerpt": "อ่านโจทย์ให้ครบก่อนลงมือ",
  "image_url": null,
  "seo": {
    "title": "เทคนิคทำข้อสอบ | Click Class Tutor",
    "description": "อ่านโจทย์ให้ครบก่อนลงมือ",
    "keywords": "ข้อสอบ, เทคนิค",
    "image_url": null
  },
  "content": "<p>...</p>",
  "related": {
    "courses": [
      {
        "id": 12,
        "slug": "math-m1",
        "name": "คณิต ม.1",
        "thumbnail_url": "https://..."
      }
    ],
    "curriculums": [],
    "exams": [
      {
        "id": 3,
        "slug": "mock-midterm",
        "name": "จำลองกลางภาค",
        "thumbnail_url": null
      }
    ]
  }
}
```

รายการบทความไม่มี `related` และไม่มี `content` แต่มี `seo`

### GET `/reviews`

รีวิวจากแอดมินสำหรับแสดงบนเว็บ เช่น หน้าแรก ส่งเฉพาะรายการสถานะเผยแพร่ สูงสุด 50 รายการ เรียงตามลำดับที่แอดมินจัดไว้ ร่างและปิดใช้งานจะไม่ปรากฏ

```json
{
  "data": [
    {
      "id": 1,
      "name": "คุณมิน",
      "content": "เรียนแล้วเข้าใจง่าย สอบติดตามเป้า",
      "image_url": "https://..."
    }
  ]
}
```

`image_url` เป็น `null` ได้ถ้ายังไม่มีหน้าปก `content` เป็นข้อความธรรมดา ไม่ใช่ HTML

## สั่งซื้อและชำระเงิน

หน้าร้านชำระได้สามวิธี

| วิธี | ขั้นตอน |
|------|---------|
| QR พร้อมเพย์ (`thai_qr`) | ผ่าน KGP ดูหัวข้อ [ชำระเงินออนไลน์](#ชำระเงินออนไลน์-qr-พร้อมเพย์-และบัตร) |
| บัตรเครดิต / เดบิต (`credit_card`) | ผ่าน Omise ดูหัวข้อเดียวกัน |
| โอนเงิน (`bank_transfer`) | โอนแล้วอัปโหลดสลิปด้วย `POST /orders/{id}/payment` แอดมินตรวจในหน้าการเงิน |

เมื่อสถานะเป็นชำระแล้ว ระบบเปิดสิทธิ์เรียนให้เอง เลขบัญชีสำหรับโอนให้ตั้งในโปรเจกต์ Nuxt เพราะ API นี้ยังไม่เก็บข้อมูลบัญชีธนาคาร

ประเภทสินค้าในตะกร้าคือ `course`, `curriculum`, `exam`, `book` วิชา หลักสูตร และข้อสอบจำนวนเป็น 1 เสมอ หนังสือส่ง `quantity` ได้ 1–99

วิชา หลักสูตร และข้อสอบส่ง `plan_id` จาก `price_plans` ได้ ถ้ารายการนั้นมีแพ็กเกจเปิดขายแพ็กเกจเดียว ไม่ส่งก็ได้ ระบบเลือกให้ ถ้ามีหลายแพ็กเกจต้องส่ง ไม่ส่งตอบ `422` ที่ฟิลด์ `items` ข้อความ `กรุณาเลือกแพ็กเกจราคาของ ...` `plan_id` ที่ปิดขายแล้วหรือไม่ใช่ของรายการนั้นตอบ `422` เช่นกัน หนังสือไม่มีแพ็กเกจ ตะกร้าส่งรายการเดียวกันซ้ำ ระบบใช้แถวแรก

ออเดอร์หนึ่งใบเป็นชนิดเดียว ตะกร้าที่ปนหนังสือกับวิชาหรือหลักสูตรตอบ `422` ที่ฟิลด์ `items` พร้อมข้อความ `แยกคำสั่งซื้อหนังสือกับสมัครเรียนคนละออเดอร์`

ถ้าตะกร้ามีหนังสือ ต้องส่งที่อยู่จัดส่ง และออเดอร์ได้ `kind` เป็น `book` วิชาหรือหลักสูตรอย่างเดียวไม่ต้องส่งที่อยู่ ไม่ต้องจัดส่ง และได้ `kind` เป็น `enrollment`

### POST `/checkout/quote`

คำนวณยอดก่อนสร้างออเดอร์ ต้องล็อกอิน ใช้ตรวจคูปองและแสดงยอดในหน้าชำระเงิน

```json
{
  "items": [
    { "type": "course", "id": 3, "plan_id": 11 }
  ],
  "coupon_code": "SAVE10"
}
```

```json
{
  "data": {
    "items": [
      {
        "type": "course",
        "id": 3,
        "name": "วิชาฟิสิกส์ (30 วัน)",
        "quantity": 1,
        "unit_price": 990,
        "total_price": 990,
        "plan": { "id": 11, "name": "30 วัน", "duration_days": 30 }
      }
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

คูปองตรวจเฉพาะเงื่อนไขของคูปอง (สถานะ ช่วงเวลา จำนวนครั้ง รายการที่ใช้ได้ ยอดขั้นต่ำ) เงื่อนไขของโปรโมชันในหลังบ้าน (กลุ่มนักเรียน ต้องเคยซื้อ ต้องดูวิดีโอกี่วินาที ของแถม) ยังไม่ถูกตรวจและยังไม่รวมใน API ดู `docs/promotions.md`

รหัสคูปองที่ไม่ผ่านเงื่อนไขตอบ `422` `plan` ของรายการเป็น `null` สำหรับหนังสือ `plan.id` อาจเป็น `null` สำหรับรายการเก่าที่ยังไม่มีแพ็กเกจ `name` ของรายการต่อท้ายด้วยชื่อแพ็กเกจเมื่อเป็นแบบมีวันหมดอายุหรือรายการมีหลายแพ็กเกจ

### POST `/orders`

สร้างออเดอร์ สถานะชำระเริ่มที่ `pending` ถ้ายอดสุทธิเป็น 0 ระบบตัดเป็นชำระแล้วและเปิดสิทธิ์เรียนทันที หลังชำระ สิทธิ์เรียนมีอายุตาม `plan.duration_days` นับจากวันชำระ หรือต่อจากวันหมดอายุเดิมถ้าสิทธิ์ยังเหลือ

สมัครเรียน

```json
{
  "items": [
    { "type": "course", "id": 3, "plan_id": 11 }
  ],
  "coupon_code": "SAVE10",
  "notes": null
}
```

ขายหนังสือ ส่ง `shipping` เมื่อมีหนังสือ

```json
{
  "items": [
    { "type": "book", "id": 5, "quantity": 2 }
  ],
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
    "kind": "enrollment",
    "subtotal": 990,
    "discount": 0,
    "shipping_amount": 0,
    "total": 990,
    "status": "pending",
    "status_label": "รอดำเนินการ",
    "payment_status": "pending",
    "payment_status_label": "รอชำระเงิน",
    "requires_shipping": false,
    "shipping": null,
    "payment": {
      "method": "bank_transfer",
      "method_label": "โอนเงิน",
      "status": "pending",
      "status_label": "รอชำระเงิน",
      "amount": 990,
      "fee_amount": 0,
      "paid_at": null,
      "slip_url": null,
      "provider": null,
      "reference": null,
      "expires_at": null,
      "failure_message": null
    },
    "items": [
      {
        "type": "course",
        "id": 3,
        "name": "วิชาฟิสิกส์ (30 วัน)",
        "quantity": 1,
        "unit_price": 990,
        "total_price": 990,
        "plan": { "id": 11, "name": "30 วัน", "duration_days": 30 }
      }
    ],
    "receipt": null,
    "created_at": "2026-09-29T02:00:00+07:00",
    "paid_at": null
  }
}
```

`receipt` เป็น `null` จนกว่าออเดอร์จะชำระแล้ว หลังชำระ:

```json
"receipt": {
  "receipt_no": "RC26100001",
  "issued_at": "2026-10-20T03:59:00+00:00",
  "total": 580,
  "download_url": "https://api.example.com/receipts/12/download?expires=...&signature=..."
}
```

`download_url` เป็นลิงก์ PDF ที่เปิดได้โดยไม่ต้องแนบ token ใช้ใน `<a href>` ได้เลย หมดอายุใน 60 นาที (เรียก `GET /orders/{id}` ใหม่เพื่อได้ลิงก์ใหม่) ต่อท้าย `&inline=1` เพื่อเปิดในเบราว์เซอร์แทนการดาวน์โหลด ดูหัวข้อ [ใบเสร็จรับเงิน](#ใบเสร็จรับเงิน)

`payment` คือรายการชำระล่าสุดของออเดอร์ `payment.amount` คือยอดที่ลูกค้าต้องจ่ายจริง รวมค่าธรรมเนียมตามวิธีชำระแล้ว `payment.fee_amount` คือค่าธรรมเนียมในยอดนั้น (0 เมื่อไม่เก็บ) ส่วน `total` ของออเดอร์ไม่รวมค่าธรรมเนียม หน้าโอนเงินควรให้ลูกค้าโอนตาม `payment.amount` `provider` เป็น `kgp`, `omise` หรือ `fake` (โหมดทดสอบ) เมื่อจ่ายผ่าน QR หรือบัตร และเป็น `null` สำหรับการโอนเงิน `reference` คือเลขอ้างอิงที่แสดงให้ลูกค้าได้ `expires_at` คือเวลาหมดอายุของ QR `failure_message` มีค่าเมื่อการชำระล่าสุดไม่สำเร็จ

สถานะชำระเงินที่ใช้บนหน้าร้าน

| ค่า | ความหมาย |
|----|----------|
| `pending` | รอชำระ (ยังไม่จ่าย หรือ QR ยังไม่ถูกสแกน) |
| `awaiting_verification` | ส่งสลิปแล้ว รอแอดมินตรวจ |
| `paid` | ชำระแล้ว เปิดสิทธิ์เรียนแล้ว |
| `failed` | แอดมินตรวจสลิปไม่ผ่าน ส่งสลิปใหม่ได้ |
| `refunded` | คืนเงินแล้ว ส่งสลิปใหม่ไม่ได้ ตั้งโดยแอดมิน หรืออัตโนมัติเมื่อ KGP ยืนยันการคืนเงินเต็มจำนวนของ QR พร้อมเพย์ |

ถ้า QR หมดอายุหรือบัตรไม่ผ่าน `payment.status` ของรายการล่าสุดจะเป็น `failed` แต่ `payment_status` ของออเดอร์ยังเป็น `pending` ให้ลูกค้าลองจ่ายใหม่ได้

### GET `/orders` และ GET `/orders/{id}`

รายการออเดอร์ของนักเรียนที่ล็อกอินเท่านั้น ใช้ `id` จากตอนสร้างออเดอร์ ไม่ใช่ `order_no`

แต่ละรายการมี `kind` เป็น `book` หรือ `enrollment` กรองได้ด้วย `GET /orders?kind=book` หรือ `GET /orders?kind=enrollment` ค่าอื่นตอบ `422`

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

หลังส่งสำเร็จ `payment_status` เป็น `awaiting_verification` และ `payment.slip_url` เป็นที่อยู่ไฟล์สลิป ส่งซ้ำได้จนกว่าออเดอร์จะถูกตัดเป็นชำระแล้ว ถ้าแอดมินเปิดค่าธรรมเนียมโอนเงิน `payment.amount` จะรวมค่าธรรมเนียมแล้ว

## ใบเสร็จรับเงิน

ออเดอร์ที่ชำระแล้วมีใบเสร็จรับเงิน PDF 1 ใบ เลขที่รูปแบบ `RC{ปีเดือน}{ลำดับ}` ระบบแนบ PDF ไปกับอีเมล "ผลการสั่งซื้อ" ตอนชำระสำเร็จ และดาวน์โหลดซ้ำได้ตลอด รายละเอียดเนื้อหาดู `docs/receipts.md`

| Endpoint | สิทธิ์ | ผล |
|------|------|------|
| `GET /orders/{id}/receipt` | Bearer token ของเจ้าของออเดอร์ | PDF |
| `GET /guest/orders/{guest_token}/receipt` | `guest_token` ของออเดอร์ | PDF |
| `receipt.download_url` | ไม่ต้องใช้ token (ลิงก์ signed) | PDF |

ตอบ `Content-Type: application/pdf` และ `Content-Disposition: attachment; filename="receipt-RC26100001.pdf"` ใส่ `?inline=1` เพื่อให้เป็น `inline` ออเดอร์ที่ยังไม่ชำระตอบ `404` ออเดอร์ของคนอื่นตอบ `404` ทั้งสอง endpoint จำกัด 20 ครั้งต่อนาที

```ts
// ดาวน์โหลดด้วย token (กรณีไม่ใช้ download_url)
const blob = await api(`/orders/${orderId}/receipt`, { responseType: 'blob' })
const url = URL.createObjectURL(blob)
window.open(url)
```

## สั่งซื้อโดยไม่ต้องสมัครสมาชิก (guest)

ลูกค้าไม่ต้องสมัครหรือเข้าสู่ระบบ ส่งอีเมลมาพร้อมตะกร้า ไม่ต้องส่ง token แต่ยังต้องส่ง `X-Api-Key` ตะกร้า แพ็กเกจราคา คูปอง และที่อยู่จัดส่งใช้กติกาเดียวกับ `POST /orders`

| อีเมล | ระบบทำอะไร |
|------|------|
| ยังไม่มีในระบบ | สร้างบัญชีนักเรียน (สถานะเปิดใช้งาน) ตั้งรหัสผ่านสุ่ม ส่งอีเมลบัญชีพร้อมรหัสผ่าน เลขคำสั่งซื้อ และปุ่มเข้าสู่ระบบ ทันที แล้วสร้างออเดอร์ให้บัญชีนั้น |
| เป็นบัญชีนักเรียนอยู่แล้ว | สร้างออเดอร์ให้บัญชีเดิม **ไม่เปลี่ยนรหัสผ่าน** ส่งอีเมลแจ้งว่ามีคำสั่งซื้อและให้เข้าสู่ระบบด้วยบัญชีเดิม (ลืมรหัสผ่านใช้เมนูลืมรหัสผ่าน) |
| เป็นบัญชีแอดมิน/พนักงาน | `422` ที่ `email` "อีเมลนี้ใช้สั่งซื้อไม่ได้ กรุณาใช้อีเมลอื่น" |
| บัญชีถูกปิดใช้งานหรือถูกลบ | `422` ที่ `email` "บัญชีของอีเมลนี้ถูกปิดใช้งาน กรุณาติดต่อแอดมิน" |

เมื่อชำระเงินสำเร็จ ลูกค้าได้อีเมล "ผลการสั่งซื้อ" อีกฉบับพร้อมใบเสร็จรับเงิน PDF แนบ (ทั้งแบบ guest และแบบเข้าสู่ระบบ) ฉบับนี้ไม่มีรหัสผ่าน บัญชีใหม่ได้รหัสผ่านในอีเมลแรกตอนสั่งซื้อ บัญชีเดิมใช้รหัสผ่านเดิม

ระบบไม่ส่ง token เข้าสู่ระบบกลับมาใน guest API เพราะใครก็พิมพ์อีเมลคนอื่นได้ ลูกค้าดูออเดอร์ จ่ายเงิน และส่งสลิปได้ด้วย `guest_token` ของออเดอร์นั้น ถ้าจะดูวิชาของฉันหรือเข้าเรียน ให้เข้าสู่ระบบด้วยรหัสผ่านที่ได้ทางอีเมล

ถ้าตะกร้าหรือที่อยู่ไม่ผ่าน ระบบตอบ `422` และไม่สร้างบัญชี

### POST `/guest/checkout/quote`

คำนวณยอดก่อนสั่งซื้อ ไม่สร้างบัญชี ใช้ body เดียวกับ `POST /checkout/quote` และต้องมี `email` (คูปองที่จำกัดต่อคนคิดจากบัญชีของอีเมลนั้นถ้ามี) ตอบกลับรูปแบบเดียวกับ `POST /checkout/quote` จำกัด 30 ครั้งต่อนาที

### POST `/guest/orders`

| ฟิลด์ | บังคับ | หมายเหตุ |
|------|--------|----------|
| email | ใช่ | ระบบแปลงเป็นตัวพิมพ์เล็ก |
| name | ไม่ | ชื่อบัญชีใหม่ ไม่ส่งใช้ส่วนหน้า `@` ของอีเมล ไม่เปลี่ยนชื่อบัญชีเดิม |
| phone | ไม่ | เบอร์ของบัญชีใหม่ |
| items, coupon_code, notes, shipping | ตาม `POST /orders` | |

```json
{
  "email": "new.student@example.com",
  "name": "ใหม่ ใจดี",
  "phone": "0812345678",
  "items": [{ "type": "course", "id": 3, "plan_id": 11 }]
}
```

ตอบกลับ `201` จำกัด 10 ครั้งต่อนาที

```json
{
  "data": {
    "order": { "id": 41, "order_no": "O261019002", "payment_status": "pending", "total": 990, "payment": { "...": "เหมือน GET /orders/{id}" }, "items": [] },
    "guest_token": "ua32DDOfWMx1ibhC7tLHCFMeJkWJFggvlM2uF3Rtt5AWoHaU",
    "account_created": true,
    "account_email_sent": true,
    "login_url": "https://clickclasstutor.com/login",
    "payment_url": "https://api.example.com/pay/Xy12...",
    "payment_url_expires_at": "2026-10-19T20:30:00+07:00"
  }
}
```

| ฟิลด์ | ความหมาย |
|------|----------|
| `guest_token` | รหัสลับ 48 ตัวของออเดอร์นี้ เก็บไว้ในเบราว์เซอร์ (เช่น localStorage) ใช้กับ `GET /guest/orders/{guest_token}` ชำระออนไลน์ (`/guest/orders/{guest_token}/payment/...` ดูหัวข้อชำระเงินออนไลน์) และส่งสลิป ห้ามแสดงในหน้าเว็บหรือใส่ใน log |
| `account_created` | `true` เมื่อสร้างบัญชีใหม่ `false` เมื่อใช้บัญชีเดิม |
| `account_email_sent` | ส่งอีเมลสำเร็จหรือไม่ ถ้า `false` ออเดอร์ยังถูกสร้าง ให้บอกลูกค้าว่าใช้เมนูลืมรหัสผ่านได้ |
| `payment_url` | ลิงก์หน้าชำระเงินของ Laravel `null` เมื่อออเดอร์ไม่มียอดต้องจ่าย (ยอด 0 ระบบตัดเป็นชำระแล้วทันที) เว็บนักเรียนไม่ใช้แล้ว ชำระในหน้าออเดอร์ของ Nuxt ผ่าน `/guest/orders/{guest_token}/payment/...` แทน |

หลังยืนยัน 3-D Secure ธนาคารพากลับไปที่ `{STUDENT_APP_URL}/orders/{order_id}?guest_token=...&payment_return=card` ถ้าตั้ง `PAYMENT_RETURN_URL` เองใส่ `{guest_token}` ในรูปแบบ URL ได้ หน้านั้นใน Nuxt ถ้าไม่ได้เข้าสู่ระบบให้ใช้ `guest_token` เรียก `GET /guest/orders/{guest_token}`

### GET `/guest/orders/{guest_token}`

ดูสถานะออเดอร์ ถ้ารอชำระผ่าน QR หรือบัตร ระบบเช็กกับ gateway ให้ด้วย ใช้ถามสถานะเป็นระยะได้

```json
{
  "data": {
    "order": { "id": 41, "order_no": "O261019002", "payment_status": "paid", "payment": {}, "items": [] },
    "payment_options": [
      { "method": "thai_qr", "label": "QR พร้อมเพย์", "fee_label": null, "order_amount": 990, "fee_amount": 0, "total": 990 }
    ],
    "payment_url": null,
    "payment_url_expires_at": null
  }
}
```

`payment_url` ได้ลิงก์ใหม่เมื่อลิงก์เดิมหมดอายุ และเป็น `null` เมื่อชำระแล้ว `guest_token` ผิดตอบ `404`

### POST `/guest/orders/{guest_token}/payment`

ส่งสลิปโอนเงินแบบ `multipart/form-data` ฟิลด์เดียวกับ `POST /orders/{id}/payment` (`slip`, `note`) ตอบ `{ "data": { "order": { ... } } }` จำกัด 10 ครั้งต่อนาที

## ชำระเงินออนไลน์ (QR พร้อมเพย์ และบัตร)

ออเดอร์ที่ชำระแล้ว คืนเงินแล้ว ยกเลิก หรือยอดเป็น 0 ตอบ `422` ที่ฟิลด์ `order` รายละเอียดฝั่งระบบดู `docs/payments.md`

เว็บนักเรียนชำระเงินในหน้าออเดอร์ของ Nuxt เอง ไม่ redirect ไปหน้า Laravel หน้า QR ทำตามคู่มือ KGP (`docs/omise/attachments/KGP QR UXUI_Instruction_Guideline _v2_SA_20260713.pdf`) และบัตรสร้าง token ด้วย Omise.js ในเบราว์เซอร์ ทุก endpoint มีสองชุด path ต่างกัน แต่ body และ response เหมือนกัน

| ใช้กับ | path ฐาน | ต้องใช้ |
|------|------|------|
| นักเรียนที่ล็อกอิน | `/orders/{id}/payment` | token ของเจ้าของออเดอร์ |
| สั่งซื้อแบบไม่สมัครสมาชิก | `/guest/orders/{guest_token}/payment` | `guest_token` ไม่ต้องใช้ token |

| endpoint ต่อจาก path ฐาน | ใช้ทำอะไร |
|------|------|
| `POST .../thai-qr` | สร้าง QR พร้อมเพย์ |
| `POST .../card` | ตัดบัตรด้วย token จาก Omise.js |
| `GET .../status` | ถามสถานะรายการล่าสุด (เช็กกับ gateway ให้ด้วย) |
| `POST .../simulate` | จำลองผล เฉพาะโหมดทดสอบ (ดูด้านล่าง) |

ตอนนี้ QR อยู่ในโหมดจำลอง (`test_mode.thai_qr: true`) สแกนไม่ได้ ใช้ `POST .../simulate` แทนการสแกน ส่วนบัตรใช้ Omise test mode แล้ว (`test_mode.credit_card: false` แต่ `omise_public_key` ขึ้นต้น `pkey_test_`) ใช้บัตรทดสอบ `4242 4242 4242 4242` = สำเร็จ, `4111 1111 1114 0011` = ไม่ผ่าน ไม่มีเงินจริง

`POST /orders/{id}/payment-link` (หน้าชำระเงินของ Laravel `/pay/{token}`) ยังใช้ได้ สำหรับลิงก์ที่แอดมินส่งให้ลูกค้าเอง เว็บนักเรียนไม่ใช้แล้ว

### GET `/payment-methods`

ไม่ต้องใช้ token (ลูกค้าแบบไม่สมัครสมาชิกต้องใช้ `omise_public_key` ด้วย) ต้องส่ง `X-Api-Key`

```json
{
  "data": {
    "methods": ["thai_qr", "credit_card"],
    "test_mode": {
      "thai_qr": true,
      "credit_card": false
    },
    "omise_public_key": "pkey_test_...",
    "merchant_name": "Click Class Tutor",
    "fees": [
      { "method": "thai_qr", "enabled": false, "label": null, "percent": 0, "fixed_amount": 0, "min_amount": null, "max_amount": null },
      { "method": "credit_card", "enabled": true, "label": "ค่าธรรมเนียมบัตรเครดิต (3.5% + 10.00 บาท)", "percent": 3.5, "fixed_amount": 10, "min_amount": null, "max_amount": null },
      { "method": "bank_transfer", "enabled": false, "label": null, "percent": 0, "fixed_amount": 0, "min_amount": null, "max_amount": null }
    ]
  }
}
```

`methods` คือวิธีที่เปิดอยู่ `test_mode` บอกว่าวิธีไหนเป็นตัวจำลองที่ไม่เรียก gateway `omise_public_key` ใช้กับ `Omise.setPublicKey()` เป็น `null` เมื่อบัตรเป็นตัวจำลอง `fees` คือกติกาค่าธรรมเนียมที่แอดมินตั้ง ใช้แสดงข้อความได้ ยอดจริงต่อออเดอร์ให้ใช้ `GET /orders/{id}/payment/options`

### GET `/orders/{id}/payment/options`

ยอดที่ต้องจ่ายของออเดอร์นี้แยกตามวิธีชำระ รวมค่าธรรมเนียมแล้ว ใช้แสดงก่อนให้ลูกค้าเลือกวิธี มีวิธีที่เปิดออนไลน์และโอนเงินเสมอ

```json
{
  "data": [
    { "method": "thai_qr", "label": "QR พร้อมเพย์", "fee_label": null, "order_amount": 290, "fee_amount": 0, "total": 290 },
    { "method": "credit_card", "label": "บัตรเครดิต", "fee_label": "ค่าธรรมเนียมบัตรเครดิต (3.5% + 10.00 บาท)", "order_amount": 290, "fee_amount": 20.15, "total": 310.15 },
    { "method": "bank_transfer", "label": "โอนเงิน", "fee_label": null, "order_amount": 290, "fee_amount": 0, "total": 290 }
  ]
}
```

ค่าธรรมเนียมคิดตอนเริ่มชำระ ถ้าแอดมินเปลี่ยนค่าระหว่างนั้น ยอดใน QR หรือการตัดบัตรจะใช้ค่าล่าสุด ยึด `payment.amount` ที่ได้จาก endpoint สร้าง QR หรือตัดบัตรเป็นยอดจริง

### POST `/orders/{id}/payment-link`

ขอลิงก์หน้าชำระเงินของ Laravel ลิงก์เดิมที่ยังไม่หมดอายุจะถูกใช้ซ้ำ เว็บนักเรียนไม่ใช้แล้ว เหลือไว้สำหรับกรณีที่ต้องการส่งลิงก์ให้ลูกค้าจ่ายนอกเว็บ

```json
{
  "data": {
    "url": "https://api.example.com/pay/Xy12...",
    "expires_at": "2026-10-17T10:30:00+07:00"
  }
}
```

```ts
const { data } = await api(`/orders/${orderId}/payment-link`, { method: 'POST' })
window.location.href = data.url
```

เมื่อจ่ายเสร็จ หน้าชำระเงินมีปุ่มพากลับมาที่ `PAYMENT_RETURN_URL` ของ Laravel ค่าเริ่มต้นคือ `{STUDENT_APP_URL}/orders/{order_id}` หน้านั้นใน Nuxt ควรเรียก `GET /orders/{id}` ใหม่เพื่อดูสถานะล่าสุด

### POST `/orders/{id}/payment/thai-qr` และ POST `/guest/orders/{guest_token}/payment/thai-qr`

สร้าง QR พร้อมเพย์ ถ้ามี QR ที่ยังไม่หมดอายุและยอดเท่าเดิมจะได้ QR เดิมกลับมา

```json
{
  "data": {
    "order_id": 10,
    "order_no": "O261017001",
    "order_payment_status": "pending",
    "paid": false,
    "payment": {
      "id": 25,
      "method": "thai_qr",
      "method_label": "QR พร้อมเพย์",
      "provider": "kgp",
      "reference": "CCT0000000025",
      "status": "pending",
      "status_label": "รอชำระเงิน",
      "amount": 295,
      "order_amount": 290,
      "fee_amount": 5,
      "fee_label": "ค่าธรรมเนียม QR พร้อมเพย์ (5.00 บาท)",
      "currency": "THB",
      "expires_at": "2026-10-17T09:40:00+07:00",
      "paid_at": null,
      "failure_code": null,
      "failure_message": null,
      "qr_image": "data:image/png;base64,iVBORw0...",
      "merchant_name": "Click Class Tutor",
      "authorize_uri": null
    }
  }
}
```

แสดง `qr_image` ใน `<img :src>` ได้ทันที ในโหมดทดสอบเป็น SVG จำลอง ระหว่างรอ ให้เรียก `GET .../payment/status` ทุก 3–5 วินาที จนกว่า `paid` เป็นจริง หรือ `payment.status` ไม่ใช่ `pending` สร้าง QR ได้ 10 ครั้งต่อนาที

ส่วน QR บนหน้าเว็บต้องมีตามคู่มือ KGP: หัวสีน้ำเงิน `#00427A` ข้อความ THAI QR PAYMENT, โลโก้ PromptPay, "Powered by KGP", รูป QR, ชื่อร้าน (`merchant_name`), ยอดแบบ `799.00 THB`, นับถอยหลัง mm:ss และเวลาหมดอายุ, ปุ่มบันทึก QR, ขั้นตอนการชำระ และคำเตือนมิจฉาชีพภาษาไทยและอังกฤษ

### POST `/orders/{id}/payment/card` และ POST `/guest/orders/{guest_token}/payment/card`

ตัดบัตรด้วย token จาก Omise.js ห้ามส่งเลขบัตรมาที่ API นี้

| ฟิลด์ | บังคับ | หมายเหตุ |
|------|--------|----------|
| token | ใช่ | `tokn_...` จาก `Omise.createToken('card', ...)` |

```ts
Omise.setPublicKey(methods.omise_public_key)
Omise.createToken('card', {
  name, number, expiration_month, expiration_year, security_code,
}, async (status, response) => {
  if (status !== 200) return showError(response.message)
  const { data } = await api(`/orders/${orderId}/payment/card`, {
    method: 'POST',
    body: { token: response.id },
  })
  if (data.payment.authorize_uri) window.location.href = data.payment.authorize_uri
  else if (data.paid) goToSuccess()
  else showError(data.payment.failure_message)
})
```

ตอบกลับรูปแบบเดียวกับ QR ผลมีสามแบบ

| ผล | ความหมาย |
|----|----------|
| `paid: true` | ตัดบัตรสำเร็จ |
| `payment.authorize_uri` มีค่า | ต้องยืนยัน 3-D Secure พาลูกค้าไปที่ลิงก์นี้ (หน้าธนาคาร) เมื่อเสร็จธนาคารพากลับมาที่หน้าออเดอร์ของ Nuxt ตรง `{STUDENT_APP_URL}/orders/{order_id}?payment_return=card` (ออเดอร์แบบไม่สมัครสมาชิกมี `guest_token` ด้วย) หน้านั้นเรียก `GET .../payment/status` เพื่อตรวจผลกับ Omise |
| `payment.status: failed` | ไม่ผ่าน แสดง `payment.failure_message` ให้ลองใหม่ |

เมื่อ `test_mode.credit_card` เป็นจริง API นี้รับเฉพาะ token จำลอง `tokn_fake_success`, `tokn_fake_fail`, `tokn_fake_3ds` ส่งค่าอื่นจะตอบ `failed` เมื่อเป็นเท็จ ต้องใช้ token จริงจาก Omise.js `failure_message` ของบัตรเป็นภาษาไทยสำหรับรหัสที่พบบ่อย เช่น `insufficient_fund` = "วงเงินในบัตรไม่พอ กรุณาใช้บัตรอื่น"

### GET `/orders/{id}/payment/status` และ GET `/guest/orders/{guest_token}/payment/status`

ถามสถานะรายการชำระล่าสุดของออเดอร์ ถ้ายังรอชำระ ระบบจะเช็กกับ KGP หรือ Omise ให้ด้วย ตอบกลับรูปแบบเดียวกับข้างบน `qr_image` และ `authorize_uri` เป็น `null` เมื่อรายการไม่ได้รออยู่แล้ว ถ้ายังไม่เคยเริ่มชำระ `payment` เป็นรายการโอนเงินที่สร้างตอนสั่งซื้อหรือ `null`

### POST `/orders/{id}/payment/simulate` และ POST `/guest/orders/{guest_token}/payment/simulate`

ใช้ได้เฉพาะเมื่อมีวิธีชำระที่อยู่ในโหมดทดสอบ (`test_mode`) จำลองผลของรายการล่าสุดที่ยังรอชำระและ `provider` เป็น `fake` โหมด live ตอบ `404` จำกัด 20 ครั้งต่อนาที

```json
{ "state": "paid" }
```

`state` เป็น `paid` หรือ `failed` ตอบกลับรูปแบบเดียวกับ `status` ถ้า `paid` ระบบเปิดสิทธิ์เรียนเหมือนชำระจริง

## ลำดับที่แนะนำใน Nuxt

1. เปิดร้านด้วย `GET /catalog` และ `GET /books` โดยยังไม่ล็อกอิน การ์ดที่ `price_plans` มีหลายรายการแสดงเป็น "เริ่มต้น ฿..."
2. ให้สมัครหรือเข้าสู่ระบบ แล้วเก็บ token หรือถ้าลูกค้าไม่อยากสมัคร ใช้ [สั่งซื้อโดยไม่ต้องสมัครสมาชิก](#สั่งซื้อโดยไม่ต้องสมัครสมาชิก-guest) (`POST /guest/orders` แล้วเปิดหน้าออเดอร์ของ Nuxt ที่ใช้ `guest_token`)
3. หน้ารายละเอียดให้เลือกแพ็กเกจจาก `price_plans` แล้วเก็บ `plan_id` ไว้กับรายการในตะกร้า หน้าตะกร้าเรียก `POST /checkout/quote` ทุกครั้งที่รายการ แพ็กเกจ หรือคูปองเปลี่ยน
4. ถ้า `requires_shipping` เป็นจริง ให้แสดงฟอร์มที่อยู่
5. `POST /orders` แล้วให้เลือกวิธีชำระ
6. ชำระในหน้าออเดอร์ของ Nuxt: QR `POST .../payment/thai-qr` แล้วถาม `GET .../payment/status` ทุก 4 วินาที / บัตร สร้าง token ด้วย Omise.js แล้ว `POST .../payment/card` / โอนเงิน `POST /orders/{id}/payment` พร้อมสลิป
7. หน้าโปรไฟล์และประวัติใช้ `GET /profile` กับ `GET /orders`
8. ตอนดูวิดีโอในวิชา เรียก `PUT /courses/{slug}/videos/{video}/progress` เป็นระยะและตอนหยุด เพื่อเปิดต่อจาก `position_seconds` ได้ ถ้า `video_url` หมดอายุ (4 ชั่วโมง) หรือตัวเล่นแจ้ง error ให้เรียก `GET /courses/{slug}` ใหม่
