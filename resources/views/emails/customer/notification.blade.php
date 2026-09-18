@component($layout, get_defined_vars())
    <h1 style="margin:0 0 12px;font-size:22px;color:#0f766e;">{{ $heading ?? $mailSubject }}</h1>
    <div style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#334155;">
        {!! nl2br(e($body)) !!}
    </div>

    @if (! empty($actionUrl))
        <p style="margin:0;text-align:center;">
            <a href="{{ $actionUrl }}"
                style="display:inline-block;background:{{ config('cct_mail.customer.primary_color') }};color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:600;">
                {{ $actionText ?? 'ดูรายละเอียด' }}
            </a>
        </p>
    @endif
@endcomponent
