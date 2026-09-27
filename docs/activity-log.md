# Activity Log (ระบบบันทึกการแก้ไข)

ระบบ audit log กลางของโปรเจค — บันทึกว่า **ใคร** ทำอะไร กับ **ข้อมูลไหน** เมื่อไหร่ จาก **IP / URL** ใด  
ใช้ตาราง `audit_logs` (มีอยู่แล้ว) ขยายคอลัมน์ให้ยืดหยุ่น และเรียกใช้ผ่าน `App\Support\Audit`

## สิ่งที่บันทึกอัตโนมัติ

| ฟิลด์ | ความหมาย |
|-------|----------|
| `user_id` | ผู้ใช้ที่ล็อกอินอยู่ตอนกระทำ (nullable ถ้าระบบทำเอง) |
| `action` | รหัสเหตุการณ์ เช่น `order.updated`, `course.created` |
| `description` | คำอธิบายอ่านง่าย (ภาษาไทยได้) |
| `entity_type` / `entity_id` | โมเดลที่เกี่ยวข้อง (polymorphic) |
| `old_values` / `new_values` | ค่าเดิม / ค่าใหม่ (JSON) |
| `properties` | ข้อมูลเสริมเพิ่มเติม |
| `batch_uuid` | จัดกลุ่มหลาย log ในชุดเดียวกัน |
| `ip_address` | IP ของ request |
| `user_agent` | Browser / client |
| `request_method` / `request_url` | Method + URL |
| `created_at` | เวลาที่บันทึก |

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|--------|
| `app/Support/Audit.php` | API กลางสำหรับเขียน log |
| `app/Models/AuditLog.php` | Eloquent + scopes |
| `app/Models/Concerns/LogsActivity.php` | Trait ให้ Model log อัตโนมัติ |
| `app/Http/Controllers/AuditLogController.php` | หน้าดูรายการ / รายละเอียด |
| `resources/views/pages/audit-logs/*` | UI |
| `database/migrations/2026_09_26_151905_enhance_audit_logs_table.php` | คอลัมน์เสริม |

เมนู: **ภาพรวม → Activity Log** (`/audit-logs`)

## วิธีใช้งาน

### 1) Manual log (ยืดหยุ่นที่สุด)

```php
use App\Support\Audit;

Audit::log(
    action: 'order.shipping_status_updated',
    entity: $order,
    old: ['shipping_status' => 'pending'],
    new: ['shipping_status' => 'shipped'],
    description: 'อัปเดตสถานะจัดส่งเป็น กำลังจัดส่ง',
    properties: ['source' => 'admin_modal'],
);

// ไม่มี model
Audit::event('auth.login', 'เข้าสู่ระบบสำเร็จ', ['guard' => 'web']);

// สั้น ๆ สำหรับ CRUD
Audit::created($course);
Audit::updated($course, ['status' => 'draft'], ['status' => 'published']);
Audit::deleted($course);
```

### 2) Auto-log บน Model (แนะนำสำหรับ CRUD ทั่วไป)

```php
use App\Models\Concerns\LogsActivity;

class Course extends Model
{
    use LogsActivity;

    // optional
    public array $auditEvents = ['created', 'updated', 'deleted'];
    public array $auditExclude = ['updated_at', 'description'];
    public array $auditOnly = ['status', 'name']; // ถ้ามี จะ log เฉพาะคีย์เหล่านี้ตอน update
    public array $auditHidden = ['secret_token'];

    public function getAuditLabel(): string
    {
        return 'คอร์ส '.$this->code.' '.$this->name;
    }
}
```

โมเดลที่เปิดใช้แล้ว:

- `Course`, `Order`, `Product`, `Assessment`, `Instructor`, `Subject`, `User`

### 3) ปิด log ชั่วคราว / จัดกลุ่ม batch

```php
Audit::withoutLogging(function () use ($order) {
    $order->update(['notes' => 'internal sync']);
});

Audit::batch(function () use ($course) {
    $course->update([...]);
    // log อื่นใน batch เดียวกันจะได้ batch_uuid เดียวกัน
    Audit::event('course.published', entity: $course);
});
```

## แสดง log บนหน้ารายละเอียด (Component)

ใช้คอมโพเนนต์ร่วมกันได้ทั้งระบบ:

```blade
{{-- ดึง log ของ entity อัตโนมัติ --}}
<x-common.activity-logs :entity="$course" :limit="8" title="ประวัติการแก้ไขคอร์ส" />

{{-- หรือส่ง collection เอง --}}
<x-common.activity-logs :logs="$logs" :show-link="false" />
```

| Prop | ค่าเริ่มต้น | ความหมาย |
|------|-------------|----------|
| `entity` | `null` | Model ที่ต้องการดูประวัติ |
| `logs` | `null` | Collection ที่เตรียมไว้แล้ว (ข้ามการ query) |
| `limit` | `10` | จำนวนรายการสูงสุด |
| `title` | ประวัติการแก้ไข | หัวข้อการ์ด |
| `empty` | ยังไม่มีบันทึกกิจกรรม | ข้อความว่าง |
| `showLink` | `true` | แสดงลิงก์ไปหน้า Activity Log |

ไฟล์: `resources/views/components/common/activity-logs.blade.php`

## Action naming

รูปแบบแนะนำ: `{resource}.{event}`

| ตัวอย่าง | ความหมาย |
|----------|----------|
| `course.created` | สร้างคอร์ส (จาก trait) |
| `order.updated` | แก้้ออเดอร์ (จาก trait) |
| `order.shipping_status_updated` | อัปเดตสถานะจัดส่ง (manual) |
| `order.tracking_updated` | บันทึกพัสดุ (manual) |
| `assessment.question_created` | เพิ่มคำถามในข้อสอบขายแยก |
| `assessment.question_updated` | แก้ไขคำถามในข้อสอบขายแยก |
| `assessment.question_deleted` | ลบคำถามในข้อสอบขายแยก |
| `auth.login` | เข้าสู่ระบบ |

## ดึงประวัติของ entity

```php
use App\Models\AuditLog;

$logs = AuditLog::query()
    ->forEntity($order)
    ->latest('id')
    ->get();

// หรือ
$logs = AuditLog::query()
    ->actionLike('order.%')
    ->where('user_id', auth()->id())
    ->get();
```

## ความปลอดภัย

- ฟิลด์อ่อนไหว (`password`, `remember_token`, …) ถูกตัดออกอัตโนมัติ
- การเขียน log ห่อด้วย try/catch — ถ้า log พังจะไม่ทำให้ business flow ล้ม
- Soft delete ของ model จะถูก log เป็น `*.deleted`

## Migration

```bash
php artisan migrate
```

## ขยายไปหน้าอื่น

1. ติด `LogsActivity` บน Model ที่ต้องการ  
2. หรือเรียก `Audit::log()` / `Audit::event()` ใน Controller สำหรับ action พิเศษ  
3. ไม่ต้องสร้างตารางใหม่ — ใช้ `audit_logs` ร่วมกันทั้งระบบ
