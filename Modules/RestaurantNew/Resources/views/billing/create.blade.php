@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::lang.new_bill'))

@section('content')
<div class="restaurantnew-page restaurantnew-billing-create">
    @include('restaurantnew::partials.toolbar', ['title' => __('restaurantnew::lang.new_bill')])

    <form method="POST" action="{{ route('restaurantnew.billing.store') }}" class="card pos-standard-card">
        @csrf
        <div class="card-body">
            <input type="hidden" name="order_id" value="{{ optional($order)->id }}">

            <div class="row">
                <div class="col-md-3 form-group">
                    <label>@lang('restaurantnew::lang.order_no')</label>
                    <input type="text" class="form-control" value="{{ optional($order)->order_no }}" readonly>
                </div>
                <div class="col-md-3 form-group">
                    <label>@lang('restaurantnew::lang.discount')</label>
                    <input type="number" step="0.0001" name="discount_amount" class="form-control rn-bill-calc" value="0">
                </div>
                <div class="col-md-3 form-group">
                    <label>@lang('restaurantnew::lang.tax')</label>
                    <input type="number" step="0.0001" name="tax_amount" class="form-control rn-bill-calc" value="0">
                </div>
                <div class="col-md-3 form-group">
                    <label>@lang('restaurantnew::lang.service_charge')</label>
                    <input type="number" step="0.0001" name="service_charge_amount" class="form-control rn-bill-calc" value="0">
                </div>
            </div>

            @include('restaurantnew::billing.partials.payment-form')
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('restaurantnew.billing.index') }}" class="btn btn-secondary">@lang('restaurantnew::lang.cancel')</a>
            <button type="submit" class="btn btn-primary">@lang('restaurantnew::lang.create_bill')</button>
        </div>
    </form>
</div>
@endsection
