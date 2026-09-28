@extends('bankingcoredeposits::layouts.app')
@section('page-title','Add Deposit Account')
@section('module-content')
<form method="post" action="{{ route('banking.core-deposits.accounts.store') }}">@csrf
<div class="row"><div class="col-md-4"><label>Account Name</label><input name="account_name" class="form-control" required></div><div class="col-md-4"><label>Account Type</label><select name="account_type" class="form-control"><option value="savings">Savings</option><option value="current">Current</option><option value="fixed_deposit">Fixed Deposit</option></select></div><div class="col-md-4"><label>Product</label><select name="product_id" class="form-control">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div></div>
<div class="row mt-2"><div class="col-md-4"><label>Initial Balance</label><input name="ledger_balance" class="form-control" value="0.0000"></div><div class="col-md-4"><label>Ownership</label><select name="ownership_type" class="form-control"><option>single</option><option>joint</option><option>minor</option><option>corporate</option><option>group</option></select></div><div class="col-md-4"><label>Opened On</label><input type="date" name="opened_on" class="form-control" value="{{ now()->toDateString() }}"></div></div>
<button class="btn btn-success mt-3">Save</button></form>
@endsection
