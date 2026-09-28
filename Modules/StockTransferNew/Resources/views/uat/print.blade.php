<!doctype html>
<html><head><meta charset="utf-8"><title>Stock Transfer-New UAT Checklist</title>
<style>body{font-family:Arial,sans-serif;font-size:12px}.card{page-break-inside:avoid;margin-bottom:18px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #888;padding:6px}h1{margin-bottom:3px}</style>
</head><body onload="window.print()">
<h1>Stock Transfer-New UAT Checklist</h1>
<p>Total checks: {{ $summary['total_checks'] }} | Recommended pass rate: {{ $summary['recommended_pass_rate'] }}%</p>
@foreach($checklist as $group => $items)
<div class="card"><h3>{{ ucwords(str_replace('_', ' ', $group)) }}</h3>
<table><thead><tr><th>Check</th><th>Pass</th><th>Fail</th><th>Remarks</th></tr></thead><tbody>
@foreach($items as $item)<tr><td>{{ $item }}</td><td></td><td></td><td></td></tr>@endforeach
</tbody></table></div>
@endforeach
</body></html>
