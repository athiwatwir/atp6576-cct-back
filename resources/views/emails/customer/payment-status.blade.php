@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:22px;color:#0f766e;">สถานะการชำระเงิน</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
        {{ $body ?? 'มีการอัปเดตสถานะการชำระเงินของคุณ' }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
        <tr>
            <td style="padding:16px 18px;font-size:14px;line-height:1.9;color:#334155;">
                @if (! empty($orderNo))
                    <div><strong>เลขที่คำสั่งซื้อ:</strong> {{ $orderNo }}</div>
                @endif
                @if (! empty($amount))
                    <div><strong>ยอดชำระ:</strong> {{ $amount }}</div>
                @endif
                <div><strong>สถานะ:</strong> {{ $statusLabel ?? $status }}</div>
            </td>
        </tr>
    </table>

    @if (! empty($actionUrl))
        <p style="margin:0;text-align:center;">
            <a href="{{ $actionUrl }}"
                style="display:inline-block;background:{{ config('cct_mail.customer.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:600;">
                {{ $actionText ?? 'ดูรายละเอียดคำสั่งซื้อ' }}
            </a>
        </p>
    @endif
@endcomponent
