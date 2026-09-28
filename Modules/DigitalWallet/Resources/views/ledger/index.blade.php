@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Wallet Ledger')
@section('digitalwallet-content')
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Wallet ID</th><th>Type</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Description</th></tr></thead><tbody>
@forelse($ledger as $entry)
<tr><td>{{ $entry->entry_date }}</td><td>{{ $entry->wallet_id }}</td><td>{{ ucfirst($entry->entry_type) }}</td><td>{{ number_format($entry->debit, 2) }}</td><td>{{ number_format($entry->credit, 2) }}</td><td>{{ number_format($entry->balance, 2) }}</td><td>{{ $entry->description }}</td></tr>
@empty<tr><td colspan="7" class="text-center">No ledger entries found.</td></tr>@endforelse
</tbody></table>{{ $ledger->links() }}</div></div>
@endsection
