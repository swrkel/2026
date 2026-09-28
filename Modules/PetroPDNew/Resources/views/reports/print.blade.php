<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title() }} — Petro PD-New</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font: 10px Arial, sans-serif; }
        .print-actions { margin: 0 0 12px; }
        .print-actions button { border: 0; border-radius: 5px; padding: 8px 14px; background: #1769aa; color: #fff; font-weight: 700; cursor: pointer; }
        .head { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #111827; padding-bottom: 8px; margin-bottom: 10px; }
        h1 { margin: 0 0 3px; font-size: 20px; }
        .muted { color: #667085; }
        .filters { margin: 8px 0 12px; padding: 7px 9px; background: #f2f4f7; border: 1px solid #d0d5dd; }
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        th, td { border: 1px solid #cfd6df; padding: 5px 6px; vertical-align: top; word-break: break-word; }
        th { background: #e9eef5; text-align: left; white-space: nowrap; }
        td.amount { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .empty { text-align: center; padding: 24px; color: #667085; }
        .footer { margin-top: 10px; color: #667085; font-size: 9px; }
        @media print { .print-actions { display: none; } }
    </style>
</head>
<body>
<div class="print-actions"><button type="button" onclick="window.print()">Print</button></div>
<div class="head">
    <div>
        <h1>{{ $report->title() }}</h1>
        <div class="muted">Petro PD-New — exclusive Pumper Dashboard-New settlement report</div>
    </div>
    <div style="text-align:right">
        <strong>Generated</strong><br>{{ $generatedAt->format('d M Y H:i:s') }}
    </div>
</div>
@if(array_filter($filters, static fn ($value) => $value !== null && $value !== ''))
<div class="filters">
    @foreach($filters as $key => $value)
        @if($value !== null && $value !== '')
            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}@unless($loop->last) &nbsp; | &nbsp; @endunless
        @endif
    @endforeach
</div>
@endif
<table>
    <thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            @foreach(array_keys($columns) as $column)
                @php($value = data_get($row, $column))
                <td class="{{ is_numeric($value) ? 'amount' : '' }}">
                    {{ is_numeric($value) && (str_contains($column, 'amount') || str_contains($column, 'total') || str_contains($column, 'variance')) ? number_format((float) $value, 4) : $value }}
                </td>
            @endforeach
        </tr>
    @empty
        <tr><td class="empty" colspan="{{ count($columns) }}">No report data for the selected filters.</td></tr>
    @endforelse
    </tbody>
</table>
<div class="footer">Generated from Petro PD-New records. Report output is limited to 10,000 rows for print and PDF safety.</div>
</body>
</html>
