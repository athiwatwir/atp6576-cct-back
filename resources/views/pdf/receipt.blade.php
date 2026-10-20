@php
/** @var \App\Models\Receipt $receipt */
$money = fn ($n) => number_format((float) $n, 2);
@endphp
<html>
<head>
    <style>
        body {
            font-family: garuda;
            font-size: 11pt;
            color: #1f2937;
        }

        .muted {
            color: #6b7280;
        }

        .small {
            font-size: 9pt;
        }

        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #0f766e;
        }

        .box {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.items th {
            background: #f0fdfa;
            color: #115e59;
            border-bottom: 1px solid #99f6e4;
            padding: 6px 8px;
            font-weight: bold;
        }

        table.items td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 8px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        table.totals td {
            padding: 3px 8px;
        }

        .grand td {
            font-size: 11pt;
            font-weight: bold;
            border-top: 1.5px solid #0f766e;
            padding-top: 6px;
        }

    </style>
</head>
<body>

    <table width="100%">
        <tr>
            <td width="52%" style="vertical-align: top;">
                @if ($company['logo'])
                <img src="{{ $company['logo'] }}" style="height: 52px;" /><br />
                @endif
                <div style="font-size: 12pt; font-weight: bold;">{{ $company['name'] }}</div>
                @if ($company['address'])<div class="small">{{ $company['address'] }}</div>@endif
                <div class="small">
                    @if ($company['tax_id'])เลขประจำตัวผู้เสียภาษี {{ $company['tax_id'] }} @endif
                    @if ($company['phone']) โทร {{ $company['phone'] }} @endif

                </div>
            </td>
            <td width="48%" class="right" style="vertical-align: top;">
                <div class="title">ใบเสร็จรับเงิน</div>
                <div class="muted">RECEIPT</div>
                <table width="100%" style="margin-top: 6px;">
                    <tr>
                        <td class="right muted small" width="38%">เลขที่</td>
                        <td class="right"><b>{{ $receipt->receipt_no }}</b></td>
                    </tr>
                    <tr>
                        <td class="right muted small">วันที่</td>
                        <td class="right">{{ $issuedAtText }}</td>
                    </tr>
                    <tr>
                        <td class="right muted small">อ้างอิงคำสั่งซื้อ</td>
                        <td class="right">{{ $order?->order_no ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="box" style="margin-top: 12px;">
        <div class="muted small">ได้รับเงินจาก</div>
        <div><b>{{ $receipt->customer_name ?: '-' }}</b></div>
        @if ($receipt->customer_address)<div class="small">{{ $receipt->customer_address }}</div>@endif
        <div class="small">
            @if ($receipt->customer_email){{ $receipt->customer_email }}@endif
            @if ($receipt->customer_phone) · โทร {{ $receipt->customer_phone }}@endif
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="center" width="7%">#</th>
                <th style="text-align: left;">รายการ</th>
                <th class="center" width="10%">จำนวน</th>
                <th class="right" width="17%">ราคาต่อหน่วย</th>
                <th class="right" width="17%">จำนวนเงิน</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt->items as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="center">{{ $item['quantity'] }}</td>
                <td class="right">{{ $money($item['unit_price']) }}</td>
                <td class="right">{{ $money($item['total_price']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table width="100%" style="margin-top: 8px;">
        <tr>
            <td width="50%" style="vertical-align: top; padding-right: 8px;">
                <table width="100%" style="border: 1px solid #d1d5db;" cellpadding="6">
                    <tr>
                        <td class="small"><span class="muted">ตัวอักษร</span><br />({{ $amountText }})</td>
                    </tr>
                    <tr>
                        <td class="small"><span class="muted">ชำระโดย</span> {{ $methodLabel ?: '-' }}</td>
                    </tr>
                    @if ($receipt->payment_reference)
                    <tr>
                        <td class="small"><span class="muted">เลขอ้างอิงการชำระ</span><br />{{ $receipt->payment_reference }}</td>
                    </tr>
                    @endif
                </table>
            </td>
            <td width="50%" style="vertical-align: top;">
                <table class="totals" width="100%">
                    <tr>
                        <td class="muted">รวมเป็นเงิน</td>
                        <td class="right" width="38%">{{ $money($receipt->subtotal) }}</td>
                    </tr>
                    @if ((float) $receipt->discount_amount > 0)
                    <tr>
                        <td class="muted">ส่วนลด</td>
                        <td class="right">-{{ $money($receipt->discount_amount) }}</td>
                    </tr>
                    @endif
                    @if ((float) $receipt->shipping_amount > 0)
                    <tr>
                        <td class="muted">ค่าจัดส่ง</td>
                        <td class="right">{{ $money($receipt->shipping_amount) }}</td>
                    </tr>
                    @endif
                    @if ((float) $receipt->fee_amount > 0)
                    <tr>
                        <td class="muted">ค่าธรรมเนียมชำระเงิน</td>
                        <td class="right">{{ $money($receipt->fee_amount) }}</td>
                    </tr>
                    @endif
                    <tr class="grand">
                        <td>ยอดชำระทั้งสิ้น (บาท)</td>
                        <td class="right">{{ $money($receipt->total_amount) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="small muted" style="margin-top: 28px;">{{ $company['note'] }}</div>

</body>
</html>
