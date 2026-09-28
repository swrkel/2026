@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::restaurantnew.create_sale'))
@section('content')
<div class="rn-page rn-pos-sale">
    <div class="rn-header"><h3>{{ __('restaurantnew::restaurantnew.create_sale') }}</h3><a href="{{ route('restaurantnew.kitchen.screen') }}" class="btn btn-primary">{{ __('restaurantnew::restaurantnew.kitchen_screen') }}</a></div>
    <form method="POST" action="{{ route('restaurantnew.sales.store') }}" id="rnSaleForm">
        @csrf
        <div class="rn-card rn-grid-4">
            <div><label>Business ID</label><input name="business_id" class="form-control" required value="{{ request('business_id') }}"></div>
            <div><label>Location ID</label><input name="location_id" class="form-control" value="{{ request('location_id') }}"></div>
            <div><label>Order Type</label><select name="order_type" class="form-control"><option value="dine_in">Dine In</option><option value="takeaway">Takeaway</option><option value="delivery">Delivery</option></select></div>
            <div><label>Table ID</label><input name="table_id" class="form-control"></div>
            <div><label>Waiter ID</label><input name="waiter_id" class="form-control"></div>
            <div><label>Cashier ID</label><input name="cashier_id" class="form-control" value="{{ auth()->id() }}"></div>
            <div><label>Customer ID</label><input name="customer_id" class="form-control"></div>
            <div><label>Note</label><input name="note" class="form-control"></div>
        </div>
        <div class="rn-card mt-3">
            <div class="rn-toolbar"><button type="button" class="btn btn-success" id="rnAddLine">+ Item</button></div>
            <table class="table table-bordered" id="rnSaleLines"><thead><tr><th>Item</th><th>Kitchen Section</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead><tbody></tbody></table>
            <div class="rn-totals">
                <label>Discount <input name="discount_amount" value="0" class="form-control rn-money"></label>
                <label>Tax <input name="tax_amount" value="0" class="form-control rn-money"></label>
                <label>Service Charge <input name="service_charge_amount" value="0" class="form-control rn-money"></label>
                <strong>{{ __('restaurantnew::restaurantnew.total') }}: <span id="rnGrandTotal">0.00</span></strong>
            </div>
            <button class="btn btn-primary btn-lg">{{ __('restaurantnew::restaurantnew.save_and_send_to_kitchen') }}</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')<script src="{{ asset('modules/restaurantnew/js/sale-create.js') }}"></script>@endpush
