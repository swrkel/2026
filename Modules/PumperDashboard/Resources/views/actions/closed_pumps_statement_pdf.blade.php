@php
    $browserPrintFallback = !empty($browser_print_fallback);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Closed Pumps &amp; Other Sales Statement</title>
<style>
    @page { size: A4 landscape; margin: 0; }
    * { box-sizing: border-box; }
    html, body {
        margin: 0;
        padding: 0;
        color: #263238;
        background: #fff;
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 8.8pt;
        line-height: 1.25;
    }
    .statement { width: 100%; margin: 0 auto; padding: 8mm 9mm; }
    .statement-header { margin-bottom: 4mm; text-align: center; }
    .business-name { margin-bottom: 1mm; color: #0d47a1; font-size: 20pt; font-weight: 800; }
    .statement-title { margin: 0; color: #1565c0; font-size: 15pt; font-weight: 800; text-transform: uppercase; }
    .info-bar { width: 100%; margin: 0 0 5mm; border: .25mm solid #d6e3f1; border-collapse: collapse; background: #f8fbff; }
    .info-bar td { width: 33.333%; padding: 2.4mm 3mm; font-size: 9.5pt; font-weight: 700; vertical-align: middle; }
    .info-bar td:nth-child(2) { text-align: center; }
    .info-bar td:nth-child(3) { text-align: right; }
    .circle {
        display: inline-block;
        width: 5.5mm;
        height: 5.5mm;
        margin-right: 2mm;
        border-radius: 50%;
        background: #dcecff;
        color: #0d47a1;
        text-align: center;
        font-size: 8pt;
        font-weight: 800;
        line-height: 5.5mm;
        vertical-align: middle;
    }
    .section-title { margin: 3.5mm 0 1.5mm; color: #0d47a1; font-size: 10.5pt; font-weight: 900; text-transform: uppercase; }
    table.data { width: 100%; margin-bottom: 4mm; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
    .data th, .data td { padding: 1.8mm 1.6mm; border: .25mm solid #c8d7e6; text-align: center; vertical-align: middle; }
    .data th { background: #0d47a1; color: #fff; font-size: 7.8pt; font-weight: 800; text-transform: uppercase; }
    .data tbody tr:nth-child(even) { background: #f8fafc; }
    .data td.number { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .data tr.totals td { background: #e3f2fd; color: #0d47a1; font-size: 9.5pt; font-weight: 900; }
    .other-table { width: 70%; }
    .bottom { width: 100%; margin-top: 4mm; border-collapse: collapse; page-break-inside: avoid; }
    .bottom td { vertical-align: bottom; }
    .summary-cell { width: 70%; padding: 0 8mm 0 0; }
    .signature-cell { width: 30%; padding-left: 8mm; text-align: center; }
    .summary { width: 100%; border: .25mm solid #ccd8e4; border-collapse: collapse; background: #fff; }
    .summary td { padding: 2.2mm 3mm; border-bottom: .2mm solid #d8e2eb; font-weight: 700; }
    .summary td:nth-child(2) { width: 8%; text-align: center; }
    .summary td:last-child { width: 32%; text-align: right; white-space: nowrap; }
    .summary tr.grand td { background: #e3f2fd; color: #0d47a1; font-size: 11.5pt; font-weight: 900; }
    .signature-line { padding-top: 2mm; border-top: .3mm dotted #777; font-weight: 700; }
    .statement-footer { width: 100%; margin-top: 6mm; padding-top: 2mm; border-top: .25mm solid #d6dfe7; border-collapse: collapse; color: #607d8b; font-size: 7.5pt; }
    .statement-footer td:last-child { text-align: right; }
    .empty { padding: 4mm !important; color: #718292; }
    .print-toolbar { display: flex; justify-content: flex-end; gap: 8px; width: 100%; max-width: 277mm; margin: 10px auto; }
    .print-toolbar button { border: 0; border-radius: 6px; padding: 10px 18px; color: #fff; font-weight: 700; cursor: pointer; }
    .print-btn { background: #1976d2; }
    .close-btn { background: #607d8b; }
    @media print {
        @page { size: A4 landscape; margin: 0 !important; }
        html, body { margin: 0 !important; padding: 0 !important; }
        .no-print { display: none !important; }
    }
</style>
</head>
<body>
@if($browserPrintFallback)
<div class="print-toolbar no-print">
    <button class="print-btn" type="button" onclick="window.print()">Print</button>
    <button class="close-btn" type="button" onclick="window.close()">Close</button>
</div>
@endif
<main class="statement">
    <header class="statement-header">
        <div class="business-name">{{ $business->name }}</div>
        <h1 class="statement-title">Closed Pumps &amp; Other Sales Statement</h1>
    </header>

    <table class="info-bar">
        <tr>
            <td><span class="circle">D</span>Date: {{ \Carbon\Carbon::parse($statement_date)->format('m/d/Y') }}</td>
            <td><span class="circle">S</span>Shift No: {{ $shift_number }}</td>
            <td><span class="circle">O</span>Operator: {{ $operator_name ?: '-' }}</td>
        </tr>
    </table>

    <div class="section-title"><span class="circle">M</span>Meter Sales</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:11%">Date</th>
                <th style="width:8%">Time</th>
                <th style="width:9%">Pump No</th>
                <th style="width:15%">Starting Meter</th>
                <th style="width:15%">Closing Meter</th>
                <th style="width:10%">Test Qty</th>
                <th style="width:12%">Sold Ltr</th>
                <th style="width:20%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($meterSales as $sale)
                @php
                    $rowDate = !empty($sale->date)
                        ? \Carbon\Carbon::parse($sale->date)->format('m/d/Y')
                        : (!empty($sale->created_at) ? \Carbon\Carbon::parse($sale->created_at)->format('m/d/Y') : '-');
                    $rowTime = !empty($sale->time)
                        ? \Carbon\Carbon::parse($sale->time)->format('H:i')
                        : (!empty($sale->created_at) ? \Carbon\Carbon::parse($sale->created_at)->format('H:i') : '-');
                @endphp
                <tr>
                    <td>{{ $rowDate }}</td>
                    <td>{{ $rowTime }}</td>
                    <td>{{ $sale->pump_no }}</td>
                    <td class="number">{{ number_format((float) $sale->starting_meter, 3, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) $sale->closing_meter, 3, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) $sale->testing_ltr, 3, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) $sale->sold_ltr, 3, '.', ',') }}</td>
                    <td class="number">{{ $currency_symbol }} {{ number_format((float) $sale->amount, 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="8">No closed-pump meter-sale records were found for this shift.</td></tr>
            @endforelse
            <tr class="totals">
                <td colspan="5">Total</td>
                <td class="number">{{ number_format($meter_totals->testing_ltr, 3, '.', ',') }}</td>
                <td class="number">{{ number_format($meter_totals->sold_ltr, 3, '.', ',') }}</td>
                <td class="number">{{ $currency_symbol }} {{ number_format($meter_totals->amount, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title"><span class="circle">S</span>Total Other Sales</div>
    <table class="data other-table">
        <thead>
            <tr>
                <th style="width:34%">Date &amp; Time</th>
                <th style="width:42%">Product Sub Category</th>
                <th style="width:24%">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($otherSales as $sale)
                <tr>
                    <td>{{ !empty($sale->created_at) ? \Carbon\Carbon::parse($sale->created_at)->format('Y-m-d H:i:s') : '-' }}</td>
                    <td>{{ $sale->product_sub_category }}</td>
                    <td class="number">{{ $currency_symbol }} {{ number_format((float) $sale->total_amount, 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="3">No other sales were recorded for this shift.</td></tr>
            @endforelse
            <tr class="totals">
                <td colspan="2">Total Other Sales</td>
                <td class="number">{{ $currency_symbol }} {{ number_format($total_other_sales, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="bottom">
        <tr>
            <td class="summary-cell">
                <table class="summary">
                    <tr><td>Total Meter Sale</td><td>:</td><td>{{ $currency_symbol }} {{ number_format($meter_totals->amount, 2, '.', ',') }}</td></tr>
                    <tr><td>Total Other Sales</td><td>:</td><td>{{ $currency_symbol }} {{ number_format($total_other_sales, 2, '.', ',') }}</td></tr>
                    <tr class="grand"><td>Total Sale</td><td>:</td><td>{{ $currency_symbol }} {{ number_format($grand_total, 2, '.', ',') }}</td></tr>
                </table>
            </td>
            <td class="signature-cell"><div class="signature-line">Signature Pump Operator</div></td>
        </tr>
    </table>

    <table class="statement-footer">
        <tr>
            <td>Printed Date &amp; Time: {{ $printed_at->format('m/d/Y H:i:s') }}</td>
            <td>{{ $business->name }} — Pump Operator System</td>
        </tr>
    </table>
</main>
@if($browserPrintFallback)
<script>
    window.addEventListener('load', function () {
        // Keep the fallback document title empty so browsers that still insist
        // on a print header cannot inject the report title into that area.
        document.title = '';
        window.setTimeout(function () { window.print(); }, 450);
    });
</script>
@endif
</body>
</html>
