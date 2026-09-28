@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Stock Transfer-New Variance Report','subtitle'=>'Short and excess quantities identified during receiving'])
@include('stocktransfernew::partials.list_toolbar',['export_route'=>route('stock-transfer-new.reports.export','variance')])
<div class="stn-card"><table class="table table-bordered table-striped"><thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th>Product</th><th>Requested</th><th>Dispatched</th><th>Received</th><th>Short</th><th>Excess</th><th>Remarks</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->transfer_no }}</td><td>{{ $row->transfer_date }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->product_id }} / {{ $row->variation_id }}</td><td class="text-right">{{ number_format($row->qty_requested,4) }}</td><td class="text-right">{{ number_format($row->qty_dispatched,4) }}</td><td class="text-right">{{ number_format($row->qty_received,4) }}</td><td class="text-right">{{ number_format($row->short_qty,4) }}</td><td class="text-right">{{ number_format($row->excess_qty,4) }}</td><td>{{ $row->remarks }}</td></tr>@empty<tr><td colspan="10" class="text-center">No variance found.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div>
@endsection
