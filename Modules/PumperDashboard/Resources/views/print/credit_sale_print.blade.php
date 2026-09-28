@php
    $printUserAgent = strtolower(request()->header('User-Agent', ''));
    $isAndroidPrint = str_contains($printUserAgent, 'android');
    $isMobilePrint = $isAndroidPrint || str_contains($printUserAgent, 'mobile');
    $copyMode = $copy_mode ?? request()->get('copy', 'customer');
    /*
     | A reprint is labelled so it cannot be mistaken for the original.
     |
     | Reprint passes ?copy=duplicate, and the receipt then reads
     | "Customer Copy - Duplicate". Every other mode is unchanged, so a first
     | print looks exactly as it always has.
     */
    $isDuplicatePrint = $copyMode === 'duplicate' || request()->boolean('duplicate');

    if ($isDuplicatePrint) {
        $receiptCopies = ['Customer Copy - Duplicate'];
    } else {
        $receiptCopies = $copyMode === 'customer'
            ? ['Customer Copy']
            : ($copyMode === 'merchant' ? ['Merchant Copy'] : ['Customer Copy', 'Merchant Copy']);
    }
    $locationName = trim((string) ($location_details->name ?? ($receipt_details->location_name ?? '')));
    $businessAddress = trim((string) ($receipt_details->address ?? ''));
    $businessMobile = trim((string) ($receipt_details->contact ?? ($location_details->mobile ?? '')));
    $creditBillFooter = trim((string) ($bill_footer ?? ($admin_invoice_footer ?? '')));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
        @if($isMobilePrint)
        @page { size: 58mm auto; margin: 0; }
        @else
        /* Zero page margin prevents browser-added date/title/url bands in Chromium print preview. */
        @page { size: A4 portrait; margin: 0; }
        @endif

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; }
        body {
            color: #17263b;
            font-family: Calibri, "Segoe UI", Arial, sans-serif;
            font-variant-numeric: tabular-nums;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .receipt-wrapper { background: #fff; }
        .desktop-business-header { display: none; }
        .receipt-copy { page-break-inside: avoid; break-inside: avoid; }
        .copy-label { font-weight: normal; }
        .header { text-align: center; }
        .info-section { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .info-right { text-align: right; }
        .items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items-table th, .items-table td { vertical-align: top; }
        .items-table th { text-align: left; }
        .text-right { text-align: right !important; }
        .totals-section { text-align: right; font-weight: normal; }
        .print-footer { text-align: center; white-space: pre-line; }

        /* Desktop A4 preview: business details at top, bill copies centered on the page. */
        body.desktop-print {
            width: 210mm;
            height: 296mm;
            margin: 0 auto;
            background: #eef3f8;
            font-size: 13px;
        }

        body.desktop-print .receipt-wrapper {
            width: 210mm;
            height: 296mm;
            margin: 0 auto;
            padding: 7mm 12mm 6mm;
            background: #fff;
        }

        body.desktop-print .desktop-business-header {
            display: block;
            padding-bottom: 4mm;
            border-bottom: 1px solid #cbd8e6;
            text-align: center;
        }

        body.desktop-print .desktop-business-name {
            margin: 0;
            color: #0d47a1;
            font-size: 26px;
            font-weight: normal;
            line-height: 1.18;
        }

        body.desktop-print .desktop-business-location {
            margin-top: 5px;
            color: #26384d;
            font-size: 15px;
            font-weight: normal;
        }

        body.desktop-print .desktop-business-contact {
            margin-top: 4px;
            color: #4c6175;
            font-size: 13px;
        }

        body.desktop-print .receipt-copies {
            min-height: 0;
            display: grid;
            grid-template-columns: repeat({{ count($receiptCopies) > 1 ? 2 : 1 }}, minmax(0, 1fr));
            gap: 12mm;
            align-content: start;
            justify-content: center;
            padding: 4mm 0 0;
        }

        body.desktop-print .receipt-copy {
            width: 100%;
            max-width: {{ count($receiptCopies) > 1 ? '82mm' : '112mm' }};
            margin: 0 auto;
            padding: 5mm;
            border: 1px solid #b9c9d8;
            border-radius: 3mm;
            background: #fff;
            box-shadow: 0 2mm 6mm rgba(29, 56, 82, .10);
        }

        body.desktop-print .copy-label {
            display: inline-block;
            margin-bottom: 5mm;
            padding: 2mm 4mm;
            border-radius: 999px;
            background: #eaf3ff;
            color: #0d47a1;
            font-size: 12px;
        }

        body.desktop-print .receipt-copy .header {
            margin-bottom: 5mm;
        }

        body.desktop-print .receipt-copy .header h3 {
            margin: 0;
            color: #17263b;
            font-size: 21px;
            font-weight: normal;
            text-transform: uppercase;
        }

        body.desktop-print .receipt-copy .mobile-business-details { display: none; }

        body.desktop-print .info-section {
            margin-bottom: 3mm;
            font-size: 12px;
            line-height: 1.35;
        }

        body.desktop-print .items-table {
            margin-top: 5mm;
            font-size: 12px;
        }

        body.desktop-print .items-table th {
            padding: 2.5mm 1.5mm;
            border-top: 1px solid #40566c;
            border-bottom: 1px solid #40566c;
            color: #17263b;
            font-weight: normal;
        }

        body.desktop-print .items-table td {
            padding: 2mm 1.5mm;
            border-bottom: 1px solid #e3e9ef;
        }

        body.desktop-print .items-table th:nth-child(1),
        body.desktop-print .items-table td:nth-child(1) { width: 7%; }
        body.desktop-print .items-table th:nth-child(2),
        body.desktop-print .items-table td:nth-child(2) { width: 35%; }
        body.desktop-print .items-table th:nth-child(3),
        body.desktop-print .items-table td:nth-child(3) { width: 17%; white-space: nowrap; }
        body.desktop-print .items-table th:nth-child(4),
        body.desktop-print .items-table td:nth-child(4) { width: 19%; white-space: nowrap; }
        body.desktop-print .items-table th:nth-child(5),
        body.desktop-print .items-table td:nth-child(5) { width: 22%; white-space: nowrap; }

        body.desktop-print .totals-section {
            margin-top: 4mm;
            padding-top: 3mm;
            border-top: 1px solid #40566c;
            font-size: 15px;
        }

        body.desktop-print .print-footer {
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: 1px dashed #9aabba;
            color: #51677b;
            font-size: 10px;
        }

        body.desktop-print .cut-line { display: none; }

        /* 58mm Bluetooth/mobile printing remains compact and independent. */
        body.mobile-bluetooth-print {
            width: 58mm;
            max-width: 58mm;
            overflow: hidden;
            font-family: monospace;
            font-size: 8px;
        }

        body.mobile-bluetooth-print .receipt-wrapper {
            width: 50mm;
            max-width: 50mm;
            margin: 0 auto;
            padding: 1mm .5mm 0;
            overflow: hidden;
        }

        body.mobile-bluetooth-print .receipt-copy {
            width: 100%;
            padding-bottom: 4px;
        }

        body.mobile-bluetooth-print .copy-label {
            font-size: 9px;
            white-space: nowrap;
        }

        body.mobile-bluetooth-print .header {
            margin-bottom: 6px;
        }

        body.mobile-bluetooth-print .header h3 {
            margin: 0;
            font-size: 11px;
            text-transform: uppercase;
        }

        body.mobile-bluetooth-print .header h2 {
            margin: 3px 0;
            font-size: 8px;
            line-height: 1.15;
        }

        body.mobile-bluetooth-print .header p {
            margin: 2px 0;
            font-size: 8px;
        }

        body.mobile-bluetooth-print .info-section {
            display: table;
            table-layout: fixed;
            width: 100%;
            margin-bottom: 5px;
            font-size: 8px;
        }

        body.mobile-bluetooth-print .info-left,
        body.mobile-bluetooth-print .info-right {
            display: table-cell;
            width: 50%;
            overflow-wrap: anywhere;
        }

        body.mobile-bluetooth-print .items-table {
            font-size: 8px;
        }

        body.mobile-bluetooth-print .items-table th {
            padding: 3px 1px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            line-height: 1.05;
        }

        body.mobile-bluetooth-print .items-table td {
            padding: 2px 1px;
        }

        body.mobile-bluetooth-print .items-table th:nth-child(1),
        body.mobile-bluetooth-print .items-table td:nth-child(1) { width: 5%; }
        body.mobile-bluetooth-print .items-table th:nth-child(2),
        body.mobile-bluetooth-print .items-table td:nth-child(2) { width: 27%; overflow-wrap: anywhere; }
        body.mobile-bluetooth-print .items-table th:nth-child(3),
        body.mobile-bluetooth-print .items-table td:nth-child(3) { width: 17%; white-space: nowrap; }
        body.mobile-bluetooth-print .items-table th:nth-child(4),
        body.mobile-bluetooth-print .items-table td:nth-child(4) { width: 23%; white-space: nowrap; }
        body.mobile-bluetooth-print .items-table th:nth-child(5),
        body.mobile-bluetooth-print .items-table td:nth-child(5) { width: 28%; white-space: nowrap; }

        body.mobile-bluetooth-print .totals-section {
            margin-top: 6px;
            padding-top: 4px;
            padding-right: 1px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }

        body.mobile-bluetooth-print .print-footer {
            margin-top: 5px;
            padding-top: 4px;
            border-top: 1px dashed #000;
            font-size: 8px;
        }

        body.mobile-bluetooth-print .cut-line {
            width: 100%;
            margin: 8px 0;
            border-top: 1px dashed #000;
        }

        @media print {
            html, body { background: #fff !important; }
            body.desktop-print .receipt-copy { box-shadow: none; }
        }
    </style>
</head>
<body class="{{ $isMobilePrint ? 'mobile-bluetooth-print' : 'desktop-print' }}">
    <div class="receipt-wrapper">
        <header class="desktop-business-header">
            <h1 class="desktop-business-name">{{ $business_details->name }}</h1>
            @if($locationName !== '')
                <div class="desktop-business-location">{{ $locationName }}</div>
            @endif
            @if($businessAddress !== '')
                <div class="desktop-business-contact">{!! nl2br(e($businessAddress)) !!}</div>
            @endif
            @if($businessMobile !== '')
                <div class="desktop-business-contact">Mobile: {{ $businessMobile }}</div>
            @endif
        </header>

        <div class="receipt-copies">
            @foreach ($receiptCopies as $copyLabel)
                <article class="receipt-copy">
                    <div class="copy-label">{{ $copyLabel }}</div>
                    <div class="header">
                        <h3>Credit Sale Bill</h3>
                        <div class="mobile-business-details">
                            <h2>{{ $business_details->name }}</h2>
                            @if($locationName !== '')<p>{{ $locationName }}</p>@endif
                            @if($businessAddress !== '')<p>{!! nl2br(e($businessAddress)) !!}</p>@endif
                            @if($businessMobile !== '')<p>Mobile: {{ $businessMobile }}</p>@endif
                        </div>
                    </div>

                    <div class="info-section">
                        <div class="info-left">Order No: {{ $order_number ?? '' }}</div>
                        <div class="info-right">Bill No: {{ $bill_number }}</div>
                    </div>
                    <div class="info-section">
                        <div class="info-left">Customer: {{ $customer_name ?? ($receipt_details->customer_name ?? '') }}</div>
                        <div class="info-right">Vehicle No: {{ $receipt_details->customer_reference ?? '' }}</div>
                    </div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipt_details->lines as $index => $line)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{ $line['name'] }}
                                        @if(!empty($line['variation']))<br><small>{{ $line['variation'] }}</small>@endif
                                    </td>
                                    <td class="text-right">{{ $line['quantity'] }}</td>
                                    <td class="text-right">{{ $line['unit_price_inc_tax'] }}</td>
                                    <td class="text-right">{{ $line['line_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="totals-section">Total: {{ $receipt_details->total }}</div>

                    @if ($creditBillFooter !== '')
                        <div class="print-footer">{!! nl2br(e($creditBillFooter)) !!}</div>
                    @endif

                    @if (!$loop->last)<div class="cut-line"></div>@endif
                </article>
            @endforeach
        </div>
    </div>

    <script>
        (function () {
            var isMobilePrint = @json($isMobilePrint);
            var printStarted = false;

            function startPrint() {
                if (printStarted) return;
                printStarted = true;
                setTimeout(function () {
                    window.focus();
                    window.print();
                }, isMobilePrint ? 500 : 250);
            }

            window.addEventListener('afterprint', function () {
                if (!isMobilePrint) {
                    setTimeout(function () { window.close(); }, 500);
                }
            });

            if (document.readyState === 'complete') startPrint();
            else window.addEventListener('load', startPrint, { once: true });
        })();
    </script>
</body>
</html>
