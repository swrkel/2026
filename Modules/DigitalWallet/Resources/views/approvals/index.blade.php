@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Wallet Approvals')
@section('digitalwallet-content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Approval Requests</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Type</th><th>Wallet</th><th>Amount</th><th>Status</th><th>Requested By</th><th>Action</th></tr></thead><tbody>
@forelse($approvals as $approval)<tr><td>{{ $approval->approval_no }}</td><td>{{ $approval->request_type }}</td><td>{{ $approval->wallet_id }}</td><td>{{ number_format($approval->amount, 2) }} {{ $approval->currency }}</td><td>{{ ucfirst($approval->status) }}</td><td>{{ $approval->requested_by }}</td><td>@if($approval->status == 'pending')<form method="POST" action="{{ route('digitalwallet.approvals.approve', $approval) }}" style="display:inline">@csrf<button class="btn btn-xs btn-success">Approve</button></form> <form method="POST" action="{{ route('digitalwallet.approvals.reject', $approval) }}" style="display:inline">@csrf<button class="btn btn-xs btn-danger">Reject</button></form>@endif</td></tr>@empty<tr><td colspan="7" class="text-center">No approval requests found.</td></tr>@endforelse
</tbody></table>{{ $approvals->links() }}</div></div>
@endsection
