@extends('distributionnew::layouts.app')
@section('content')
@include('distributionnew::partials.erp-standard-styles')
<div class="pos-card">
    <div class="pos-card-header"><h4>@lang('distributionnew::messages.customer_order')</h4></div>
    <form method="POST" action="{{ route('distributionnew.customer-order.store', $token) }}">@csrf
        <input type="hidden" name="customer_id" value="{{ $accessToken->customer_id }}">
        <div class="row"><div class="col-md-4"><input name="requested_delivery_date" type="date" class="form-control"></div><div class="col-md-8"><input name="order_note" class="form-control" placeholder="Order note"></div></div>
        <hr><div class="alert alert-info">Add order lines using the module product selector after connecting product lookup in your tenant.</div>
        <button class="btn btn-primary">Submit Order</button>
    </form>
</div>
@endsection
