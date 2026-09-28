<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Cash Receipt List</title>
<style>body{font-family:Arial,sans-serif;color:#111;margin:28px}.head{margin-bottom:20px}.business{font-size:22px;font-weight:700}.sub{margin-top:4px;color:#444}table{width:100%;border-collapse:collapse}th,td{border:1px solid #777;padding:7px 8px}th{background:#f3f4f6;text-align:left}.r{text-align:right}</style>
</head>
<body>
<div class="head"><div class="business">{{ $organisation['business_name'] }}</div><div>{{ $organisation['location_name'] }}</div><div class="sub">Cash Receipt List · {{ $dateFrom }} to {{ $dateTo }}</div></div>
<table><thead><tr><th>Date</th><th>Receipt No</th><th>Source</th><th class="r">Total Amount</th><th>Entered By</th></tr></thead><tbody>@forelse($receipts as $receipt)<tr><td>{{ $receipt->receipt_date?->format('Y-m-d') }}</td><td>{{ $receipt->receipt_no }}</td><td>{{ $receipt->source_name }}</td><td class="r">{{ number_format((float)$receipt->total_amount, $currencyPrecision, '.', ',') }}</td><td>{{ $receipt->entered_by_name ?: '—' }}</td></tr>@empty<tr><td colspan="5">No Receipts found.</td></tr>@endforelse</tbody></table>
</body>
</html>
