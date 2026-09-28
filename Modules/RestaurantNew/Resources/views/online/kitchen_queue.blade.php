@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::online.online_kitchen_queue'))
@section('content')
<div class="rn-pos-page rn-kitchen-queue">
    <div class="rn-command-header"><h3>{{ __('restaurantnew::online.online_kitchen_queue') }}</h3><p>{{ __('restaurantnew::online.all_online_orders_received') }}</p></div>
    <div class="rn-kot-board">
        @forelse($orders as $order)
            <div class="rn-kot-card rn-status-{{ $order->status }}">
                <div class="rn-kot-head"><strong>{{ $order->online_order_no }}</strong><span>{{ ucfirst($order->status) }}</span></div>
                <div>{{ optional($order->customer)->customer_name }} / {{ $order->order_type }}</div>
                <ul>@foreach($order->lines as $line)<li>{{ number_format($line->quantity, 3) }} x {{ $line->item_name }}</li>@endforeach</ul>
                <button class="btn btn-sm btn-success rn-online-status" data-id="{{ $order->id }}" data-status="preparing">{{ __('restaurantnew::online.start_preparing') }}</button>
                <button class="btn btn-sm btn-primary rn-online-status" data-id="{{ $order->id }}" data-status="ready">{{ __('restaurantnew::online.mark_ready') }}</button>
                <button class="btn btn-sm btn-default" onclick="window.print()">{{ __('restaurantnew::online.print') }}</button>
            </div>
        @empty
            <div class="rn-empty-state">{{ __('restaurantnew::online.no_orders') }}</div>
        @endforelse
    </div>
</div>
@endsection
@push('javascript')<script src="{{ asset('Modules/RestaurantNew/Resources/assets/js/online-ordering.js') }}"></script>@endpush
