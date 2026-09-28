@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::gift_voucher.issue_new'))

@section('content')
<div class="restnew-page">
    <div class="restnew-toolbar"><h3>{{ __('restaurantnew::gift_voucher.issue_new') }}</h3></div>
    <form method="POST" action="{{ route('restaurant-new.gift-vouchers.store') }}" class="restnew-card restnew-form">
        @csrf
        <div class="row">
            <div class="col-md-4"><label>{{ __('restaurantnew::gift_voucher.voucher_no') }}</label><input name="voucher_no" class="form-control" required></div>
            <div class="col-md-4"><label>{{ __('restaurantnew::gift_voucher.type') }}</label><select name="voucher_type" class="form-control"><option value="gift_card">Gift Card</option><option value="voucher">Voucher</option></select></div>
            <div class="col-md-4"><label>{{ __('restaurantnew::gift_voucher.issue_amount') }}</label><input name="issue_amount" type="number" step="0.0001" class="form-control" required></div>
        </div>
        <div class="row mt-15">
            <div class="col-md-4"><label>{{ __('restaurantnew::gift_voucher.customer') }}</label><input name="customer_name" class="form-control"></div>
            <div class="col-md-4"><label>{{ __('restaurantnew::gift_voucher.mobile') }}</label><input name="customer_mobile" class="form-control"></div>
            <div class="col-md-4"><label>{{ __('restaurantnew::gift_voucher.email') }}</label><input name="customer_email" type="email" class="form-control"></div>
        </div>
        <div class="row mt-15">
            <div class="col-md-3"><label>Business ID</label><input name="business_id" type="number" class="form-control" required></div>
            <div class="col-md-3"><label>Location ID</label><input name="business_location_id" type="number" class="form-control"></div>
            <div class="col-md-3"><label>{{ __('restaurantnew::gift_voucher.issued_on') }}</label><input name="issued_on" type="date" class="form-control"></div>
            <div class="col-md-3"><label>{{ __('restaurantnew::gift_voucher.expires_on') }}</label><input name="expires_on" type="date" class="form-control"></div>
        </div>
        <button class="btn btn-primary mt-20">{{ __('restaurantnew::gift_voucher.save') }}</button>
    </form>
</div>
@endsection
