@extends('layouts.app')
@section('title', 'Deposit Transaction')
@section('content')
<section class="content-header no-print"><h1>Deposit Transaction</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<form method="POST" action="{{ route('deposits.transactions.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Transaction No</label><input name="transaction_no" class="form-control" value="{{ old('transaction_no', $transaction_no) }}"></div>
<div class="col-md-3 form-group"><label>Account *</label><select name="deposit_account_id" class="form-control" required><option value="">Select</option>@foreach($accounts as $id=>$no)<option value="{{ $id }}">{{ $no }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Type</label><select name="type" class="form-control"><option value="deposit">Deposit</option><option value="interest">Interest</option><option value="withdrawal">Withdrawal</option><option value="charge">Charge</option><option value="closure">Closure</option></select></div>
<div class="col-md-3 form-group"><label>Date</label><input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', date('Y-m-d')) }}"></div>
<div class="col-md-3 form-group"><label>Amount *</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Payment Method</label><input name="payment_method" class="form-control"></div>
<div class="col-md-3 form-group"><label>Reference No</label><input name="reference_no" class="form-control"></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('deposits.transactions.index') }}" class="btn btn-default">Cancel</a></div></div>
</form>
</section>
@endsection
