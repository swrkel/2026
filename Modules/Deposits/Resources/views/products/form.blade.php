@extends('layouts.app')
@section('title', 'Deposit Product')
@section('content')
<section class="content-header no-print"><h1>Deposit Product</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Product Details</h3></div>
    <div class="box-body"><div class="row">
        <div class="col-md-4 form-group"><label>Name *</label><input name="name" class="form-control" value="{{ old('name', $product->name) }}" required></div>
        <div class="col-md-2 form-group"><label>Code</label><input name="code" class="form-control" value="{{ old('code', $product->code) }}"></div>
        <div class="col-md-2 form-group"><label>Account Prefix</label><input name="account_prefix" class="form-control" value="{{ old('account_prefix', $product->account_prefix) }}"></div>
        <div class="col-md-2 form-group"><label>Type</label><select name="type" class="form-control"><option value="fixed_deposit" {{ old('type', $product->type)=='fixed_deposit'?'selected':'' }}>Fixed Deposit</option><option value="recurring_deposit" {{ old('type', $product->type)=='recurring_deposit'?'selected':'' }}>Recurring Deposit</option><option value="savings" {{ old('type', $product->type)=='savings'?'selected':'' }}>Savings</option><option value="call_deposit" {{ old('type', $product->type)=='call_deposit'?'selected':'' }}>Call Deposit</option></select></div>
        <div class="col-md-2 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" {{ old('status', $product->status)=='active'?'selected':'' }}>Active</option><option value="inactive" {{ old('status', $product->status)=='inactive'?'selected':'' }}>Inactive</option></select></div>
    </div></div>
</div>
<div class="box box-info">
    <div class="box-header with-border"><h3 class="box-title">Interest & Term Rules</h3></div>
    <div class="box-body"><div class="row">
        <div class="col-md-3 form-group"><label>Interest Rate %</label><input type="number" step="0.0001" name="interest_rate" class="form-control" value="{{ old('interest_rate', $product->interest_rate) }}"></div>
        <div class="col-md-3 form-group"><label>Term Months</label><input type="number" name="term_months" class="form-control" value="{{ old('term_months', $product->term_months) }}"></div>
        <div class="col-md-3 form-group"><label>Interest Frequency</label><select name="interest_frequency" class="form-control"><option value="monthly" {{ old('interest_frequency', $product->interest_frequency)=='monthly'?'selected':'' }}>Monthly</option><option value="quarterly" {{ old('interest_frequency', $product->interest_frequency)=='quarterly'?'selected':'' }}>Quarterly</option><option value="maturity" {{ old('interest_frequency', $product->interest_frequency)=='maturity'?'selected':'' }}>At Maturity</option></select></div>
        <div class="col-md-3 form-group"><label>Interest Method</label><select name="interest_method" class="form-control"><option value="simple" {{ old('interest_method', $product->interest_method)=='simple'?'selected':'' }}>Simple</option><option value="compound" {{ old('interest_method', $product->interest_method)=='compound'?'selected':'' }}>Compound</option><option value="flat" {{ old('interest_method', $product->interest_method)=='flat'?'selected':'' }}>Flat</option></select></div>
        <div class="col-md-3 form-group"><label>Minimum Amount</label><input type="number" step="0.01" name="minimum_amount" class="form-control" value="{{ old('minimum_amount', $product->minimum_amount) }}"></div>
        <div class="col-md-3 form-group"><label>Maximum Amount</label><input type="number" step="0.01" name="maximum_amount" class="form-control" value="{{ old('maximum_amount', $product->maximum_amount) }}"></div>
        <div class="col-md-3 form-group"><label>Renewal Policy</label><select name="renewal_policy" class="form-control"><option value="manual" {{ old('renewal_policy', $product->renewal_policy)=='manual'?'selected':'' }}>Manual</option><option value="auto" {{ old('renewal_policy', $product->renewal_policy)=='auto'?'selected':'' }}>Auto Renewal</option><option value="none" {{ old('renewal_policy', $product->renewal_policy)=='none'?'selected':'' }}>No Renewal</option></select></div>
        <div class="col-md-3 form-group"><label>Penalty Rate %</label><input type="number" step="0.0001" name="penalty_rate" class="form-control" value="{{ old('penalty_rate', $product->penalty_rate) }}"></div>
    </div></div>
</div>
<div class="box box-warning">
    <div class="box-header with-border"><h3 class="box-title">Controls</h3></div>
    <div class="box-body"><div class="row">
        <div class="col-md-3 form-group"><label>Premature Closure</label><select name="premature_closure_allowed" class="form-control"><option value="1" {{ old('premature_closure_allowed', $product->premature_closure_allowed)==1?'selected':'' }}>Allowed</option><option value="0" {{ old('premature_closure_allowed', $product->premature_closure_allowed)==0?'selected':'' }}>Not Allowed</option></select></div>
        <div class="col-md-3 form-group"><label>Nominee Required</label><select name="require_nominee" class="form-control"><option value="0" {{ old('require_nominee', $product->require_nominee)==0?'selected':'' }}>No</option><option value="1" {{ old('require_nominee', $product->require_nominee)==1?'selected':'' }}>Yes</option></select></div>
        <div class="col-md-3 form-group"><label>Beneficiary Required</label><select name="require_beneficiary" class="form-control"><option value="0" {{ old('require_beneficiary', $product->require_beneficiary)==0?'selected':'' }}>No</option><option value="1" {{ old('require_beneficiary', $product->require_beneficiary)==1?'selected':'' }}>Yes</option></select></div>
        <div class="col-md-12 form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea></div>
    </div></div>
    <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('deposits.products.index') }}" class="btn btn-default">Cancel</a></div>
</div>
</form>
</section>
@endsection
