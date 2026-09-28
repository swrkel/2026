<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title></title>
<style>
    body { margin: 0; padding: 0; color: #263238; font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; }
    .sheet { width: 100%; }
    .header { text-align: center; margin-bottom: 8px; }
    .business { color: #0d47a1; font-size: 20px; font-weight: bold; }
    .title { color: #1565c0; font-size: 14px; font-weight: bold; text-transform: uppercase; }
    .info, .data, .summary, .footer { width: 100%; border-collapse: collapse; }
    .info { margin: 8px 0 10px; background: #f8fbff; }
    .info td { border: 1px solid #d6e3f1; padding: 6px; font-weight: bold; }
    .section { margin: 9px 0 4px; color: #0d47a1; font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .data { margin-bottom: 9px; }
    .data th, .data td { border: 1px solid #c8d7e6; padding: 4px 3px; text-align: center; vertical-align: middle; }
    .data th { background: #0d47a1; color: #ffffff; font-size: 8px; font-weight: bold; }
    .data td.num { text-align: right; }
    .total td { background: #e3f2fd; color: #0d47a1; font-weight: bold; }
    .bottom { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .bottom td { vertical-align: bottom; }
    .summary-wrap { width: 70%; padding-right: 18px; }
    .sign-wrap { width: 30%; text-align: center; padding-left: 18px; }
    .summary td { border: 1px solid #d8e2eb; padding: 5px 7px; font-weight: bold; }
    .summary td:last-child { text-align: right; }
    .grand td { background: #e3f2fd; color: #0d47a1; font-size: 11px; }
    .signature { border-top: 1px dotted #777777; padding-top: 5px; font-weight: bold; }
    .footer { margin-top: 12px; border-top: 1px solid #d6dfe7; color: #607d8b; font-size: 7px; }
    .footer td { padding-top: 5px; }
    .footer td:last-child { text-align: right; }
</style>
</head>
<body>
<div class="sheet">
    <div class="header">
        <div class="business">{{ $business->name }}</div>
        <div class="title">Closed Pumps &amp; Other Sales Statement</div>
    </div>

    <table class="info">
        <tr>
            <td width="33%">Date: {{ \Carbon\Carbon::parse($statement_date)->format('m/d/Y') }}</td>
            <td width="34%" style="text-align:center;">Shift No: {{ $shift_number }}</td>
            <td width="33%" style="text-align:right;">Operator: {{ $operator_name ?: '-' }}</td>
        </tr>
    </table>

    <div class="section">Meter Sales</div>
    <table class="data">
        <thead>
            <tr>
                <th width="11%">Date</th><th width="8%">Time</th><th width="9%">Pump No</th>
                <th width="15%">Starting Meter</th><th width="15%">Closing Meter</th><th width="10%">Test Qty</th>
                <th width="12%">Sold Ltr</th><th width="20%">Amount</th>
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
                    <td>{{ $rowDate }}</td><td>{{ $rowTime }}</td><td>{{ $sale->pump_no }}</td>
                    <td class="num">{{ number_format((float) $sale->starting_meter, 3, '.', ',') }}</td>
                    <td class="num">{{ number_format((float) $sale->closing_meter, 3, '.', ',') }}</td>
                    <td class="num">{{ number_format((float) $sale->testing_ltr, 3, '.', ',') }}</td>
                    <td class="num">{{ number_format((float) $sale->sold_ltr, 3, '.', ',') }}</td>
                    <td class="num">{{ $currency_symbol }} {{ number_format((float) $sale->amount, 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No closed-pump meter-sale records were found for this shift.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="5">Total</td>
                <td class="num">{{ number_format($meter_totals->testing_ltr, 3, '.', ',') }}</td>
                <td class="num">{{ number_format($meter_totals->sold_ltr, 3, '.', ',') }}</td>
                <td class="num">{{ $currency_symbol }} {{ number_format($meter_totals->amount, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section">Total Other Sales</div>
    <table class="data" style="width:70%;">
        <thead><tr><th width="34%">Date &amp; Time</th><th width="42%">Product Sub Category</th><th width="24%">Total Amount</th></tr></thead>
        <tbody>
            @forelse($otherSales as $sale)
                <tr>
                    <td>{{ !empty($sale->created_at) ? \Carbon\Carbon::parse($sale->created_at)->format('Y-m-d H:i:s') : '-' }}</td>
                    <td>{{ $sale->product_sub_category }}</td>
                    <td class="num">{{ $currency_symbol }} {{ number_format((float) $sale->total_amount, 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No other sales were recorded for this shift.</td></tr>
            @endforelse
            <tr class="total"><td colspan="2">Total Other Sales</td><td class="num">{{ $currency_symbol }} {{ number_format($total_other_sales, 2, '.', ',') }}</td></tr>
        </tbody>
    </table>

    <table class="bottom">
        <tr>
            <td class="summary-wrap">
                <table class="summary">
                    <tr><td>Total Meter Sale</td><td>:</td><td>{{ $currency_symbol }} {{ number_format($meter_totals->amount, 2, '.', ',') }}</td></tr>
                    <tr><td>Total Other Sales</td><td>:</td><td>{{ $currency_symbol }} {{ number_format($total_other_sales, 2, '.', ',') }}</td></tr>
                    <tr class="grand"><td>Total Sale</td><td>:</td><td>{{ $currency_symbol }} {{ number_format($grand_total, 2, '.', ',') }}</td></tr>
                </table>
            </td>
            <td class="sign-wrap"><div class="signature">Signature Pump Operator</div></td>
        </tr>
    </table>

    <table class="footer"><tr><td>Printed Date &amp; Time: {{ $printed_at->format('m/d/Y H:i:s') }}</td><td>{{ $business->name }} — Pump Operator System</td></tr></table>
</div>
</body>
</html>
