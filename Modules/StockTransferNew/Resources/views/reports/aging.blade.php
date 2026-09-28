@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Stock Transfer-New Aging Report','subtitle'=>'Pending, approved and in-transit transfers by age'])
@include('stocktransfernew::partials.list_toolbar',['export_route'=>route('stock-transfer-new.reports.export','aging')])
<div class="stn-card"><table class="table table-bordered table-striped"><thead><tr><th>Transfer No</th><th>Date</th><th>Status</th><th>From</th><th>To</th><th>Age Days</th><th>Action</th></tr></thead><tbody>
@forelse($transfers as $transfer)<tr><td>{{ $transfer->transfer_no }}</td><td>{{ optional($transfer->transfer_date)->format('Y-m-d') }}</td><td>{{ ucfirst($transfer->status) }}</td><td>{{ $transfer->from_location_id }}</td><td>{{ $transfer->to_location_id }}</td><td>{{ $transfer->created_at ? $transfer->created_at->diffInDays(now()) : 0 }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('stock-transfer-new.transfers.show',$transfer) }}">View</a></td></tr>@empty<tr><td colspan="7" class="text-center">No pending aging records.</td></tr>@endforelse
</tbody></table>{{ $transfers->links() }}</div>
@endsection
