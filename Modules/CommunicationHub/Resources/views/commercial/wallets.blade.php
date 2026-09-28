@extends('communicationhub::layout')
@section('communicationhub_title', 'Business SMS Wallets')
@section('communicationhub_content')
<div class="box"><div class="box-header"><h3 class="box-title">Wallet Balances</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tr><th>ID</th><th>Client ID</th><th>Available</th><th>Reserved</th><th>Total Purchased</th><th>Total Used</th><th>Status</th></tr>@forelse($wallets as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->client_id }}</td><td>{{ $row->available_credits }}</td><td>{{ $row->reserved_credits }}</td><td>{{ $row->total_purchased }}</td><td>{{ $row->total_used }}</td><td>{{ $row->status }}</td></tr>@empty<tr><td colspan="7">No wallets yet. Add SMS clients or do a credit refill.</td></tr>@endforelse</table></div></div>
@endsection
