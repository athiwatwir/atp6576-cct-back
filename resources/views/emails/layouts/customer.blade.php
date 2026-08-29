<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject ?? $appName }}</title>
</head>
<body style="margin:0;padding:0;background:{{ config('cct_mail.customer.bg_color') }};font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:{{ config('cct_mail.customer.bg_color') }};padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #ccfbf1;">
                    <tr>
                        <td style="background:{{ config('cct_mail.customer.primary_color') }};padding:24px 28px;text-align:center;">
                            <div style="font-size:22px;font-weight:700;color:#ffffff;letter-spacing:0.3px;">
                                {{ config('cct_mail.customer.header_title') }}
                            </div>
                            <div style="margin-top:6px;font-size:13px;color:#ccfbf1;">เรียนรู้ออนไลน์ได้ทุกที่</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            @if (! empty($recipientName))
                                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">สวัสดีคุณ {{ $recipientName }},</p>
                            @endif

                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                                {{ config('cct_mail.customer.footer_note') }}
                            </p>
                            <p style="margin:8px 0 0;font-size:12px;color:#94a3b8;">
                                &copy; {{ date('Y') }} {{ $appName }} · {{ $supportEmail }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
