@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:20px;color:#111827;">ข้อมูลเข้าใช้งานระบบ</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#374151;">
        บัญชีพนักงานของคุณถูกสร้างหรืออัปเดตรหัสผ่านแล้ว สามารถเข้าสู่ระบบด้วยข้อมูลด้านล่าง
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;">
        <tr>
            <td style="padding:16px 18px;font-size:14px;line-height:1.8;color:#374151;">
                <div><strong>อีเมล:</strong> {{ $email }}</div>
                <div><strong>รหัสผ่านชั่วคราว:</strong> {{ $password }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#6b7280;">
        แนะนำให้เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก และห้ามแชร์รหัสผ่านกับผู้อื่น
    </p>

    @if (! empty($actionUrl))
        <p style="margin:0;">
            <a href="{{ $actionUrl }}"
                style="display:inline-block;background:{{ config('cct_mail.staff.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:600;">
                {{ $actionText ?? 'เข้าสู่ระบบ' }}
            </a>
        </p>
    @endif
@endcomponent
