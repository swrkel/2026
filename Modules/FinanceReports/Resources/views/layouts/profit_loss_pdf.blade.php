<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit &amp; Loss - New</title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #111827;
            background: #fff;
        }
        h1 { margin: 0 0 3px; font-size: 18px; }
        .meta { margin: 0 0 12px; color: #64748b; font-size: 9px; }
        .section {
            border: 1px solid #d8dee6;
            margin: 0 0 10px;
            page-break-inside: auto;
            break-inside: auto;
        }
        .section-title {
            padding: 7px 9px;
            border-bottom: 1px solid #d8dee6;
            background: #f8fafc;
            font-weight: 700;
            font-size: 12px;
        }
        .section-body { padding: 7px 9px; }
        .two-col { display: table; width: 100%; table-layout: fixed; border-spacing: 8px 0; margin: 0 -8px 10px; }
        .two-col > div { display: table-cell; width: 50%; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; }
        th { background: #f1f5f9; font-weight: 700; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        .total th, .total td { background: #eef2f7; font-weight: 700; border-top: 2px solid #94a3b8; }
        .negative { color: #b91c1c; }
        .net-box { border: 1px solid #d8dee6; padding: 8px 10px; font-size: 12px; font-weight: 700; }
        .empty { text-align: center; color: #64748b; padding: 10px; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
@php
    $activeTab = $profit_tabs[$profit_tab] ?? ['label' => 'Profit Breakdown', 'column' => 'Description'];
    $locationLabel = 'All Locations';
    if (!empty($location_id)) {
        if (is_object($locations) && method_exists($locations, 'get')) {
            $locationLabel = $locations->get($location_id) ?? $locationLabel;
        } elseif (is_array($locations)) {
            $locationLabel = $locations[$location_id] ?? $locationLabel;
        }
    }
@endphp

<h1>Profit &amp; Loss - New</h1>
<div class="meta">
    Period: {{ $start }} to {{ $end }} &middot;
    Location: {{ $locationLabel }} &middot;
    Generated: {{ $generated_at->format('d/m/Y H:i') }}
</div>

<div class="section">
    <div class="section-title">Financial Performance Summary</div>
    <div class="section-body">
        <table>
            <tbody>
                <tr><th>Sales / Operating Revenue</th><td class="num">{{ number_format($report['totals']['revenue'] ?? 0, 4, '.', ',') }}</td></tr>
                <tr><th>Cost of Goods Sold (COGS)</th><td class="num">{{ number_format($report['totals']['cogs'] ?? 0, 4, '.', ',') }}</td></tr>
                <tr class="total"><th>Gross Profit / (Loss)</th><td class="num">{{ number_format($report['totals']['gross_profit'] ?? 0, 4, '.', ',') }}</td></tr>
                <tr><th>Other Income</th><td class="num">{{ number_format($report['totals']['other_income'] ?? 0, 4, '.', ',') }}</td></tr>
                <tr><th>Operating / Other Expenses</th><td class="num">{{ number_format($report['totals']['operating_expenses'] ?? 0, 4, '.', ',') }}</td></tr>
                <tr class="total"><th>Net Profit / (Loss)</th><td class="num">{{ number_format($report['totals']['net_profit'] ?? 0, 4, '.', ',') }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="two-col">
    <div>
        <div class="section">
            <div class="section-title">Income</div>
            <div class="section-body">
                <table>
                    <tbody>
                        @forelse($report['income'] ?? [] as $row)
                            <tr><td>{{ $row->name }}</td><td class="num">{{ number_format($row->amount, 4, '.', ',') }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="empty">No income entries.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="total"><th>Total Income</th><th class="num">{{ number_format($report['totals']['income'] ?? 0, 4, '.', ',') }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div>
        <div class="section">
            <div class="section-title">Expenses</div>
            <div class="section-body">
                <table>
                    <tbody>
                        @forelse($report['expenses'] ?? [] as $row)
                            <tr><td>{{ $row->name }}</td><td class="num">{{ number_format($row->amount, 4, '.', ',') }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="empty">No expense entries.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="total"><th>Total Expenses</th><th class="num">{{ number_format($report['totals']['expenses'] ?? 0, 4, '.', ',') }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">Profit Breakdown &mdash; {{ $activeTab['label'] }}</div>
    <div class="section-body">
        <table>
            <thead>
                <tr>
                    <th>{{ $activeTab['column'] ?? 'Description' }}</th>
                    @if(!empty($show_location_column))<th>Location</th>@endif
                    <th class="num">Quantity</th>
                    <th class="num">Revenue</th>
                    <th class="num">Cost</th>
                    <th class="num">Discount</th>
                    <th class="num">Gross Profit</th>
                    <th class="num">Margin %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($profit_rows as $row)
                    <tr>
                        <td>{{ $row->label }}</td>
                        @if(!empty($show_location_column))<td>{{ $row->location_label ?? '—' }}</td>@endif
                        <td class="num">{{ number_format($row->quantity, 2, '.', ',') }}</td>
                        <td class="num">{{ number_format($row->revenue, 2, '.', ',') }}</td>
                        <td class="num">{{ number_format($row->cost, 2, '.', ',') }}</td>
                        <td class="num">{{ number_format($row->discount ?? 0, 2, '.', ',') }}</td>
                        <td class="num {{ $row->profit < 0 ? 'negative' : '' }}">{{ number_format($row->profit, 2, '.', ',') }}</td>
                        <td class="num {{ $row->margin < 0 ? 'negative' : '' }}">{{ number_format($row->margin, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ !empty($show_location_column) ? 8 : 7 }}" class="empty">No sales found for the selected period.</td></tr>
                @endforelse
            </tbody>
            @if(count($profit_rows) > 0)
                <tfoot>
                    <tr class="total">
                        <th>Total</th>
                        @if(!empty($show_location_column))<th></th>@endif
                        <th class="num">{{ number_format($profit_totals['quantity'] ?? 0, 2, '.', ',') }}</th>
                        <th class="num">{{ number_format($profit_totals['revenue'] ?? 0, 2, '.', ',') }}</th>
                        <th class="num">{{ number_format($profit_totals['cost'] ?? 0, 2, '.', ',') }}</th>
                        <th class="num">{{ number_format($profit_totals['discount'] ?? 0, 2, '.', ',') }}</th>
                        <th class="num">{{ number_format($profit_totals['profit'] ?? 0, 2, '.', ',') }}</th>
                        <th class="num">{{ number_format($profit_totals['margin'] ?? 0, 2, '.', ',') }}</th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="net-box">Net Profit / Loss: {{ number_format($report['totals']['net_profit'] ?? 0, 4, '.', ',') }}</div>
</body>
</html>
