<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>&nbsp;</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 7mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
        }

        .statement {
            width: 100%;
        }

        .statement-title {
            margin: 0 0 5px;
            text-align: center;
            color: #0f4c81;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .business-name {
            margin: 0;
            text-align: center;
            font-size: 15px;
            font-weight: 800;
        }

        .business-meta {
            margin: 2px 0 8px;
            text-align: center;
            font-size: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px 12px;
            margin-bottom: 8px;
            padding: 7px 9px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
        }

        .summary-label {
            font-weight: 700;
            color: #374151;
        }

        .section-title {
            margin: 7px 0 0;
            padding: 5px 7px;
            background: #0f5fa6;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 4px 5px;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        th {
            background: #dbeafe;
            color: #123b61;
            font-weight: 800;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-table {
            width: 45%;
            margin: 8px 0 0 auto;
        }

        .totals-table td:first-child {
            font-weight: 700;
        }

        .grand-total td {
            background: #e0f2fe;
            color: #0c4a6e;
            font-size: 12px;
            font-weight: 800;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            gap: 25mm;
            margin-top: 16mm;
        }

        .signature {
            width: 55mm;
            padding-top: 4px;
            border-top: 1px solid #111827;
            text-align: center;
        }

        .empty-row {
            text-align: center;
            color: #6b7280;
            padding: 9px;
        }

        @media screen {
            body {
                padding: 10mm;
                background: #e5e7eb;
            }

            .statement {
                max-width: 1120px;
                margin: 0 auto;
                padding: 8mm;
                background: #fff;
                box-shadow: 0 2px 14px rgba(0, 0, 0, .14);
            }
        }

        @media print {
            html,
            body,
            .statement {
                background: #fff !important;
            }

            body,
            .statement {
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            tr,
            td,
            th {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $locationParts = array_values(array_filter([
            $business_location->name ?? null,
            $business_location->landmark ?? null,
            $business_location->city ?? null,
            $business_location->state ?? null,
        ]));
        $locationText = implode(', ', array_unique($locationParts));
        $mobile = $business_location->mobile ?? ($business->mobile ?? '');
        $statementDate = $shift->shift_date ?? $shift->created_at ?? null;
    @endphp

    <main class="statement">
        <h1 class="statement-title">CLOSED PUMPS &amp; OTHER SALES STATEMENT</h1>
        <h2 class="business-name">{{ $business->name ?? '' }}</h2>
        <div class="business-meta">
            @if($locationText !== ''){{ $locationText }}@endif
            @if(!empty($mobile)){{ $locationText !== '' ? ' | ' : '' }}Mobile: {{ $mobile }}@endif
        </div>

        <div class="summary-grid">
            <div><span class="summary-label">Date:</span> {{ !empty($statementDate) ? @format_date($statementDate) : '—' }}</div>
            <div><span class="summary-label">Shift No:</span> {{ $shift_number }}</div>
            <div><span class="summary-label">Pump Operator:</span> {{ $pump_operator->name ?? '—' }}</div>
            <div><span class="summary-label">Closed Time:</span> {{ !empty($shift->closed_time) ? @format_datetime($shift->closed_time) : '—' }}</div>
        </div>

        <div class="section-title">Meter Sales</div>
        <table>
            <thead>
                <tr>
                    <th style="width:5%;">No.</th>
                    <th style="width:13%;">Pump</th>
                    <th style="width:16%;">Fuel</th>
                    <th style="width:12%;">Opening Meter</th>
                    <th style="width:12%;">Closing Meter</th>
                    <th style="width:10%;">Testing Qty</th>
                    <th style="width:10%;">Sold Qty</th>
                    <th style="width:11%;">Unit Price</th>
                    <th style="width:11%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($meter_sales as $index => $sale)
                    @php
                        $soldQty = (float) ($sale->sold_ltr ?? 0);
                        $amount = (float) ($sale->amount ?? 0);
                        $unitPrice = $soldQty != 0.0 ? $amount / $soldQty : 0.0;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $sale->pump_display ?? '—' }}</td>
                        <td>{{ $sale->product_name ?? '—' }}</td>
                        <td class="text-right">{{ number_format((float) ($sale->starting_meter ?? 0), 3, '.', ',') }}</td>
                        <td class="text-right">{{ number_format((float) ($sale->closing_meter ?? 0), 3, '.', ',') }}</td>
                        <td class="text-right">{{ number_format((float) ($sale->testing_ltr ?? 0), 3, '.', ',') }}</td>
                        <td class="text-right">{{ number_format($soldQty, 3, '.', ',') }}</td>
                        <td class="text-right">{{ number_format($unitPrice, 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format($amount, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty-row">No meter sales found for this shift.</td></tr>
                @endforelse
                <tr>
                    <td colspan="8" class="text-right"><strong>Total Meter Sales</strong></td>
                    <td class="text-right"><strong>{{ number_format($meter_total, 2, '.', ',') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Other Sales</div>
        <table>
            <thead>
                <tr>
                    <th style="width:7%;">No.</th>
                    <th>Product</th>
                    <th style="width:14%;">Unit</th>
                    <th style="width:14%;">Qty</th>
                    <th style="width:17%;">Unit Price</th>
                    <th style="width:17%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($other_sales as $index => $sale)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $sale->product_name ?? '—' }}</td>
                        <td class="text-center">{{ $sale->unit_name ?? '—' }}</td>
                        <td class="text-right">{{ number_format((float) ($sale->qty ?? 0), 3, '.', ',') }}</td>
                        <td class="text-right">{{ number_format((float) ($sale->price ?? 0), 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format((float) ($sale->net_amount ?? 0), 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-row">No other sales found for this shift.</td></tr>
                @endforelse
                <tr>
                    <td colspan="5" class="text-right"><strong>Total Other Sales</strong></td>
                    <td class="text-right"><strong>{{ number_format($other_total, 2, '.', ',') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td>Total Meter Sales</td>
                <td class="text-right">{{ number_format($meter_total, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Total Other Sales</td>
                <td class="text-right">{{ number_format($other_total, 2, '.', ',') }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total Sales</td>
                <td class="text-right">{{ number_format($grand_total, 2, '.', ',') }}</td>
            </tr>
        </table>

        <div class="signature-row">
            <div class="signature">Pump Operator Signature</div>
            <div class="signature">Supervisor Signature</div>
        </div>
    </main>

    <script>
        window.addEventListener('load', function () {
            document.title = '\u00a0';
            window.print();
        });
    </script>
</body>
</html>
