@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Stock Transfer Details','subtitle'=>$transfer->transfer_no.' / '.$transfer->status])
<div class="stn-card">
 <div class="stn-action-row">
  <a class="stn-btn" href="{{ route('stock-transfer-new.transfers.index') }}">Back</a>
  <a class="stn-btn" href="{{ route('stock-transfer-new.transfers.dispatch-note',$transfer) }}" target="_blank">Dispatch Note</a>
  <a class="stn-btn" href="{{ route('stock-transfer-new.transfers.receive-note',$transfer) }}" target="_blank">Receive Note</a>
  @if(in_array($transfer->status,['draft','rejected']))<a class="stn-btn primary" href="{{ route('stock-transfer-new.transfers.edit',$transfer) }}">Edit</a><form method="post" action="{{ route('stock-transfer-new.transfers.submit',$transfer) }}">@csrf<button class="stn-btn success">Submit</button></form>@endif
 </div>
 <div class="stn-grid-4 stn-summary">
  <div><b>Date</b><span>{{ $transfer->transfer_date }}</span></div><div><b>Status</b><span>{{ strtoupper($transfer->status) }}</span></div><div><b>Vehicle</b><span>{{ $transfer->vehicle_no ?: '-' }}</span></div><div><b>Driver</b><span>{{ $transfer->driver_name ?: '-' }}</span></div>
 </div>
 <p><b>Reason:</b> {{ $transfer->reason ?: '-' }}</p><p><b>Remarks:</b> {{ $transfer->remarks ?: '-' }}</p>
 <table class="stn-table"><thead><tr><th>#</th><th>Product</th><th>Batch</th><th>Requested</th><th>Dispatched</th><th>Received</th><th>Short</th><th>Excess</th><th>Unit Cost</th><th>Total</th></tr></thead><tbody>
 @foreach($transfer->lines as $line)<tr><td>{{ $loop->iteration }}</td><td>{{ $line->product_id }}</td><td>{{ $line->batch_no ?: '-' }}</td><td>{{ number_format($line->qty_requested,4) }}</td><td>{{ number_format($line->qty_dispatched,4) }}</td><td>{{ number_format($line->qty_received,4) }}</td><td>{{ number_format($line->short_qty,4) }}</td><td>{{ number_format($line->excess_qty,4) }}</td><td>{{ number_format($line->unit_cost,4) }}</td><td>{{ number_format($line->line_total,4) }}</td></tr>@endforeach
 </tbody></table>
 <h4>Audit Timeline</h4><ul class="stn-timeline">@foreach($transfer->audits as $audit)<li><b>{{ $audit->action }}</b> - {{ $audit->note }} <small>{{ $audit->created_at }}</small></li>@endforeach</ul>
</div>
@endsection
