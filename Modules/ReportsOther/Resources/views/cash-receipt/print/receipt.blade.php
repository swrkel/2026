<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $receipt->receipt_no }}</title>
    <style>
        *{box-sizing:border-box}html,body{max-width:100%;overflow-x:hidden}body{font-family:Arial,sans-serif;color:#111;margin:0;padding:24px 32px;background:#fff}.paper{width:100%;max-width:900px;margin:0 auto;padding:0 8px}.head{display:flex;justify-content:space-between;gap:30px;align-items:flex-start;min-width:0}.head>div{min-width:0}.business{font-size:26px;font-weight:700;overflow-wrap:anywhere}.location{font-size:16px;font-weight:700;margin-top:5px;overflow-wrap:anywhere}.address{font-size:13px;margin-top:3px;overflow-wrap:anywhere}.meta{min-width:250px;max-width:42%;font-size:14px}.meta div{display:flex;justify-content:space-between;gap:20px;margin:5px 0}.fields{margin:28px 0 20px;display:grid;gap:9px}.field{min-width:0;overflow-wrap:anywhere}.field span{display:inline-block;min-width:130px;font-weight:700}.words strong{font-weight:700}table{width:100%;max-width:100%;border-collapse:collapse;table-layout:fixed;margin-top:18px}th,td{border:1px solid #333;padding:8px 10px;overflow-wrap:anywhere;word-break:break-word}th{background:#f5f5f5}.right{text-align:right}.amount{width:30%}.cheques{margin-top:30px;display:grid;grid-template-columns:130px minmax(0,1fr);gap:12px}.cheque-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) 140px;gap:10px;padding:5px 0;border-bottom:1px dotted #aaa}.cheque-row>*{min-width:0;overflow-wrap:anywhere}.signature{margin-top:70px;margin-left:auto;width:240px;max-width:45%;border-top:1px dotted #333;text-align:center;padding-top:7px}.print-actions{text-align:right;margin:0 auto 18px;max-width:900px;padding:0 8px}.print-actions button{padding:8px 14px}@page{size:A4 portrait;margin:12mm 14mm}@media print{html,body{width:auto!important;max-width:none!important;overflow:visible!important}body{padding:0!important}.print-actions{display:none!important}.paper{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important}.head{gap:18px}.meta{min-width:220px}.cheques{grid-template-columns:110px minmax(0,1fr)}.cheque-row{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 120px}}@media(max-width:650px){body{padding:18px 14px}.paper{padding:0}.head{display:grid}.meta{min-width:0;max-width:none}.cheques{grid-template-columns:1fr}.cheque-row{grid-template-columns:1fr}.signature{max-width:none}}
    </style>
</head>
<body>
<div class="print-actions"><button type="button" onclick="window.print()">Print</button></div>
<div class="paper">
    <div class="head">
        <div>
            <div class="business">{{ $organisation['business_name'] }}</div>
            <div class="location">{{ $organisation['location_name'] }}</div>
            @if($organisation['location_address'])<div class="address">{{ $organisation['location_address'] }}</div>@endif
        </div>
        <div class="meta">
            <div><span>Receipt No:</span><strong>{{ $receipt->receipt_no }}</strong></div>
            <div><span>Date:</span><strong>{{ $receipt->receipt_date?->format('Y-m-d') }}</strong></div>
        </div>
    </div>
    <div class="fields">
        <div class="field"><span>Membership No:</span><strong>{{ $receipt->membership_no ?: '—' }}</strong></div>
        <div class="field"><span>Source:</span><strong>{{ $receipt->source_name }}</strong></div>
        <div class="field words"><span>Amount:</span><strong>{{ $receipt->amount_in_words }}</strong></div>
    </div>
    <table>
        <thead><tr><th>Source Details</th><th class="right amount">Amount</th></tr></thead>
        <tbody>
        @foreach($receipt->details as $detail)
            <tr><td>{{ $detail->source_detail }}</td><td class="right">{{ number_format((float)$detail->amount, $currencyPrecision, '.', ',') }}</td></tr>
        @endforeach
        </tbody>
        <tfoot><tr><th class="right">Total</th><th class="right">{{ number_format((float)$receipt->total_amount, $currencyPrecision, '.', ',') }}</th></tr></tfoot>
    </table>
    <div class="cheques">
        <strong>Cheque No:</strong>
        <div>
            @forelse($receipt->cheques as $cheque)
                <div class="cheque-row"><span>{{ $cheque->cheque_number ?: '—' }}</span><span>{{ $cheque->bank_name ?: '—' }}</span><span>{{ $cheque->cheque_date?->format('Y-m-d') ?: '—' }}</span></div>
            @empty
                <span>—</span>
            @endforelse
        </div>
    </div>
    <div class="signature">Signature of the Officer</div>
</div>
<script>window.addEventListener('load',()=>window.print());</script>
</body>
</html>
