<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt {{ $receipt->receipt_no }}</title>
<style>body{font-family:Arial,sans-serif;color:#111;max-width:900px;margin:30px auto;padding:0 18px}.h{display:flex;justify-content:space-between;gap:24px}.b{font-size:24px;font-weight:700}.l{font-weight:700;margin-top:4px}.m{margin:24px 0;line-height:1.8}table{width:100%;border-collapse:collapse}th,td{border:1px solid #555;padding:8px}th{background:#f3f4f6}.r{text-align:right}.c{margin-top:24px}.cr{display:grid;grid-template-columns:1fr 1fr 140px;gap:10px;padding:6px 0;border-bottom:1px dotted #aaa}.s{margin-top:60px;margin-left:auto;width:230px;text-align:center;border-top:1px dotted #444;padding-top:6px}</style>
</head>
<body>
<div class="h"><div><div class="b">{{ $organisation['business_name'] }}</div><div class="l">{{ $organisation['location_name'] }}</div><div>{{ $organisation['location_address'] }}</div></div><div><div><b>Receipt No:</b> {{ $receipt->receipt_no }}</div><div><b>Date:</b> {{ $receipt->receipt_date?->format('Y-m-d') }}</div></div></div>
<div class="m"><div><b>Membership No:</b> {{ $receipt->membership_no ?: '—' }}</div><div><b>Source:</b> {{ $receipt->source_name }}</div><div><b>Amount:</b> {{ $receipt->amount_in_words }}</div></div>
<table><thead><tr><th>Source Details</th><th class="r">Amount</th></tr></thead><tbody>@foreach($receipt->details as $detail)<tr><td>{{ $detail->source_detail }}</td><td class="r">{{ number_format((float)$detail->amount, $currencyPrecision, '.', ',') }}</td></tr>@endforeach</tbody><tfoot><tr><th class="r">Total</th><th class="r">{{ number_format((float)$receipt->total_amount, $currencyPrecision, '.', ',') }}</th></tr></tfoot></table>
<div class="c"><b>Cheque No:</b>@forelse($receipt->cheques as $cheque)<div class="cr"><span>{{ $cheque->cheque_number ?: '—' }}</span><span>{{ $cheque->bank_name ?: '—' }}</span><span>{{ $cheque->cheque_date?->format('Y-m-d') ?: '—' }}</span></div>@empty <span>—</span>@endforelse</div>
<div class="s">Signature of the Officer</div>
</body>
</html>
