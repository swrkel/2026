@extends('bankingmicrofinance::layouts.app')
@section('page-title','Add Microfinance Loan')
@section('module-content')
<form method="post" action="{{ route('banking.microfinance.loans.store') }}">@csrf
<div class="box"><div class="box-body row">
<div class="form-group col-md-4"><label>Member</label><select name="member_id" class="form-control" required>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_no }} - {{ $member->name }}</option>@endforeach</select></div>
<div class="form-group col-md-4"><label>Product</label><select name="loan_product_id" class="form-control"><option value="">Manual Rate</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->code }} - {{ $product->name }}</option>@endforeach</select></div>
<div class="form-group col-md-4"><label>Application Date</label><input type="date" name="application_date" class="form-control" value="{{ old('application_date',$loan->application_date) }}"></div>
<div class="form-group col-md-4"><label>Principal Amount</label><input name="principal_amount" class="form-control" value="{{ old('principal_amount',$loan->principal_amount) }}" required></div>
<div class="form-group col-md-4"><label>Term Weeks</label><input name="term_weeks" class="form-control" value="{{ old('term_weeks',$loan->term_weeks ?: 24) }}" required></div>
<div class="form-group col-md-4"><label>Manual Annual Rate %</label><input name="annual_interest_rate" class="form-control" value="{{ old('annual_interest_rate',0) }}"></div>
<div class="form-group col-md-12"><label>Purpose</label><textarea name="purpose" class="form-control">{{ old('purpose',$loan->purpose) }}</textarea></div>
</div><div class="box-footer"><button class="btn btn-primary">Create Loan & Schedule</button></div></div></form>
@endsection
