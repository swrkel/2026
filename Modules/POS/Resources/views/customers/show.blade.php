@extends('pos::layouts.app')
@section('pos_content')
<div class="row">
  <div class="col-md-8"><div class="card"><div class="card-body"><h3 style="margin-top:0;">{{ $customer->name }} <small>({{ $customer->customer_code }})</small></h3><p>{{ $customer->mobile }} {{ $customer->email ? ' | '.$customer->email : '' }}</p><p>{{ $customer->address }}</p></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><strong>Current Balance</strong><h2>{{ number_format($customer->balance_amount,2) }}</h2><a class="btn btn-primary" href="{{ route('pos.customers.statement',$customer->id) }}">Print Statement</a></div></div></div>
</div>
<div class="card">
  <div class="card-header"><strong>Receive Payment</strong></div>
  <div class="card-body"><form method="post" action="{{ route('pos.customers.payment',$customer->id) }}" class="row">@csrf
    <div class="col-md-3"><input class="form-control" name="amount" type="number" step="0.0001" required placeholder="Amount"></div>
    <div class="col-md-3"><input class="form-control" name="reference_no" placeholder="Reference No"></div>
    <div class="col-md-4"><input class="form-control" name="note" placeholder="Note"></div>
    <div class="col-md-2"><button class="btn btn-success">Save Payment</button></div>
  </form></div>
</div>
@include('pos::customers._ledger_table')
@endsection
