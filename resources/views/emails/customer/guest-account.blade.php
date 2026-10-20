@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:22px;color:#0f766e;">ยินดีต้อนรับสู่ {{ $appName }}</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        ระบบสร้างบัญชีให้คุณจากการสั่งซื้อครั้งนี้แล้ว ใช้ข้อมูลด้านล่างเข้าสู่ระบบเพื่อดูคำสั่งซื้อและเข้าเรียนหลังชำระเงิน
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:12px;">
        <tr>
            <td style="padding:16px 18px;font-size:14px;line-height:1.8;color:#115e59;">
                <div><strong>อีเมล:</strong> {{ $email }}</div>
                <div><strong>รหัสผ่าน:</strong> {{ $password }}</div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
        <tr>
            <td style="padding:16px 18px;font-size:14px;line-height:1.9;color:#334155;">
                <div><strong>เลขที่คำสั่งซื้อ:</strong> {{ $orderNo }}</div>
                <div><strong>ยอดคำสั่งซื้อ:</strong> {{ $amount }}</div>
                @if (! empty($items))
                    <div style="margin-top:8px;"><strong>รายการ:</strong></div>
                    <ul style="margin:6px 0 0;padding-left:18px;">
                        @foreach ($items as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#64748b;">
        เพื่อความปลอดภัย แนะนำให้เปลี่ยนรหัสผ่านหลังเข้าสู่ระบบ ถ้าคุณไม่ได้สั่งซื้อรายการนี้ ไม่ต้องชำระเงิน และแจ้งแอดมินได้
    </p>

    @if (! empty($actionUrl))
        <p style="margin:0;text-align:center;">
            <a href="{{ $actionUrl }}"
                style="display:inline-block;background:{{ config('cct_mail.customer.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:600;">
                {{ $actionText ?? 'เข้าสู่ระบบ' }}
            </a>
        </p>
    @endif
@endcomponent
