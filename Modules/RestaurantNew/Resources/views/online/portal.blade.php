@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::online.online_ordering'))
@section('content')
<div class="rn-pos-page rn-online-portal">
    <div class="rn-command-header">
        <h3>{{ __('restaurantnew::online.online_ordering') }}</h3>
        <p>{{ __('restaurantnew::online.portal_subtitle') }}</p>
    </div>
    <div class="rn-card-grid rn-card-grid-4">
        <a class="rn-dashboard-card" href="{{ route('restaurantnew.online.menu') }}"><span>{{ __('restaurantnew::online.browse_menu') }}</span></a>
        <a class="rn-dashboard-card" href="{{ route('restaurantnew.online.checkout') }}"><span>{{ __('restaurantnew::online.checkout') }}</span></a>
        <div class="rn-dashboard-card"><span>{{ __('restaurantnew::online.delivery_pickup_dinein') }}</span></div>
        <div class="rn-dashboard-card"><span>{{ __('restaurantnew::online.live_tracking') }}</span></div>
    </div>
</div>
@endsection
