@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::online.checkout'))
@section('content')
<div class="rn-pos-page rn-online-checkout">
    <div class="row">
        <div class="col-md-8">
            <div class="rn-panel"><h4>{{ __('restaurantnew::online.customer_details') }}</h4>
                <input class="form-control" name="customer_name" placeholder="{{ __('restaurantnew::online.customer_name') }}">
                <input class="form-control" name="mobile" placeholder="{{ __('restaurantnew::online.mobile') }}">
                <textarea class="form-control" name="delivery_address" placeholder="{{ __('restaurantnew::online.delivery_address') }}"></textarea>
            </div>
        </div>
        <div class="col-md-4"><div class="rn-panel"><h4>{{ __('restaurantnew::online.order_summary') }}</h4><div id="rn-online-cart"></div><button class="btn btn-primary btn-block" id="rn-place-online-order">{{ __('restaurantnew::online.place_order') }}</button></div></div>
    </div>
</div>
@endsection
