<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $settlement->settlement_number }}</title>
<style>
body{font:12px Arial,sans-serif;color:#111;margin:20px}h1,h2,h3{margin:0 0 8px}.head{display:flex;justify-content:space-between;border-bottom:2px solid #111;padding-bottom:10px;margin-bottom:14px}.meta{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:12px 0}.meta div{border:1px solid #bbb;padding:8px}.meta span{display:block;color:#555;font-size:10px;margin-bottom:3px}table{width:100%;border-collapse:collapse;margin:10px 0 18px}th,td{border:1px solid #bbb;padding:6px;text-align:left}th{background:#eee}.amount{text-align:right}.no-print{margin-bottom:15px}.note{border:1px solid #bbb;padding:10px;margin-top:10px}@media print{.no-print{display:none}body{margin:0}}
</style>
</head>
<body>
@php
    $classifiedShortage = (float) $settlement->source_shortage_total + (float) $settlement->manual_shortage_total;
    $classifiedExcess = (float) $settlement->source_excess_total + (float) $settlement->manual_excess_total;
@endphp
<button class="no-print" onclick="window.print()">Print</button>
<div class="head">
    <div><h1>Petro PD-New Settlement</h1><strong>{{ $settlement->settlement_number }}</strong></div>
    <div style="text-align:right">PONE Shift: {{ $settlement->pone_shift_number }}<br>{{ optional($settlement->settlement_date)->format('d M Y') }}</div>
</div>
<div class="meta">
    <div><span>Operator</span><strong>{{ $settlement->operator_name }}</strong></div>
    <div><span>Status</span><strong>{{ strtoupper($settlement->status) }}</strong></div>
    <div><span>Reconciliation</span><strong>{{ strtoupper($settlement->reconciliation_status) }}</strong></div>
    <div><span>Expected</span><strong>{{ number_format((float)$settlement->expected_total,4) }}</strong></div>
    <div><span>Meter Sales</span><strong>{{ number_format((float)$settlement->meter_sales_total,4) }}</strong></div>
    <div><span>Other Sales</span><strong>{{ number_format((float)$settlement->other_sales_total,4) }}</strong></div>
    <div><span>PONE Declared</span><strong>{{ number_format((float)$settlement->source_declared_total,4) }}</strong></div>
    <div><span>Normal Collections</span><strong>{{ number_format((float)$settlement->received_total,4) }}</strong></div>
    <div><span>PONE Shortage</span><strong>{{ number_format((float)$settlement->source_shortage_total,4) }}</strong></div>
    <div><span>Manual Shortage</span><strong>{{ number_format((float)$settlement->manual_shortage_total,4) }}</strong></div>
    <div><span>PONE Excess</span><strong>{{ number_format((float)$settlement->source_excess_total,4) }}</strong></div>
    <div><span>Manual Excess</span><strong>{{ number_format((float)$settlement->manual_excess_total,4) }}</strong></div>
    <div><span>Operational Difference</span><strong>{{ number_format((float)$settlement->operational_variance_amount,4) }}</strong></div>
    <div><span>Classified Shortage</span><strong>{{ number_format($classifiedShortage,4) }}</strong></div>
    <div><span>Classified Excess</span><strong>{{ number_format($classifiedExcess,4) }}</strong></div>
    <div><span>Unresolved Variance</span><strong>{{ number_format((float)$settlement->variance_amount,4) }}</strong></div>
</div>
<p><strong>Accounting rule:</strong> shortage and excess are shown separately and are not included in normal collections.</p>

<h3>Pump & Meter Sales</h3>
<table><thead><tr><th>Pump</th><th>Product</th><th class="amount">Opening</th><th class="amount">Closing</th><th class="amount">Testing</th><th class="amount">Sold</th><th class="amount">Unit Price</th><th class="amount">Amount</th></tr></thead><tbody>
@foreach($settlement->pumps as $row)<tr><td>{{ $row->pump_id }}</td><td>{{ $row->product_id }}</td><td class="amount">{{ number_format((float)$row->opening_meter,3) }}</td><td class="amount">{{ number_format((float)$row->closing_meter,3) }}</td><td class="amount">{{ number_format((float)$row->testing_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->sold_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->unit_price,4) }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td></tr>@endforeach
</tbody></table>

<h3>Payments</h3>
<table><thead><tr><th>No</th><th>Source</th><th>Type</th><th>Reference</th><th>Status</th><th class="amount">Amount</th></tr></thead><tbody>
@foreach($settlement->payments as $row)<tr><td>{{ $row->payment_number }}</td><td>{{ $row->is_source ? 'PONE' : 'Manual' }}</td><td>{{ ucfirst(str_replace('_',' ',$row->payment_type)) }}</td><td>{{ $row->reference_no ?: '—' }}</td><td>{{ $row->status }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td></tr>@endforeach
</tbody></table>

@if($settlement->creditSales->isNotEmpty())
<h3>Credit Sales</h3>
<table><thead><tr><th>Customer</th><th>Order</th><th>Vehicle</th><th>Confirmed</th><th class="amount">Amount</th></tr></thead><tbody>
@foreach($settlement->creditSales as $row)<tr><td>{{ $row->customer_id }}</td><td>{{ $row->order_number }}</td><td>{{ $row->vehicle_number }}</td><td>{{ $row->confirmed ? 'Yes' : 'No' }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td></tr>@endforeach
</tbody></table>
@endif

@if($settlement->recoveries->isNotEmpty() || $settlement->commissions->isNotEmpty())
<h3>Recovery and Commission Records</h3>
<table><thead><tr><th>Type</th><th>Reference</th><th>Date</th><th>Status</th><th class="amount">Amount</th></tr></thead><tbody>
@foreach($settlement->recoveries as $row)<tr><td>Shortage recovery</td><td>{{ $row->recovery_number }}</td><td>{{ optional($row->recovery_date)->format('d M Y') }}</td><td>{{ $row->status }}</td><td class="amount">{{ number_format((float)$row->amount,4) }}</td></tr>@endforeach
@foreach($settlement->commissions as $row)<tr><td>Excess commission</td><td>{{ $row->commission_number }}</td><td>{{ optional($row->commission_date)->format('d M Y') }}</td><td>{{ $row->status }}</td><td class="amount">{{ number_format((float)$row->commission_amount,4) }}</td></tr>@endforeach
</tbody></table>
@endif

@if($settlement->notes)<div class="note"><strong>Notes:</strong> {!! nl2br(e($settlement->notes)) !!}</div>@endif
<p style="margin-top:30px;font-size:10px;color:#555">Generated by Petro PD-New from Pumper Dashboard-New source snapshot {{ substr((string)$settlement->source_hash,0,16) }}… on {{ now()->format('d M Y H:i:s') }}.</p>
<script>window.addEventListener('load',function(){if(new URLSearchParams(location.search).get('auto')==='1')window.print();});</script>
</body>
</html>
