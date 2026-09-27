# Page Loading (Global)

ระบบ loading กลางของโปรเจคอยู่ที่ `resources/js/loading.js`  
ใช้แสดง overlay เมื่อ submit form หรือคลิกลิงก์นำทาง และเรียกใช้เองจากที่ไหนก็ได้ในระบบ

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|--------|
| `resources/js/loading.js` | **จุดตั้งค่าเดียว** + Alpine store + auto-bind |
| `resources/js/app.js` | ลงทะเบียนผ่าน `registerPageLoading(Alpine)` |
| `resources/views/components/common/page-loading.blade.php` | UI overlay |
| `resources/views/layouts/app.blade.php` | include overlay |
| `resources/views/layouts/fullscreen-layout.blade.php` | include overlay |

## การตั้งค่า (จุดเดียว)

แก้ใน `resources/js/loading.js` → `loadingConfig`

```js
export const loadingConfig = {
    enabled: true,          // เปิด/ปิดทั้งระบบ
    forms: true,            // แสดงตอน submit form
    links: true,            // แสดงตอนคลิกลิงก์ในระบบ
    message: 'กำลังโหลด...',
    formMessage: 'กำลังบันทึก...',
    linkMessage: 'กำลังโหลดหน้า...',
    skipSelector: '[data-no-loading]',
};
```

## พฤติกรรมอัตโนมัติ

- **Form submit** → แสดง `formMessage` (เช่น “กำลังบันทึก...”)
- **ลิงก์ same-origin** → แสดง `linkMessage` (เช่น “กำลังโหลดหน้า...”)
- ไม่ทำงานกับลิงก์ `#`, `javascript:`, `target="_blank"`, `download`, หรือกดพร้อม Ctrl/Cmd/Shift
- Form/ลิงก์ที่ `preventDefault` (AJAX) จะไม่ค้าง loading ค้างจอ

## ยกเว้น (opt-out)

ใส่ `data-no-loading` ที่ form, ลิงก์, หรือ parent

```html
<form data-no-loading @submit="uploadVideo($event)" ...>
```

ข้อความเฉพาะจุด (ถ้ายังให้แสดง loading):

```html
<form data-loading-message="กำลังอัปโหลดไฟล์...">
<a href="..." data-loading-message="กำลังเปิดหน้าแก้ไข...">
```

## เรียกใช้เอง

### JavaScript

```js
PageLoading.show('กำลังบันทึก...')
PageLoading.hide()
PageLoading.toggle(true, 'กำลังโหลด...')
```

### Alpine.js

```html
<button type="button" @click="$store.loading.show('กำลังประมวลผล...')">
<button type="button" @click="$store.loading.hide()">
```

## ตัวอย่างเคส

| เคส | วิธีใช้ |
|-----|---------|
| ฟอร์มบันทึกปกติ (POST/PUT/DELETE) | ไม่ต้องทำอะไร — ทำงานอัตโนมัติ |
| ลิงก์ไปหน้าอื่นในระบบ | ไม่ต้องทำอะไร — ทำงานอัตโนมัติ |
| อัปโหลดวิดีโอมี progress เอง | ใส่ `data-no-loading` ที่ form |
| Fetch/XHR เอง | เรียก `PageLoading.show()` ก่อน และ `.hide()` เมื่อจบ |

## หมายเหตุ

- Overlay ใช้ `Alpine.store('loading')` (`active`, `message`)
- เมื่อกลับหน้าจาก bfcache จะ `hide()` ให้เองผ่าน event `pageshow`
- สไตล์ overlay อยู่ใน `x-common.page-loading` ใช้สี `brand` ตามธีม
