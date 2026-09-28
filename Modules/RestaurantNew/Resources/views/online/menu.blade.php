@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::online.menu'))
@section('content')
<div class="rn-pos-page rn-online-menu">
    <div class="rn-toolbar rn-pos-toolbar">
        <input type="text" class="form-control rn-search" placeholder="{{ __('restaurantnew::online.search_menu') }}">
        <select class="form-control rn-order-type"><option value="delivery">{{ __('restaurantnew::online.delivery') }}</option><option value="pickup">{{ __('restaurantnew::online.pickup') }}</option><option value="dine_in">{{ __('restaurantnew::online.dine_in') }}</option></select>
    </div>
    <div class="rn-menu-grid" id="restaurant-new-online-menu-grid">
        <div class="rn-empty-state">{{ __('restaurantnew::online.menu_items_load_here') }}</div>
    </div>
</div>
@endsection
@push('javascript')<script src="{{ asset('Modules/RestaurantNew/Resources/assets/js/online-ordering.js') }}"></script>@endpush
