@extends('pumperdashboardnew::layouts.operator')
@section('title','Closed Pumps Statement')
@section('body_attributes','data-auto-print="1"')
@section('pone_content')
<div class="pone-print-toolbar pone-no-print"><button class="pone-btn pone-btn-primary" data-print>Print</button><a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.pumps.index') }}">Back</a></div>
<div class="pone-receipt pone-receipt-a4">
<div class="pone-receipt-head"><h1>{{ $business->name ?? 'Business' }}</h1><p>{{ $location->name ?? '' }}</p><h2>Closed Pumps Statement</h2><p>Shift {{ $shift->shift_number }} · {{ optional($shift->opened_at)->format('Y-m-d H:i') }} – {{ optional($shift->closed_at)->format('Y-m-d H:i') ?: 'Open' }}</p></div>
<table class="pone-receipt-table"><thead><tr><th>Pump</th><th class="right">Opening</th><th class="right">Closing</th><th class="right">Testing</th><th class="right">Sold Qty</th><th class="right">Unit Price</th><th class="right">Amount</th><th>Status</th></tr></thead><tbody>@foreach($shift->assignments as $a) @php($pump=$pumps->get($a->pump_id))<tr><td>{{ $pump->pump_name ?? $pump->pump_no ?? ('Pump '.$a->pump_id) }}</td><td class="right">{{ number_format((float)$a->opening_meter,3) }}</td><td class="right">{{ $a->closing_meter===null?'-':number_format((float)$a->closing_meter,3) }}</td><td class="right">{{ number_format((float)$a->testing_quantity,3) }}</td><td class="right">{{ number_format((float)$a->sold_quantity,3) }}</td><td class="right">{{ number_format((float)$a->unit_price,4) }}</td><td class="right">{{ number_format((float)$a->amount,4) }}</td><td>{{ ucfirst($a->status) }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="4" class="right">Totals</th><th class="right">{{ number_format((float)$shift->assignments->sum('sold_quantity'),3) }}</th><th></th><th class="right">{{ number_format((float)$shift->assignments->sum('amount'),4) }}</th><th></th></tr></tfoot></table>
<div class="pone-signatures"><div class="pone-signature">Pump Operator</div><div class="pone-signature">Checked By</div></div>
<div class="pone-receipt-foot">Generated {{ now()->format('Y-m-d H:i:s') }} · Pumper Dashboard-New</div>
</div>
@endsection
