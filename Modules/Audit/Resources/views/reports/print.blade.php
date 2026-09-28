<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Audit Findings Report</title>
<style>
body{font-family:Arial,sans-serif;font-size:10px;color:#222;margin:18px}h2{text-align:center;margin:0 0 4px}.meta{text-align:center;color:#666;margin-bottom:14px}table{border-collapse:collapse;width:100%;table-layout:fixed}th,td{border:1px solid #bbb;padding:5px;vertical-align:top;word-break:break-word}th{background:#eee;font-weight:700}.note{padding:8px;background:#fff5d9;margin-bottom:10px}.empty{text-align:center;padding:16px;color:#666}@media print{.no-print{display:none}body{margin:0}}
</style>
</head>
<body>
@isset($pdfFallback)
<div class="note no-print">PDF library was not found, so this printable report is shown. Use browser Print → Save as PDF, or install/enable Barryvdh DomPDF.</div>
@endisset
<h2>Audit Findings Report</h2>
<div class="meta">Generated {{ now()->format('d M Y H:i') }}</div>
<table>
    <thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>@foreach(array_keys($columns) as $key)<td>{{ data_get($row,$key) }}</td>@endforeach</tr>
    @empty
        <tr><td colspan="{{ count($columns) }}" class="empty">No audit findings match the selected filters.</td></tr>
    @endforelse
    </tbody>
</table>
<script>window.addEventListener('load',function(){ if(!document.querySelector('.note')) window.print(); });</script>
</body>
</html>
