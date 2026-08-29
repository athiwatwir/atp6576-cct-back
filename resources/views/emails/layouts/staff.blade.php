<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject ?? $appName }}</title>
</head>
<body style="margin:0;padding:0;background:{{ config('cct_mail.staff.bg_color') }};font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:{{ config('cct_mail.staff.bg_color') }};padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:{{ config('cct_mail.staff.primary_color') }};padding:20px 28px;">
                            <div style="font-size:18px;font-weight:700;color:#ffffff;">{{ $appName }}</div>
                            <div style="margin-top:4px;font-size:13px;color:#e0e7ff;">{{ config('cct_mail.staff.header_title') }}</div>
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
                        <td style="padding:18px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">
                                {{ config('cct_mail.staff.footer_note') }}
                            </p>
                            <p style="margin:8px 0 0;font-size:12px;color:#9ca3af;">
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
