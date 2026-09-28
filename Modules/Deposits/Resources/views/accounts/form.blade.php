@extends('layouts.app')
@section('title', 'Deposit Account')
@section('content')
<section class="content-header no-print"><h1>Deposit Account</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
@php
    $nominee = old('nominee_name') ? null : optional($account->parties ?? collect())->where('party_type','nominee')->first();
    $beneficiary = old('beneficiary_name') ? null : optional($account->parties ?? collect())->where('party_type','beneficiary')->first();
@endphp
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Account Details</h3></div>
    <div class="box-body"><div class="row">
        <div class="col-md-3 form-group"><label>Account No *</label><input name="account_no" class="form-control" value="{{ old('account_no', $account->account_no) }}" required></div>
        <div class="col-md-3 form-group"><label>Product</label><select name="deposit_product_id" id="deposit_product_id" class="form-control"><option value="">Select</option>@foreach($products as $product)<option value="{{ $product->id }}" data-rate="{{ $product->interest_rate }}" data-term="{{ $product->term_months }}" {{ old('deposit_product_id', $account->deposit_product_id)==$product->id?'selected':'' }}>{{ $product->name }}</option>@endforeach</select></div>
        <div class="col-md-3 form-group"><label>Banking Customer ID</label><input type="number" name="banking_customer_id" class="form-control" value="{{ old('banking_customer_id', $account->banking_customer_id) }}"></div>
        <div class="col-md-3 form-group"><label>Location</label><select name="location_id" class="form-control"><option value="">Select</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" {{ old('location_id', $account->location_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
        <div class="col-md-6 form-group"><label>Customer Name</label><input name="customer_name" class="form-control" value="{{ old('customer_name', $account->customer_name) }}"></div>
        <div class="col-md-3 form-group"><label>Principal Amount *</label><input type="number" step="0.01" name="principal_amount" class="form-control" value="{{ old('principal_amount', $account->principal_amount) }}" required></div>
        <div class="col-md-3 form-group"><label>Interest Rate %</label><input type="number" step="0.0001" name="interest_rate" id="interest_rate" class="form-control" value="{{ old('interest_rate', $account->interest_rate) }}"></div>
        <div class="col-md-3 form-group"><label>Opened On</label><input type="date" name="opened_on" id="opened_on" class="form-control" value="{{ old('opened_on', optional($account->opened_on)->format('Y-m-d') ?: $account->opened_on) }}"></div>
        <div class="col-md-3 form-group"><label>Maturity On</label><input type="date" name="maturity_on" id="maturity_on" class="form-control" value="{{ old('maturity_on', optional($account->maturity_on)->format('Y-m-d') ?: $account->maturity_on) }}"></div>
        <div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" {{ old('status', $account->status)=='active'?'selected':'' }}>Active</option><option value="matured" {{ old('status', $account->status)=='matured'?'selected':'' }}>Matured</option><option value="closed" {{ old('status', $account->status)=='closed'?'selected':'' }}>Closed</option><option value="renewed" {{ old('status', $account->status)=='renewed'?'selected':'' }}>Renewed</option></select></div>
        <div class="col-md-3 form-group"><label>Auto Renew</label><select name="auto_renew" class="form-control"><option value="0" {{ old('auto_renew', $account->auto_renew)==0?'selected':'' }}>No</option><option value="1" {{ old('auto_renew', $account->auto_renew)==1?'selected':'' }}>Yes</option></select></div>
    </div></div>
</div>

<div class="box box-info">
    <div class="box-header with-border"><h3 class="box-title">Nominee</h3></div>
    <div class="box-body"><div class="row">
        <div class="col-md-3 form-group"><label>Name</label><input name="nominee_name" class="form-control" value="{{ old('nominee_name', optional($nominee)->name) }}"></div>
        <div class="col-md-3 form-group"><label>Relationship</label><input name="nominee_relationship" class="form-control" value="{{ old('nominee_relationship', optional($nominee)->relationship) }}"></div>
        <div class="col-md-2 form-group"><label>NIC</label><input name="nominee_nic_no" class="form-control" value="{{ old('nominee_nic_no', optional($nominee)->nic_no) }}"></div>
        <div class="col-md-2 form-group"><label>Mobile</label><input name="nominee_mobile" class="form-control" value="{{ old('nominee_mobile', optional($nominee)->mobile) }}"></div>
        <div class="col-md-2 form-group"><label>Share %</label><input type="number" step="0.01" name="nominee_share_percentage" class="form-control" value="{{ old('nominee_share_percentage', optional($nominee)->share_percentage ?: 100) }}"></div>
        <div class="col-md-12 form-group"><label>Address</label><textarea name="nominee_address" class="form-control" rows="2">{{ old('nominee_address', optional($nominee)->address) }}</textarea></div>
    </div></div>
</div>

<div class="box box-info">
    <div class="box-header with-border"><h3 class="box-title">Beneficiary</h3></div>
    <div class="box-body"><div class="row">
        <div class="col-md-3 form-group"><label>Name</label><input name="beneficiary_name" class="form-control" value="{{ old('beneficiary_name', optional($beneficiary)->name) }}"></div>
        <div class="col-md-3 form-group"><label>Relationship</label><input name="beneficiary_relationship" class="form-control" value="{{ old('beneficiary_relationship', optional($beneficiary)->relationship) }}"></div>
        <div class="col-md-2 form-group"><label>NIC</label><input name="beneficiary_nic_no" class="form-control" value="{{ old('beneficiary_nic_no', optional($beneficiary)->nic_no) }}"></div>
        <div class="col-md-2 form-group"><label>Mobile</label><input name="beneficiary_mobile" class="form-control" value="{{ old('beneficiary_mobile', optional($beneficiary)->mobile) }}"></div>
        <div class="col-md-2 form-group"><label>Share %</label><input type="number" step="0.01" name="beneficiary_share_percentage" class="form-control" value="{{ old('beneficiary_share_percentage', optional($beneficiary)->share_percentage ?: 100) }}"></div>
        <div class="col-md-12 form-group"><label>Address</label><textarea name="beneficiary_address" class="form-control" rows="2">{{ old('beneficiary_address', optional($beneficiary)->address) }}</textarea></div>
    </div></div>
</div>

<div class="box box-primary"><div class="box-body"><div class="row">
    <div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $account->notes) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('deposits.accounts.index') }}" class="btn btn-default">Cancel</a></div></div>
</form>
</section>
@endsection
