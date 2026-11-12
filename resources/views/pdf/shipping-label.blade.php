@php
/** @var \App\Models\Order $order */
$postalDigits = preg_split('//u', (string) $order->shipping_postal_code, -1, PREG_SPLIT_NO_EMPTY);
@endphp
<html>
<head>
    <style>
        body { font-family: notosansthai; font-size: 8.5pt; color: #111827; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
        .muted { color: #4b5563; }
        .tiny { font-size: 7pt; }
        .tag { font-size: 7pt; font-weight: bold; color: #ffffff; background-color: #111827; padding: 0.3mm 1.5mm; }
        .sender { border: 0.3mm solid #111827; padding: 1.2mm 2mm; }
        .recipient { border: 0.6mm solid #111827; padding: 1.8mm 2.5mm; }
        .name { font-size: 13pt; font-weight: bold; }
        .phone { font-size: 11pt; font-weight: bold; }
        .address { font-size: 10pt; }
        .digit { width: 8mm; height: 9mm; border: 0.4mm solid #111827; text-align: center; vertical-align: middle; font-size: 17pt; font-weight: bold; }
        .cod { background-color: #111827; color: #ffffff; padding: 1.2mm 2.5mm; vertical-align: middle; }
        .items td { font-size: 7.5pt; padding: 0.3mm 0; border-bottom: 0.2mm solid #e5e7eb; }
        .tracking { font-size: 10.5pt; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="sender" style="width: 64%;">
                <span class="tag">ผู้ส่ง FROM</span><br>
                <b>{{ $sender['name'] }}</b>
                @if ($sender['address'])
                    <br><span class="tiny muted">{{ $sender['address'] }}</span>
                @endif
                @if ($sender['phone'])
                    <br><span class="tiny">โทร {{ $sender['phone'] }}</span>
                @endif
            </td>
            <td style="width: 36%; text-align: right; padding-left: 2mm;">
                @if ($sender['logo'])
                    <img src="{{ $sender['logo'] }}" style="height: 8mm;" /><br>
                @endif
                <b>{{ $order->order_no }}</b><br>
                <span class="tiny muted">{{ $order->created_at?->timezone(config('receipt.timezone', 'Asia/Bangkok'))->format('d/m/Y') }}</span>
            </td>
        </tr>
    </table>

    <table style="margin-top: 2mm;">
        <tr>
            <td class="recipient">
                <span class="tag">ผู้รับ TO</span><br>
                <span class="name">{{ $order->shipping_name ?: ($order->user?->name ?? '-') }}</span><br>
                <span class="phone">โทร {{ $order->shipping_phone ?: ($order->user?->phone ?? '-') }}</span><br>
                <span class="address">{{ $recipientAddress ?: '-' }}</span>
                @if ($postalDigits)
                    <table style="margin-top: 1.5mm; width: {{ count($postalDigits) * 9 }}mm;">
                        <tr>
                            @foreach ($postalDigits as $digit)
                                <td class="digit">{{ $digit }}</td>
                            @endforeach
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    @if ($isCod)
        <table style="margin-top: 2mm;">
            <tr>
                <td class="cod" style="font-weight: bold;">เก็บเงินปลายทาง (COD)</td>
                <td class="cod" style="text-align: right; font-size: 14pt; font-weight: bold;">฿{{ number_format($codAmount, 2) }}</td>
            </tr>
        </table>
    @endif

    <table style="margin-top: 2mm; border-top: 0.3mm dashed #6b7280;">
        <tr>
            <td style="width: 55%; padding-top: 1.5mm;">
                <barcode code="{{ $order->order_no }}" type="C128B" size="0.7" height="0.5" />
            </td>
            <td style="width: 45%; padding-top: 1.5mm; text-align: right;">
                @if ($order->tracking_number)
                    <span class="tiny muted">{{ $order->shipping_carrier ?: 'เลขพัสดุ' }}</span><br>
                    <span class="tracking">{{ $order->tracking_number }}</span>
                @else
                    <span class="tiny muted">{{ $isCod ? 'ชำระปลายทาง' : ($order->payment_status === \App\Enums\PaymentStatus::Paid->value ? 'ชำระเงินแล้ว' : 'ยังไม่ชำระเงิน') }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 1.5mm;">
        <tr>
            <td colspan="2" style="font-weight: bold; border-bottom: 0.3mm solid #111827;">รายการสินค้า ({{ number_format($totalQuantity) }} ชิ้น)</td>
        </tr>
        @foreach ($items as $item)
            <tr>
                <td>{{ \Illuminate\Support\Str::limit($item->item_name, 60) }}</td>
                <td style="width: 10mm; text-align: right;">x {{ (int) $item->quantity }}</td>
            </tr>
        @endforeach
        @if ($moreItems > 0)
            <tr><td colspan="2" class="muted">และอีก {{ number_format($moreItems) }} รายการ</td></tr>
        @endif
    </table>
    @if ($order->notes)
        <div class="tiny muted" style="margin-top: 1mm;">หมายเหตุ: {{ \Illuminate\Support\Str::limit($order->notes, 90) }}</div>
    @endif
    <div class="tiny muted" style="margin-top: 0.5mm; text-align: right;">พิมพ์ {{ $printedAt }}</div>
</body>
</html>
