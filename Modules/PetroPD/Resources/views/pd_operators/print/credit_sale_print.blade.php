<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>&nbsp;</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 210mm;
            min-height: 297mm;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
        }

        .print-page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm 12mm 9mm;
            display: flex;
            flex-direction: column;
            page-break-after: always;
            overflow: hidden;
        }

        .print-page:last-child {
            page-break-after: auto;
        }

        .business-header {
            flex: 0 0 auto;
            text-align: center;
            padding-bottom: 5mm;
            border-bottom: 1px solid #222;
        }

        .business-name {
            margin: 0 0 2mm;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }

        .business-meta {
            margin: 1mm 0;
            font-size: 12px;
            line-height: 1.35;
        }

        .bill-positioner {
            flex: 1 1 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 0;
            padding: 6mm 0;
        }

        .receipt-card {
            width: 92mm;
            max-width: 100%;
            border: 1px solid #222;
            padding: 5mm;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 1.35;
        }

        .copy-label {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 3mm;
        }

        .bill-title {
            text-align: center;
            margin: 0 0 4mm;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm 5mm;
            margin-bottom: 4mm;
        }

        .info-item {
            overflow-wrap: anywhere;
        }

        .info-item.right {
            text-align: right;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            padding: 2.2mm 1mm;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .items-table th {
            border-top: 1px dashed #111;
            border-bottom: 1px dashed #111;
            font-weight: 700;
        }

        .items-table .number-col {
            width: 7mm;
        }

        .items-table .qty-col {
            width: 15mm;
        }

        .items-table .price-col,
        .items-table .total-col {
            width: 21mm;
        }

        .text-right {
            text-align: right;
        }

        .totals-section {
            margin-top: 3mm;
            padding-top: 3mm;
            border-top: 1px dashed #111;
            text-align: right;
            font-size: 13px;
            font-weight: 700;
        }

        .signature-section {
            margin-top: 12mm;
            width: 45mm;
            text-align: center;
        }

        .signature-line {
            border-top: 1px dashed #111;
            margin-bottom: 2mm;
        }

        @media screen {
            body {
                background: #e9edf2;
            }

            .print-page {
                margin: 8mm auto;
                background: #fff;
                box-shadow: 0 2px 14px rgba(0, 0, 0, .14);
            }
        }

        @media print {
            html,
            body {
                background: #fff !important;
            }

            .print-page {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    @php
        $copyMode = $copy_mode ?? request()->get('copy', 'customer');
        $receiptCopies = $copyMode === 'customer'
            ? ['Customer Copy']
            : ($copyMode === 'merchant' ? ['Merchant Copy'] : ['Customer Copy', 'Merchant Copy']);

        $locationParts = array_values(array_filter([
            $receipt_details->location_name ?? null,
            $receipt_details->address ?? null,
            $receipt_details->city ?? null,
        ]));
        $locationText = implode(', ', array_unique($locationParts));
        $mobileText = $receipt_details->contact ?? ($business_details->mobile ?? '');
    @endphp

    @foreach ($receiptCopies as $copyLabel)
        <section class="print-page">
            <header class="business-header">
                <h1 class="business-name">{{ $business_details->name ?? '' }}</h1>
                @if($locationText !== '')
                    <p class="business-meta">{{ $locationText }}</p>
                @endif
                @if(!empty($mobileText))
                    <p class="business-meta">Mobile: {{ $mobileText }}</p>
                @endif
            </header>

            <div class="bill-positioner">
                <article class="receipt-card">
                    <div class="copy-label">{{ $copyLabel }}</div>
                    <h2 class="bill-title">Credit Sale Bill</h2>

                    <div class="info-grid">
                        <div class="info-item">Order No: {{ $order_number ?? '' }}</div>
                        <div class="info-item right">Bill No: {{ $bill_number ?? '' }}</div>
                        <div class="info-item">Customer: {{ $customer_name ?? ($receipt_details->customer_name ?? '') }}</div>
                        <div class="info-item right">Vehicle No: {{ $receipt_details->customer_reference ?? '' }}</div>
                    </div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="number-col">#</th>
                                <th>Product</th>
                                <th class="qty-col text-right">Qty</th>
                                <th class="price-col text-right">Unit Price</th>
                                <th class="total-col text-right">Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipt_details->lines as $index => $line)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{ $line['name'] }}
                                        @if(!empty($line['variation']))
                                            <br><small>{{ $line['variation'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $line['quantity'] }}</td>
                                    <td class="text-right">{{ $line['unit_price_inc_tax'] }}</td>
                                    <td class="text-right">{{ $line['line_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="totals-section">
                        Total: {{ $receipt_details->total }}
                    </div>

                    <div class="signature-section">
                        <div class="signature-line"></div>
                        Signature
                    </div>
                </article>
            </div>
        </section>
    @endforeach

    <script>
        window.addEventListener('load', function () {
            document.title = '\u00a0';
            window.print();
            setTimeout(function () {
                window.close();
            }, 1200);
        });
    </script>
</body>
</html>
