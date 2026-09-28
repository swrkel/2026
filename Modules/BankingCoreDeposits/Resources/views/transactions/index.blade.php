@extends('bankingcoredeposits::layouts.app')
@section('page-title','Deposit Transactions')
@section('module-content')
<a class="btn btn-primary" href="{{ route('banking.core-deposits.transactions.create') }}">Add Transaction</a><table class="table table-bordered"><thead><tr><th>No</th><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th></tr></thead><tbody>@foreach($transactions as $t)<tr><td>{{ $t->transaction_no }}</td><td>{{ $t->transaction_date }}</td><td>{{ $t->type }}</td><td class="text-right">{{ number_format($t->amount,4) }}</td><td class="text-right">{{ number_format($t->balance_after,4) }}</td></tr>@endforeach</tbody></table>{{ $transactions->links() }}
@endsection
