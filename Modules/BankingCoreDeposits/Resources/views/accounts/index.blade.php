@extends('bankingcoredeposits::layouts.app')
@section('page-title','Deposit Accounts')
@section('module-content')
<a class="btn btn-primary" href="{{ route('banking.core-deposits.accounts.create') }}">Add Account</a>
<table class="table table-bordered table-striped"><thead><tr><th>Account No</th><th>Name</th><th>Type</th><th>Status</th><th>Ledger Balance</th><th>Action</th></tr></thead><tbody>@foreach($accounts as $a)<tr><td>{{ $a->account_no }}</td><td>{{ $a->account_name }}</td><td>{{ $a->account_type }}</td><td>{{ $a->status }}</td><td class="text-right">{{ number_format($a->ledger_balance,4) }}</td><td><a href="{{ route('banking.core-deposits.accounts.show',$a) }}" class="btn btn-xs btn-info">View</a></td></tr>@endforeach</tbody></table>{{ $accounts->links() }}
@endsection
