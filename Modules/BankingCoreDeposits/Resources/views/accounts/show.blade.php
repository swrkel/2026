@extends('bankingcoredeposits::layouts.app')
@section('page-title','Deposit Account')
@section('module-content')
<div class="bkg-panel"><h4>{{ $account->account_no }} - {{ $account->account_name }}</h4><p>Status: {{ $account->status }}</p><p>Balance: {{ number_format($account->ledger_balance,4) }}</p></div>
@endsection
