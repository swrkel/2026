@extends('bankingmicrofinance::layouts.app')
@section('page-title','Add Collection')
@section('module-content')
<form method="post" action="{{ route('banking.microfinance.collections.store') }}">@csrf
<div class="box"><div class="box-body row">
<div class="form-group col-md-4"><label>Loan</label><select name="loan_id" class="form-control" required>@foreach($loans as $loan)<option value="{{ $loan->id }}">{{ $loan->loan_no }} - {{ number_format($loan->total_payable,4) }}</option>@endforeach</select></div>
<div class="form-group col-md-4"><label>Date</label><input type="date" name="collection_date" class="form-control" value="{{ old('collection_date',$collection->collection_date) }}" required></div>
<div class="form-group col-md-4"><label>Payment Method</label><input name="payment_method" class="form-control" value="{{ old('payment_method',$collection->payment_method) }}"></div>
<div class="form-group col-md-3"><label>Principal Paid</label><input name="principal_paid" class="form-control" value="0"></div>
<div class="form-group col-md-3"><label>Interest Paid</label><input name="interest_paid" class="form-control" value="0"></div>
<div class="form-group col-md-3"><label>Fee Paid</label><input name="fee_paid" class="form-control" value="0"></div>
<div class="form-group col-md-3"><label>Saving Paid</label><input name="saving_paid" class="form-control" value="0"></div>
<div class="form-group col-md-12"><label>Note</label><textarea name="note" class="form-control"></textarea></div>
</div><div class="box-footer"><button class="btn btn-primary">Save Collection</button></div></div></form>
@endsection
