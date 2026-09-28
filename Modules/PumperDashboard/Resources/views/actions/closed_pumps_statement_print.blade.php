<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
        :root {
            --primary: #0d47a1;
            --secondary: #1565c0;
            --light-blue: #eaf3ff;
            --border: #cbd8e6;
            --text: #263238;
            --muted: #607d8b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 18px;
            background: #eef3f8;
            color: var(--text);
            font-family: Calibri, "Segoe UI", Arial, sans-serif;
            font-size: 12px;
        }
        .print-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            width: 100%;
            max-width: 1120px;
            margin: 0 auto 10px;
        }
        .print-toolbar button {
            border: 0;
            border-radius: 6px;
            padding: 10px 18px;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .print-toolbar .print-btn { background: #1976d2; }
        .print-toolbar .close-btn { background: #607d8b; }
        .statement {
            width: 100%;
            max-width: 1120px;
            min-height: 720px;
            margin: 0 auto;
            padding: 24px 28px;
            background: #fff;
            border: 1px solid #d8e2ec;
            border-radius: 8px;
            box-shadow: 0 3px 14px rgba(0, 0, 0, 0.10);
        }
        .statement-header {
            text-align: center;
            margin-bottom: 14px;
        }
        .business-name {
            margin-bottom: 4px;
            color: var(--primary);
            font-size: 25px;
            font-weight: 800;
        }
        .statement-title {
            margin: 0;
            color: var(--secondary);
            font-size: 20px;
            font-weight: 800;
            letter-spacing: .3px;
            text-transform: uppercase;
        }
        .info-bar {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 16px 0 18px;
            padding: 10px 14px;
            border: 1px solid #d6e3f1;
            border-radius: 7px;
            background: #f8fbff;
            font-size: 13px;
            font-weight: 700;
        }
        .info-item { display: flex; align-items: center; gap: 7px; }
        .info-item:nth-child(2) { justify-content: center; }
        .info-item:nth-child(3) { justify-content: flex-end; }
        .info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #dcecff;
            color: var(--primary);
            font-size: 12px;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 17px 0 7px;
            color: var(--primary);
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .section-title .section-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 23px;
            height: 23px;
            border-radius: 50%;
            background: var(--light-blue);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }
        th, td {
            border: 1px solid var(--border);
            padding: 7px 6px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: var(--primary);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td.number { text-align: right; font-variant-numeric: tabular-nums; }
        .totals-row td {
            background: var(--light-blue);
            color: #163b62;
            font-weight: 800;
        }
        .empty-row td {
            padding: 14px;
            color: var(--muted);
            font-style: italic;
        }
        .other-sales-table { width: 70%; }
        .summary-signature {
            display: grid;
            grid-template-columns: minmax(360px, 1fr) 320px;
            gap: 36px;
            align-items: end;
            margin-top: 20px;
        }
        .summary {
            border: 1px solid #d3dfeb;
            border-radius: 7px;
            overflow: hidden;
        }
        .summary-row {
            display: grid;
            grid-template-columns: 1fr 24px 150px;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid #e1e8ef;
            font-size: 13px;
            font-weight: 700;
        }
        .summary-row:last-child { border-bottom: 0; }
        .summary-row .amount { text-align: right; font-variant-numeric: tabular-nums; }
        .summary-row.grand {
            background: var(--light-blue);
            color: var(--primary);
            font-size: 16px;
            font-weight: 900;
        }
        .signature-wrap {
            padding-top: 28px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px dotted #455a64;
            padding-top: 7px;
            color: #455a64;
            font-size: 12px;
            font-weight: 700;
        }
        .statement-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #e1e8ef;
            color: #607d8b;
            font-size: 10px;
        }
        @media print {
            @page { size: A4 landscape; margin: 0; }
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            .statement {
                max-width: none;
                min-height: 0;
                margin: 8mm;
                padding: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
            table, tr, td, th { page-break-inside: avoid; }
        }
        @media (max-width: 760px) {
            .info-bar { grid-template-columns: 1fr; }
            .info-item, .info-item:nth-child(2), .info-item:nth-child(3) { justify-content: flex-start; }
            .other-sales-table { width: 100%; }
            .summary-signature { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
        <button type="button" class="close-btn" onclick="window.close()">Close</button>
    </div>

    <main class="statement">
        <header class="statement-header">
            <div class="business-name">{{ $business->name }}</div>
            <h1 class="statement-title">Closed Pumps & Other Sales Statement</h1>
        </header>

        <section class="info-bar">
            <div class="info-item">
                <span class="info-icon">D</span>
                <span>Date: {{ \Carbon\Carbon::parse($statement_date)->format('m/d/Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-icon">S</span>
                <span>Shift No: {{ $shift_number }}</span>
            </div>
            <div class="info-item">
                <span class="info-icon">O</span>
                <span>Operator: {{ $operator_name ?: '-' }}</span>
            </div>
        </section>

        <h2 class="section-title"><span class="section-icon">M</span> Meter Sales</h2>
        <table class="meter-sales-table">
            <thead>
                <tr>
                    <th style="width: 11%;">Date</th>
                    <th style="width: 8%;">Time</th>
                    <th style="width: 9%;">Pump No</th>
                    <th style="width: 14%;">Starting Meter</th>
                    <th style="width: 14%;">Closing Meter</th>
                    <th style="width: 10%;">Test Qty</th>
                    <th style="width: 12%;">Sold Ltr</th>
                    <th style="width: 16%;">Amount</th>
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
                    <tr class="empty-row"><td colspan="8">No closed-pump meter-sale records were found for this shift.</td></tr>
                @endforelse
                <tr class="totals-row">
                    <td colspan="5">Total</td>
                    <td class="number">{{ number_format($meter_totals->testing_ltr, 3, '.', ',') }}</td>
                    <td class="number">{{ number_format($meter_totals->sold_ltr, 3, '.', ',') }}</td>
                    <td class="number">{{ $currency_symbol }} {{ number_format($meter_totals->amount, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <h2 class="section-title"><span class="section-icon">S</span> Total Other Sales</h2>
        <table class="other-sales-table">
            <thead>
                <tr>
                    <th style="width: 34%;">Date & Time</th>
                    <th style="width: 40%;">Product Sub Category</th>
                    <th style="width: 26%;">Total Amount</th>
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
                    <tr class="empty-row"><td colspan="3">No other sales were recorded for this shift.</td></tr>
                @endforelse
                <tr class="totals-row">
                    <td colspan="2">Total Other Sales</td>
                    <td class="number">{{ $currency_symbol }} {{ number_format($total_other_sales, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <section class="summary-signature">
            <div class="summary">
                <div class="summary-row">
                    <span>Total Meter Sale</span><span>:</span>
                    <span class="amount">{{ $currency_symbol }} {{ number_format($meter_totals->amount, 2, '.', ',') }}</span>
                </div>
                <div class="summary-row">
                    <span>Total Other Sales</span><span>:</span>
                    <span class="amount">{{ $currency_symbol }} {{ number_format($total_other_sales, 2, '.', ',') }}</span>
                </div>
                <div class="summary-row grand">
                    <span>Total Sale</span><span>:</span>
                    <span class="amount">{{ $currency_symbol }} {{ number_format($grand_total, 2, '.', ',') }}</span>
                </div>
            </div>
            <div class="signature-wrap">
                <div class="signature-line">Signature Pump Operator</div>
            </div>
        </section>

        <footer class="statement-footer">
            <span>Printed Date & Time: {{ $printed_at->format('m/d/Y H:i:s') }}</span>
            <span>{{ $business->name }} — Pump Operator System</span>
        </footer>
    </main>

    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                window.print();
            }, 450);
        });
    </script>
</body>
</html>
