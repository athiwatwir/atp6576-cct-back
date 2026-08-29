@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:22px;color:#0f766e;">ยินดีต้อนรับสู่ {{ $appName }}</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        บัญชีของคุณพร้อมใช้งานแล้ว ใช้ข้อมูลด้านล่างเพื่อเข้าสู่ระบบ
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:12px;">
        <tr>
            <td style="padding:16px 18px;font-size:14px;line-height:1.8;color:#115e59;">
                <div><strong>อีเมล:</strong> {{ $email }}</div>
                <div><strong>รหัสผ่าน:</strong> {{ $password }}</div>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#64748b;">
        เพื่อความปลอดภัย แนะนำให้เปลี่ยนรหัสผ่านหลังเข้าสู่ระบบ
    </p>

    @if (! empty($actionUrl))
        <p style="margin:0;text-align:center;">
            <a href="{{ $actionUrl }}"
                style="display:inline-block;background:{{ config('cct_mail.customer.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:600;">
                {{ $actionText ?? 'เริ่มเรียนเลย' }}
            </a>
        </p>
    @endif
@endcomponent
