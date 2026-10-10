@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:22px;color:#0f766e;">ยืนยันอีเมล</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        กดปุ่มด้านล่างเพื่อยืนยันบัญชี หลังยืนยันแล้วจึงเข้าสู่ระบบได้
    </p>

    <p style="margin:0 0 20px;text-align:center;">
        <a href="{{ $actionUrl }}"
            style="display:inline-block;background:{{ config('cct_mail.customer.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:600;">
            {{ $actionText ?? 'ยืนยันอีเมล' }}
        </a>
    </p>

    <p style="margin:0 0 12px;font-size:13px;line-height:1.7;color:#64748b;">
        ลิงก์นี้จะหมดอายุใน {{ $expiresMinutes ?? 60 }} นาที หากปุ่มกดไม่ได้ ให้คัดลอกลิงก์นี้ไปเปิดในเบราว์เซอร์
    </p>
    <p style="margin:0;font-size:12px;line-height:1.7;color:#64748b;word-break:break-all;">
        {{ $actionUrl }}
    </p>
@endcomponent
