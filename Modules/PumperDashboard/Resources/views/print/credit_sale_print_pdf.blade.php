@php
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
    $creditBillFooter = trim((string) ($bill_footer ?? ($admin_invoice_footer ?? '')));
    $lines = is_iterable($receipt_details->lines ?? null) ? $receipt_details->lines : [];
    $lineCount = is_countable($lines) ? count($lines) : 0;
    $browserPrintFallback = !empty($browser_print_fallback);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Sale Bill</title>
    <style>
        @page { size: A4 portrait; margin: 8mm 10mm 18mm; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            color: #17263b;
            background: #fff;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.25;
        }
        .page { width: 100%; min-height: 255mm; position: relative; }
        .copies-table {
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 8mm 0;
            table-layout: fixed;
            page-break-inside: avoid;
        }
        .copies-table td.copy-cell { width: 50%; padding: 0; vertical-align: top; }
        .single-copy-wrap { width: 92mm; margin: 0 auto; }
        .receipt-card {
            width: 100%;
            min-height: 94mm;
            padding: 5mm;
            border: .35mm solid #aebfce;
            border-radius: 2.5mm;
            background: #fff;
            page-break-inside: avoid;
        }
        .copy-label {
            display: inline-block;
            padding: 1mm 3mm;
            margin: 0 0 4mm;
            border-radius: 8mm;
            background: #eaf3ff;
            color: #0d47a1;
            font-size: 7.5pt;
            font-weight: normal;
        }
        .bill-title {
            margin: 0 0 6mm;
            color: #17263b;
            text-align: center;
            font-size: 14pt;
            line-height: 1;
            font-weight: normal;
            text-transform: uppercase;
        }
        .info-table, .items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .info-table { margin-bottom: 5mm; font-size: 8pt; }
        .info-table td { padding: 1mm 0; vertical-align: top; word-break: break-word; }
        .info-table td:first-child { width: 56%; padding-right: 2mm; }
        .info-table td:last-child { width: 44%; text-align: right; }
        .items-table { font-size: 7.6pt; }
        .items-table th {
            padding: 1.6mm .8mm;
            border-top: .35mm solid #354b60;
            border-bottom: .35mm solid #354b60;
            text-align: left;
            font-weight: normal;
        }
        .items-table td {
            padding: 1.8mm .8mm;
            border-bottom: .2mm solid #d8e0e7;
            vertical-align: top;
            word-break: break-word;
        }
        .items-table th:nth-child(1), .items-table td:nth-child(1) { width: 8%; }
        .items-table th:nth-child(2), .items-table td:nth-child(2) { width: 36%; }
        .items-table th:nth-child(3), .items-table td:nth-child(3) { width: 15%; }
        .items-table th:nth-child(4), .items-table td:nth-child(4) { width: 19%; }
        .items-table th:nth-child(5), .items-table td:nth-child(5) { width: 22%; }
        .text-right { text-align: right !important; white-space: nowrap; }
        .totals {
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: .4mm solid #354b60;
            text-align: right;
            font-size: 10pt;
            font-weight: normal;
        }
        .receipt-footer {
            margin-top: 6mm;
            padding-top: 3mm;
            border-top: .2mm dashed #9aabba;
            color: #5d7184;
            text-align: center;
            font-size: 6.7pt;
            line-height: 1.3;
        }
        .page-footer {
            position: fixed;
            left: 10mm;
            right: 10mm;
            bottom: 3mm;
            padding-top: 2mm;
            border-top: .25mm solid #7d8b98;
            text-align: center;
            color: #4d5d6d;
            font-size: 7pt;
            line-height: 1.35;
        }
        .many-lines .receipt-card { padding: 4mm; }
        .many-lines .items-table { font-size: 6.7pt; }
        .many-lines .items-table th, .many-lines .items-table td { padding-top: 1mm; padding-bottom: 1mm; }
        .print-toolbar { display: flex; justify-content: flex-end; gap: 8px; width: 100%; max-width: 190mm; margin: 10px auto; }
        .print-toolbar button { border: 0; border-radius: 6px; padding: 10px 18px; color: #fff; font-weight: normal; cursor: pointer; }
        .print-btn { background: #1976d2; }
        .close-btn { background: #607d8b; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
@if($browserPrintFallback)
    <div class="print-toolbar no-print">
        <button class="print-btn" type="button" onclick="window.print()">Print</button>
        <button class="close-btn" type="button" onclick="window.close()">Close</button>
    </div>
@endif
<div class="page {{ $lineCount > 6 ? 'many-lines' : '' }}">
    @if(count($receiptCopies) === 1)
        <div class="single-copy-wrap">
            @include('pumperdashboard::print.partials.credit_sale_pdf_copy', ['copyLabel' => $receiptCopies[0]])
        </div>
    @else
        <table class="copies-table">
            <tr>
                @foreach($receiptCopies as $copyLabel)
                    <td class="copy-cell">
                        @include('pumperdashboard::print.partials.credit_sale_pdf_copy', ['copyLabel' => $copyLabel])
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="page-footer">
        @if($creditBillFooter !== '')
            {!! nl2br(e($creditBillFooter)) !!}
        @else
            This Software is developed by SYZYGY Technologies.
        @endif
    </div>
</div>
@if($browserPrintFallback)
<script>
    window.addEventListener('load', function () {
        window.setTimeout(function () { window.print(); }, 450);
    });
</script>
@endif
</body>
</html>
