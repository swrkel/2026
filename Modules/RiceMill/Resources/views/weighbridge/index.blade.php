@extends('RiceMill::layout')
@section('rcm-title','Weighbridge Entries')
@section('rcm-actions')<a class="rcm-btn" href="{{ route('rice-mill.receipts.create') }}">+ New Paddy Receiving</a>@endsection
@section('rcm-content')
<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-weighbridge-table','exportName'=>'rice-mill-weighbridge','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'entries'])
<div class="rcm-table-wrap"><table id="rcm-weighbridge-table" class="rcm-table rcm-managed-table"><thead><tr><th>Entry</th><th>Paddy</th><th>Time</th><th>Vehicle</th><th>Supplier</th><th class="rcm-num">Gross</th><th class="rcm-num">Tare</th><th class="rcm-num">Net</th><th>Receipt</th></tr></thead><tbody>
@forelse($rows as $r)
<tr>
    <td>{{ $r->entry_no }}</td>
    <td>{{ $r->paddy_name ? (($r->paddy_code ? $r->paddy_code.' - ' : '').$r->paddy_name) : '-' }}</td>
    <td>{{ $r->weighed_at }}</td><td>{{ $r->vehicle_no }}</td><td>{{ $r->supplier_id }}</td>
    <td class="rcm-num">{{ number_format($r->gross_weight,$rcmQuantityPrecision) }}</td><td class="rcm-num">{{ number_format($r->tare_weight,$rcmQuantityPrecision) }}</td><td class="rcm-num">{{ number_format($r->net_weight,$rcmQuantityPrecision) }}</td><td>{{ $r->receipt_id }}</td>
</tr>
@empty<tr data-rcm-empty-row><td colspan="9" class="rcm-muted">No weighbridge entries found.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}</div>
@endsection
