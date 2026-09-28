@extends('restaurantnew::layouts.app')
@section('title', __('corporate.new_account'))
@section('content')
<div class="restnew-card restnew-form-card">
    <h3>{{ __('corporate.new_account') }}</h3>
    <form method="POST" action="{{ route('restaurant-new.corporate.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-4"><label>Account Code</label><input name="account_code" class="form-control" required></div>
            <div class="col-md-8"><label>Company Name</label><input name="company_name" class="form-control" required></div>
            <div class="col-md-4"><label>Contact Person</label><input name="contact_person" class="form-control"></div>
            <div class="col-md-4"><label>Mobile</label><input name="mobile" class="form-control"></div>
            <div class="col-md-4"><label>Email</label><input name="email" type="email" class="form-control"></div>
            <div class="col-md-4"><label>{{ __('corporate.credit_limit') }}</label><input name="credit_limit" type="number" step="0.0001" class="form-control"></div>
            <div class="col-md-4"><label>{{ __('corporate.credit_days') }}</label><input name="credit_days" type="number" class="form-control"></div>
        </div>
        <button class="btn btn-success mt-3">Save</button>
    </form>
</div>
@endsection
