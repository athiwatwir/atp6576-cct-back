@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:20px;color:#111827;">{{ $heading ?? $mailSubject }}</h1>
    <div style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#374151;">
        {!! nl2br(e($message)) !!}
    </div>

    @if (! empty($actionUrl))
        <p style="margin:0;">
            <a href="{{ $actionUrl }}"
                style="display:inline-block;background:{{ config('cct_mail.staff.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:600;">
                {{ $actionText ?? 'เปิดดูรายละเอียด' }}
            </a>
        </p>
    @endif
@endcomponent
