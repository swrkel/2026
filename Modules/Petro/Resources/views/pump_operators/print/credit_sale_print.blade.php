<!DOCTYPE html>
<html>
<head>
    <title>Credit Sale Bill</title>
    <style>
    /* Adjustments for 80mm thermal printer */
    @page {
        size: 80mm auto;
        margin: 0;
    }

    body {
        font-family: monospace;
        font-size: 10px;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    * {
        box-sizing: border-box;
    }

    .receipt-wrapper {
        width: 80mm;
        max-width: 80mm;
        margin: 0 auto;
        padding: 4px 6px 0;
    }

    .receipt-copy {
        width: 100%;
        margin: 0 auto;
        padding-bottom: 4px;
    }

    .header {
        text-align: center;
        margin-bottom: 6px;
    }

    body,
    .receipt-wrapper,
    .receipt-copy,
    .header h3,
    .header h2,
    .items-table th,
    .totals-section,
    .copy-label,
    .signature-section,
    .print-footer {
        font-weight: normal;
    }

    .header h3 {
        margin: 0;
        font-size: 13px;
        text-transform: uppercase;
    }

    .header h2 {
        margin: 3px 0;
        font-size: 15px;
    }

    .header p {
        margin: 2px 0;
        font-size: 10px;
    }

    .info-section {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .info-left,
    .info-right {
        font-size: 10px;
        width: 50%;
    }

    .info-right {
        text-align: right;
    }

    .info-row {
        margin-bottom: 2px;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }

    .items-table th {
        font-size: 10px;
        padding: 2px;
        border-top: 1px dashed #000;
        border-bottom: 1px dashed #000;
        text-align: left;
        word-wrap: break-word;
        word-break: break-word;
    }

    .items-table td {
        font-size: 9px;
        padding: 2px;
        vertical-align: top;
        word-wrap: break-word;
        word-break: break-word;
    }

    .text-right {
        text-align: right;
    }

    .items-table th:first-child,
    .items-table td:first-child {
        width: 12px;
    }

    tr {
        page-break-inside: avoid;
    }

    .totals-section {
        margin-top: 6px;
        border-top: 1px dashed #000;
        padding-top: 4px;
        text-align: right;
        font-size: 10px;
    }

    .signature-section {
        margin-top: 18px;
        font-size: 9px;
        text-align: center;
    }

    .signature-line {
        border-top: 1px dashed #000;
        width: 120px;
        margin-bottom: 3px;
    }

    .receipt-copy {
        position: relative;
    }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            text-align: left;
            border-top: 1px dotted #000;
            border-bottom: 1px dotted #000;
            padding: 5px 2px;
            font-weight: bold;
        }
        .items-table td {
            text-align: left;
            padding: 5px 2px;
            border-bottom: none;
        }
        .items-table td.text-right, .items-table th.text-right {
            text-align: right;
        }
        .items-table th:first-child, .items-table td:first-child {
            width: 20px;
        }

    .cut-line {
        border-top: 1px dashed #000;
        margin: 8px 0;
        width: 100%;
    }

    .print-footer {
        width: 100%;
        text-align: center;
        font-size: 8px;
        padding: 6px 0;
        margin-top: 12px;
        border-top: 1px dashed #000;
        background: #fff;
    }

    @media print {
        .bill-footer {
            position: static !important;
            display: block;
            margin-top: 6px !important;
        }

        .no-print {
            display: none !important;
        }
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }
    </style>
</head>
<body>
    @php
        $copyMode = $copy_mode ?? request()->get('copy', 'customer');
        $receiptCopies = $copyMode === 'customer' ? ['Customer Copy'] : ($copyMode === 'merchant' ? ['Merchant Copy'] : ['Customer Copy', 'Merchant Copy']);
    @endphp

    <div class="receipt-wrapper">
        @foreach ($receiptCopies as $copyLabel)
            <div class="receipt-copy">
                <div class="copy-label">{{ $copyLabel }}</div>
                <div class="header">
                    <h3>Bill</h3>
                    <h2>{{ $business_details->name }}</h2>

                    @if(!empty($receipt_details->location_name))
                        <p>{{ $receipt_details->address }}</p>
                    @endif

                    @if(!empty($receipt_details->city))
                        <p>{{ $receipt_details->city }}</p>
                    @endif

                    <p>Phone: {{ $receipt_details->contact }}</p>
                </div>
                <div class="info-section">
                    <div class="info-left">
                        <div class="info-row">
                            Order No: {{ $order_number ?? '' }}
                        </div>
                    </div>
                    <div class="info-right">
                        <div class="info-row">
                            Bill No: {{ $bill_number }}
                        </div>
                    </div>
                </div>
                <div class="info-section">
                    <div class="info-left">
                        <div class="info-row">
                            Customer: {{ $customer_name ?? ($receipt_details->customer_name ?? '') }}
                        </div>
                    </div>
                    <div class="info-right">
                        <div class="info-row">
                            Vehicle No: {{ $receipt_details->customer_reference ?? '' }}
                        </div>
                    </div>
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
                                @if(!empty($line['variation'])) <br><small>{{ $line['variation'] }}</small> @endif
                            </td>
                            <td class="text-right">{{ $line['quantity'] }}</td>
                            <td class="text-right">{{ $line['unit_price_inc_tax'] }}</td>
                            <td class="text-right">
    {{ $receipt_details->total }}
</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="totals-section">
                    <div class="total-row">
                        Total: &nbsp;&nbsp;&nbsp; {{ $receipt_details->total }}
                    </div>
                </div>

                <div class="signature-section">
                    <div class="signature-line"></div>
                    <br>
                    Signature
                </div>

                @if (!empty($admin_invoice_footer))
                    <div class="print-footer bill-footer">
                        {!! nl2br(e($admin_invoice_footer)) !!}
                    </div>
                @endif

                @if (!$loop->last)
                    <div class="cut-line"></div>
                @endif
            </div>
        @endforeach
    </div>

    <script type="text/javascript">
        window.onload = function() {
            window.print();
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>
