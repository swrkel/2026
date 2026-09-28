<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="erp-skip-global-document-chrome" content="1">
    <title>Purchase Entry - Print Preview</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef2f6;
            color: #1f2937;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-btn {
            display: block;
            margin: 18px auto 10px;
            padding: 10px 22px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
        }

        .print-btn:hover,
        .print-btn:focus {
            background: #0056b3;
            outline: none;
        }

        /*
         * Screen preview represents one full A4 sheet.
         * Printing uses the same 210mm x 297mm box and the same 10mm padding,
         * so preview and printed output share the exact same content geometry.
         */
        .container {
            width: calc(100% - 24px);
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto 24px;
            padding: 10mm;
            background: #fff;
            box-shadow: 0 0 5mm rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
        }

        .header h2 {
            margin: 0;
            font-size: 24px;
            line-height: 1.2;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .header .business-location {
            margin: 4px 0 0;
            font-size: 14px;
            line-height: 1.35;
            font-weight: 700;
            color: #475569;
            overflow-wrap: anywhere;
        }

        .header h3 {
            margin: 8px 0 4px;
            font-size: 22px;
            line-height: 1.2;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            font-size: 13px;
            line-height: 1.35;
            font-style: italic;
            color: #64748b;
        }

        .info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 9px;
        }

        .info-main {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .status {
            flex: 0 0 auto;
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 7px;
            text-align: right;
        }

        .status span {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 11px;
            border-radius: 5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .details {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 22px;
            font-size: 13px;
            margin-top: 16px;
        }

        .details > div {
            min-width: 0;
        }

        .section-title {
            font-weight: 700;
            border-bottom: 2px solid #007bff;
            margin-bottom: 9px;
            padding-bottom: 5px;
            font-size: 15px;
        }

        .detail-list {
            display: grid;
            grid-template-columns: minmax(92px, 40%) minmax(0, 60%);
            row-gap: 6px;
            column-gap: 10px;
            margin: 0;
        }

        .detail-label {
            color: #64748b;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 700;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .product-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 11.5px;
            page-break-inside: auto;
        }

        .product-table thead {
            display: table-header-group;
        }

        .product-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .product-table th,
        .product-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 6px;
            text-align: center;
            vertical-align: middle;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .product-table th {
            background: #f1f3f5;
            font-size: 10.5px;
            line-height: 1.25;
            font-weight: 700;
        }

        .product-table td.product-name {
            text-align: left;
            font-weight: 700;
        }

        .product-table td.number {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .summary-wrap {
            width: 48%;
            min-width: 310px;
            margin: 16px 0 0 auto;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .summary-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
        }

        .summary-table td:first-child {
            width: 54%;
            font-weight: 600;
            text-align: left;
        }

        .summary-table td:last-child {
            width: 46%;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .summary-table .grand-total td {
            background: #e5e7eb;
            font-size: 14px;
            font-weight: 800;
        }

        .notes {
            margin-top: 18px;
            padding-top: 9px;
            border-top: 1px solid #d7dee7;
            font-size: 12px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 42px;
            margin-top: 52px;
            font-size: 12px;
        }

        .signatures div {
            text-align: center;
            border-top: 1px solid #64748b;
            padding-top: 6px;
        }

        .footer {
            margin-top: 28px;
            font-size: 10.5px;
            line-height: 1.45;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
        }

        .page-no {
            white-space: nowrap;
            text-align: right;
            font-weight: 700;
            color: #334155;
        }

        @media screen and (max-width: 760px) {
            .container {
                width: calc(100% - 12px);
                padding: 18px;
            }

            .info,
            .footer {
                flex-direction: column;
            }

            .status {
                justify-content: flex-start;
            }

            .details {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .summary-wrap {
                width: 100%;
                min-width: 0;
            }

            .product-table {
                font-size: 10.5px;
            }

            .product-table th,
            .product-table td {
                padding: 6px 4px;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .container {
                width: 210mm;
                max-width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
@php
    $money = static fn ($value) => ($currency_symbol ? $currency_symbol.' ' : '').number_format((float) $value, $currency_precision);
    $qty = static fn ($value) => number_format((float) $value, $quantity_precision);
    $dateTime = static function ($value) {
        try {
            return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $dateOnly = static function ($value) {
        try {
            return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $businessName = trim((string) (session('business.name') ?: config('app.name')));
    $locationName = trim((string) ($purchase->location_name ?? '')) ?: '-';
@endphp

<button class="print-btn no-print" type="button" onclick="window.print()">Print Document</button>

<div class="container">
    <div class="header">
        <h2>{{ $businessName }}</h2>
        <div class="business-location">{{ $locationName }}</div>
        <h3>Purchase Entry</h3>
    </div>

    <div class="info">
        <div class="info-main">
            <strong>Purchase No.:</strong> {{ $purchase->invoice_no ?: ('PUR-'.$purchase->id) }}
            &nbsp;|&nbsp;
            <strong>Supplier Ref.:</strong> {{ $purchase->ref_no ?: '-' }}
        </div>
        <div class="status">
            <span>{{ ucfirst((string) ($purchase->status ?: 'Unknown')) }}</span>
            <span>{{ ucfirst((string) ($purchase->payment_status ?: 'Due')) }}</span>
        </div>
    </div>

    <div class="info">
        <div class="info-main"><strong>Printed:</strong> {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="details">
        <div>
            <div class="section-title">Purchase Details</div>
            <div class="detail-list">
                <div class="detail-label">Received Date:</div>
                <div class="detail-value">{{ $dateTime($purchase->transaction_date ?? null) }}</div>
                <div class="detail-label">Invoice Date:</div>
                <div class="detail-value">{{ $dateOnly($purchase->invoice_date ?? null) }}</div>
                <div class="detail-label">Location:</div>
                <div class="detail-value">{{ $purchase->location_name ?: '-' }}</div>
                <div class="detail-label">Store:</div>
                <div class="detail-value">{{ $purchase->store_name ?: '-' }}</div>
                <div class="detail-label">Order No.:</div>
                <div class="detail-value">{{ $purchase->order_no ?: '-' }}</div>
            </div>
        </div>

        <div>
            <div class="section-title">Supplier Details</div>
            <div class="detail-list">
                <div class="detail-label">Supplier:</div>
                <div class="detail-value">{{ $purchase->supplier_name ?: '-' }}</div>
                <div class="detail-label">Code:</div>
                <div class="detail-value">{{ $purchase->supplier_code ?: '-' }}</div>
                <div class="detail-label">Mobile:</div>
                <div class="detail-value">{{ $purchase->supplier_mobile ?: '-' }}</div>
                <div class="detail-label">Tax Number:</div>
                <div class="detail-value">{{ $purchase->supplier_tax_number ?: '-' }}</div>
            </div>
        </div>
    </div>

    <table class="product-table">
        <colgroup>
            <col style="width: 4%">
            <col style="width: 23%">
            <col style="width: 10%">
            <col style="width: 9%">
            <col style="width: 7%">
            <col style="width: 8%">
            <col style="width: 12%">
            <col style="width: 12%">
            <col style="width: 15%">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>PRODUCT / VARIATION</th>
                <th>SKU</th>
                <th>QTY</th>
                <th>FREE</th>
                <th>UNIT</th>
                <th>UNIT COST</th>
                <th>TAX</th>
                <th>LINE TOTAL</th>
            </tr>
        </thead>
        <tbody>
        @forelse($lines as $index => $line)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="product-name">
                    {{ $line->product_name ?: 'Product #'.$line->product_id }}{{ $line->variation_name ? ' / '.$line->variation_name : '' }}
                </td>
                <td>{{ $line->sku ?: '-' }}</td>
                <td class="number">{{ $qty($line->display_quantity) }}</td>
                <td class="number">{{ $qty($line->display_bonus_quantity) }}</td>
                <td>{{ $line->display_unit }}</td>
                <td class="number">{{ $money((float) ($line->pp_without_discount ?? 0) * max(0.000001, (float) ($line->sub_unit_multiplier ?: 1))) }}</td>
                <td class="number">{{ $money($line->line_tax) }}</td>
                <td class="number"><strong>{{ $money($line->line_total) }}</strong></td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No product lines.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary-table">
            <tbody>
                <tr><td>Subtotal before tax</td><td>{{ $money($purchase->total_before_tax ?? 0) }}</td></tr>
                <tr><td>Product tax</td><td>{{ $money($line_tax_total) }}</td></tr>
                <tr><td>Discount</td><td>{{ $money($purchase->discount_amount ?? 0) }}</td></tr>
                <tr><td>Additional tax</td><td>{{ $money($purchase->tax_amount ?? 0) }}</td></tr>
                <tr><td>Shipping</td><td>{{ $money($purchase->shipping_charges ?? 0) }}</td></tr>
                <tr><td>Adjustment</td><td>{{ $money($purchase->price_adjustment ?? 0) }}</td></tr>
                <tr class="grand-total"><td>Purchase Total</td><td>{{ $money($purchase->final_total ?? 0) }}</td></tr>
                <tr><td>Paid</td><td>{{ $money($paid_total) }}</td></tr>
                <tr><td>Due</td><td>{{ $money($due_total) }}</td></tr>
            </tbody>
        </table>
    </div>

    @if(!empty($purchase->shipping_details) || !empty($purchase->additional_notes))
        <div class="notes">
            <strong>Notes:</strong><br>
            {{ $purchase->shipping_details }}{{ $purchase->shipping_details && $purchase->additional_notes ? "\n" : '' }}{{ $purchase->additional_notes }}
        </div>
    @endif

    <div class="signatures">
        <div>Prepared By</div>
        <div>Checked By</div>
        <div>Authorized By</div>
    </div>

    <div class="footer">
        <div>
            This Software is developed by SYZYGY Technologies.<br>
            Contact: 077 4055 434 / 071 1616 192
        </div>
        <div class="page-no">Page No: 1</div>
    </div>
</div>
</body>
</html>
