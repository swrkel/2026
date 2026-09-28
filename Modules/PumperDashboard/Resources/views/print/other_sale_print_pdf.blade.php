@php
    $displayName = trim((string) (($business_details->name ?? '') ?: ($receipt_details->display_name ?? '')));
    $invoiceNo = trim((string) ($receipt_details->invoice_no ?? ''));
    $invoiceDate = trim((string) ($receipt_details->invoice_date ?? ''));
    $customerName = trim((string) ($receipt_details->customer_name ?? ''));
    $lines = is_iterable($receipt_details->lines ?? null) ? $receipt_details->lines : [];
    $browserPrintFallback = !empty($browser_print_fallback);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Other Sales Invoice</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            color: #26384a;
            background: #fff;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.28;
        }
        .receipt { width: 100%; margin: 0 auto; padding: 8mm 10mm 12mm; page-break-inside: avoid; }
        .business-header {
            margin: 0 0 4mm;
            padding: 0 0 2.5mm;
            border-bottom: .3mm solid #ccd8e4;
            text-align: center;
        }
        .business-name { margin: 0; color: #183d69; font-size: 20pt; font-weight: 800; }
        .receipt-title { margin: 1.2mm 0 0; color: #183d69; font-size: 13pt; font-weight: 800; text-transform: uppercase; }
        .meta { width: 100%; margin: 0 0 3mm; border-collapse: collapse; table-layout: fixed; }
        .meta td { width: 50%; padding: .6mm 0; vertical-align: top; }
        .meta .right { text-align: right; }
        .label { font-weight: 700; color: #31465a; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
        .items th, .items td { border: .2mm solid #ccd8e4; padding: 1.6mm 1.8mm; vertical-align: middle; }
        .items th { background: #164a82; color: #fff; font-size: 8pt; text-transform: uppercase; font-weight: 800; }
        .items td.number { text-align: right; font-variant-numeric: tabular-nums; }
        .items tbody tr:nth-child(even) { background: #f6f9fc; }
        /* IS1814-3: Product was 42%; the requested 50% reduction is 21%. */
        .items th:nth-child(1), .items td:nth-child(1) { width: 21%; }
        .items th:nth-child(2), .items td:nth-child(2) { width: 19%; }
        .items th:nth-child(3), .items td:nth-child(3) { width: 27%; }
        .items th:nth-child(4), .items td:nth-child(4) { width: 33%; }
        .summary-wrap { width: 42%; margin: 3mm 0 0 auto; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary th, .summary td { padding: 1.3mm 1.8mm; border-bottom: .2mm solid #dce5ed; }
        .summary th { text-align: left; font-weight: 700; }
        .summary td { text-align: right; font-variant-numeric: tabular-nums; }
        .summary tr.total th, .summary tr.total td {
            background: #eaf3ff;
            color: #123f70;
            font-size: 10pt;
            font-weight: 900;
            border-top: .3mm solid #bfd2e6;
            border-bottom: .3mm solid #bfd2e6;
        }
        .footer { margin-top: 6mm; padding-top: 2mm; border-top: .2mm solid #dce5ed; color: #657789; text-align: center; font-size: 7pt; }
        .no-data { padding: 5mm !important; text-align: center; color: #758697; }
        .print-toolbar { display: flex; justify-content: flex-end; gap: 8px; width: 100%; max-width: 190mm; margin: 10px auto; }
        .print-toolbar button { border: 0; border-radius: 6px; padding: 10px 18px; color: #fff; font-weight: 700; cursor: pointer; }
        .print-btn { background: #1976d2; }
        .close-btn { background: #607d8b; }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@if($browserPrintFallback)
    <div class="print-toolbar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
        <button type="button" class="close-btn" onclick="window.close()">Close</button>
    </div>
@endif
<main class="receipt">
    <header class="business-header">
        @if($displayName !== '')<h1 class="business-name">{{ $displayName }}</h1>@endif
        <div class="receipt-title">Invoice</div>
    </header>

    <table class="meta">
        <tr>
            <td>
                @if($invoiceNo !== '')<span class="label">Invoice No:</span> {{ $invoiceNo }}@endif
                @if($customerName !== '')<br><span class="label">Customer:</span> {{ $customerName }}@endif
            </td>
            <td class="right">
                @if($invoiceDate !== '')<span class="label">Date:</span> {{ $invoiceDate }}@endif
                <br><span class="label">Payment Method:</span> {{ $payment_method_value }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $line)
                <tr>
                    <td>{{ trim(($line['name'] ?? '') . ' ' . ($line['product_variation'] ?? '') . ' ' . ($line['variation'] ?? '')) }}</td>
                    <td class="number">{{ $line['quantity'] ?? '' }} {{ $line['units'] ?? '' }}</td>
                    <td class="number">{{ $line['unit_price_before_discount'] ?? ($line['unit_price'] ?? '') }}</td>
                    <td class="number">{{ $line['line_total'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="no-data">No other-sale items were found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary">
            @if(!empty($receipt_details->subtotal))
                <tr><th>{{ strip_tags((string) ($receipt_details->subtotal_label ?? 'Subtotal')) }}</th><td>{{ $receipt_details->subtotal }}</td></tr>
            @endif
            @if(!empty($receipt_details->discount))
                <tr><th>{{ strip_tags((string) ($receipt_details->discount_label ?? 'Discount')) }}</th><td>{{ $receipt_details->discount }}</td></tr>
            @endif
            <tr class="total"><th>{{ strip_tags((string) ($receipt_details->total_label ?? 'Total')) }}</th><td>{{ $receipt_details->total ?? ($receipt_details->subtotal ?? '0.00') }}</td></tr>
        </table>
    </div>

    @if(!empty($receipt_details->footer_text) || !empty($receipt_details->admin_invoice_footer))
        <footer class="footer">{!! $receipt_details->footer_text ?? $receipt_details->admin_invoice_footer !!}</footer>
    @endif
</main>
@if($browserPrintFallback)
<script>
    window.addEventListener('load', function () {
        window.setTimeout(function () { window.print(); }, 450);
    });
</script>
@endif
</body>
</html>
