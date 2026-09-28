@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.delivery_order'))
@section('content')
<div class="rn-page">
    <div class="rn-page-header"><h3>{{ __('restaurantnew::lang.delivery_order') }} #{{ $order->id }}</h3></div>
    <div class="rn-table-card">
        <p><strong>{{ __('restaurantnew::lang.status') }}:</strong> {{ ucfirst($order->delivery_status) }}</p>
        <p><strong>{{ __('restaurantnew::lang.delivery_charge') }}:</strong> {{ number_format($order->delivery_charge, 4) }}</p>
        <p><strong>{{ __('restaurantnew::lang.payment_collection_status') }}:</strong> {{ ucfirst($order->payment_collection_status) }}</p>
        <form method="post" action="{{ route('restaurantnew.delivery.status', $order->id) }}">@csrf
            <select name="delivery_status" class="form-control"><option value="assigned">Assigned</option><option value="dispatched">Dispatched</option><option value="delivered">Delivered</option><option value="cancelled">Cancelled</option></select>
            <textarea name="note" class="form-control" placeholder="{{ __('restaurantnew::lang.note') }}"></textarea>
            <button class="btn rn-btn-primary">{{ __('restaurantnew::lang.update_status') }}</button>
        </form>
    </div>
</div>
@endsection
