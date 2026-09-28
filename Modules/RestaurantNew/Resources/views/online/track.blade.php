@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::online.track_order'))
@section('content')
<div class="rn-pos-page rn-track-order">
    <div class="rn-panel">
        <h3>{{ $order->online_order_no }}</h3>
        <p>{{ __('restaurantnew::online.current_status') }}: <strong>{{ ucfirst($order->status) }}</strong></p>
        <p>{{ __('restaurantnew::online.total') }}: {{ number_format($order->total_amount, 4) }}</p>
    </div>
</div>
@endsection
