@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Operational Queue','subtitle'=>'All active stock transfers that need action or monitoring'])
<div class="stn-card"><div class="stn-card-body">
<form method="get" class="stn-filter-row">
    <input type="date" name="from_date" value="{{ request('from_date') }}">
    <input type="date" name="to_date" value="{{ request('to_date') }}">
    <select name="status"><option value="">All Active Statuses</option>@foreach(['draft','pending','approved','in_transit'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select>
    <button class="stn-btn stn-btn-primary">Filter</button>
    <a class="stn-btn" href="{{ route('stock-transfer-new.command-center.queue') }}">Reset</a>
</form>
</div></div>
<div class="stn-card"><div class="stn-card-body table-responsive"><table class="table table-bordered stn-table"><thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th>From</th><th>To</th><th>Lines</th><th>Last Updated</th><th>Action</th></tr></thead><tbody>
@forelse($transfers as $transfer)<tr><td><strong>{{ $transfer->transfer_no }}</strong></td><td>{{ optional($transfer->transfer_date)->format('Y-m-d') }}</td><td><span class="stn-status stn-status-{{ $transfer->status }}">{{ ucwords(str_replace('_',' ',$transfer->status)) }}</span></td><td>{{ $transfer->from_location_id }} / {{ $transfer->from_store_id }}</td><td>{{ $transfer->to_location_id }} / {{ $transfer->to_store_id }}</td><td>{{ $transfer->lines_count }}</td><td>{{ $transfer->updated_at }}</td><td><a class="stn-btn stn-btn-sm" href="{{ route('stock-transfer-new.transfers.show',$transfer->id) }}">View</a></td></tr>@empty<tr><td colspan="8" class="text-center stn-muted">No active transfers found.</td></tr>@endforelse
</tbody></table>{{ $transfers->links() }}</div></div>
@endsection
