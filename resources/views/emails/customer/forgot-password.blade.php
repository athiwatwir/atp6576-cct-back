@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:22px;color:#0f766e;">รีเซ็ตรหัสผ่าน</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ กดปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่
    </p>

    <p style="margin:0 0 20px;text-align:center;">
        <a href="{{ $actionUrl }}"
            style="display:inline-block;background:{{ config('cct_mail.customer.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:600;">
            {{ $actionText ?? 'ตั้งรหัสผ่านใหม่' }}
        </a>
    </p>

    <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
        ลิงก์นี้จะหมดอายุใน {{ $expiresMinutes ?? 60 }} นาที หากคุณไม่ได้เป็นผู้ร้องขอ สามารถเพิกเฉยอีเมลนี้ได้
    </p>
@endcomponent
