@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header', ['title' => 'Transfer Returns', 'subtitle' => 'Create return notes for received transfers.'])
<div class="stn-card"><table class="table table-bordered table-striped"><thead><tr><th>Transfer No</th><th>Status</th><th>Return Reason</th><th>Action</th></tr></thead><tbody>@forelse($transfers as $transfer)<tr><td>{{ $transfer->transfer_no }}</td><td>{{ ucfirst($transfer->status) }}</td><td colspan="2"><form class="form-inline" method="post" action="{{ route('stock-transfer-new.returns.store',$transfer) }}">@csrf <input name="reason" class="form-control" placeholder="Reason for return"><button class="btn btn-warning">Create Return</button></form></td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No received transfers available for return.</td></tr>@endforelse</tbody></table>{{ method_exists($transfers,'links') ? $transfers->links() : '' }}</div>
@endsection
