@extends('layouts.app')
@section('title', 'Deposit Transactions')
@section('content')
<section class="content-header no-print"><h1>Deposit Transactions</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Transactions</h3><div class="box-tools"><a href="{{ route('deposits.transactions.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add</a></div></div>
<div class="box-body">
<form method="GET" class="row" style="margin-bottom:10px;">
    <div class="col-md-3 form-group"><select name="deposit_account_id" class="form-control"><option value="">All Accounts</option>@foreach(($accounts ?? collect()) as $id=>$no)<option value="{{ $id }}" {{ request('deposit_account_id')==$id?'selected':'' }}>{{ $no }}</option>@endforeach</select></div>
    <div class="col-md-2 form-group"><select name="type" class="form-control"><option value="">All Types</option>@foreach(['deposit','withdrawal','interest','charge','penalty','closure'] as $type)<option value="{{ $type }}" {{ request('type')==$type?'selected':'' }}>{{ ucfirst($type) }}</option>@endforeach</select></div>
    <div class="col-md-2 form-group"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
    <div class="col-md-2 form-group"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    <div class="col-md-2 form-group"><button class="btn btn-primary"><i class="fa fa-search"></i> Filter</button></div>
</form>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>No</th><th>Account</th><th>Type</th><th>Amount</th><th>Balance</th><th>Reference</th></tr></thead><tbody>
@forelse($transactions as $tx)<tr><td>{{ $tx->transaction_date }}</td><td>{{ $tx->transaction_no }}</td><td>{{ optional($tx->account)->account_no }}</td><td>{{ ucfirst($tx->type) }}</td><td>{{ number_format($tx->amount, 2) }}</td><td>{{ number_format($tx->balance_after, 2) }}</td><td>{{ $tx->reference_no }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records found</td></tr>@endforelse
</tbody></table>@if(method_exists($transactions, 'appends')) {{ $transactions->appends(request()->query())->links() }} @endif</div></div></div>
</section>
@endsection
