@extends('pos::layouts.app')

@section('pos_styles')
<link rel="stylesheet" href="{{ route('pos.assets', ['type' => 'css', 'file' => 'pos_page_003.css', 'v' => 'is2238-20260910'], false) }}">
<link rel="stylesheet" href="{{ route('pos.assets', ['type' => 'css', 'file' => 'pos_s375_cashier_experience.css', 'v' => 'is2238-20260910'], false) }}">
@endsection

@section('pos_content')
<div class="pos-sales-workspace" data-cart-url="{{ route('pos.sales.cart.show', [], false) }}">
    @include('pos::sales.partials._workspace_header')
    @include('pos::sales.partials._enterprise_cashier_hotbar')
    @include('pos::sales.partials._quick_action_ribbon')

    <div class="row pos-sales-grid">
        <div class="col-md-7 col-sm-12">
            @include('pos::sales.partials._product_search')
            @include('pos::sales.partials._product_grid')
        </div>
        <div class="col-md-5 col-sm-12">
            @include('pos::sales.partials._customer_panel')
            @include('pos::sales.partials._cart')
            @include('pos::sales.partials._bill_summary')
        </div>
    </div>

    @include('pos::sales.partials._payment_entry_placeholder')
</div>
@endsection

@section('pos_scripts')
<script>
window.POS_PAGE_003_ROUTES = {
    productSearch: "{{ route('pos.sales.products.search', [], false) }}",
    customerSearch: "{{ route('pos.sales.customers.search', [], false) }}",
    cart: "{{ route('pos.sales.cart.show', [], false) }}",
    addLine: "{{ route('pos.sales.cart.add_line', [], false) }}",
    setCustomer: "{{ route('pos.sales.cart.set_customer', [], false) }}",
    hold: "{{ route('pos.sales.cart.hold', [], false) }}",
    suspend: "{{ route('pos.sales.cart.suspend', [], false) }}",
    quotation: "{{ route('pos.sales.quotations.store', [], false) }}"
};
</script>
<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_page_003.js', 'v' => 'is2238-20260910'], false) }}"></script>
<script src="{{ route('pos.assets', ['type' => 'js', 'file' => 'pos_s375_cashier_experience.js', 'v' => 'is2238-20260910'], false) }}"></script>
@endsection
