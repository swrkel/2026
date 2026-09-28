@extends('communicationhub::layout')
@section('communicationhub_title', 'Credit Transactions')
@section('communicationhub_content')
<div class="box"><div class="box-header"><h3 class="box-title">Wallet Transaction History</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tr><th>ID</th><th>Client</th><th>Type</th><th>Credits</th><th>Opening</th><th>Closing</th><th>Amount</th><th>Profit</th><th>Date</th></tr>@forelse($transactions as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->client_id }}</td><td>{{ $row->type }}</td><td>{{ $row->credits }}</td><td>{{ $row->opening_balance }}</td><td>{{ $row->closing_balance }}</td><td>{{ number_format((float)$row->amount,2) }}</td><td>{{ number_format((float)$row->profit_amount,2) }}</td><td>{{ $row->created_at }}</td></tr>@empty<tr><td colspan="9">No transactions yet</td></tr>@endforelse</table></div></div>
@endsection
