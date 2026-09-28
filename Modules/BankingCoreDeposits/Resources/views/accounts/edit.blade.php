@extends('bankingcoredeposits::layouts.app')
@section('page-title','Edit Deposit Account')
@section('module-content')
<form method="post" action="{{ route('banking.core-deposits.accounts.update',$account) }}">@csrf @method('PUT')<label>Account Name</label><input name="account_name" class="form-control" value="{{ $account->account_name }}"><button class="btn btn-success mt-3">Update</button></form>
@endsection
