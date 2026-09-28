@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.delivery_management'))
@section('content')
<div class="rn-page rn-delivery-page">
    <div class="rn-page-header">
        <h3>{{ __('restaurantnew::lang.delivery_management') }}</h3>
        <div class="rn-toolbar">
            <input type="text" class="form-control rn-search" placeholder="{{ __('restaurantnew::lang.search') }}">
            <a href="{{ route('restaurantnew.reports.delivery_summary') }}" class="btn rn-btn-primary">{{ __('restaurantnew::lang.delivery_report') }}</a>
        </div>
    </div>
    <div class="rn-card-grid rn-four-columns">
        <div class="rn-card"><span>{{ __('restaurantnew::lang.pending') }}</span><strong>{{ $orders->where('delivery_status','pending')->count() }}</strong></div>
        <div class="rn-card"><span>{{ __('restaurantnew::lang.assigned') }}</span><strong>{{ $orders->where('delivery_status','assigned')->count() }}</strong></div>
        <div class="rn-card"><span>{{ __('restaurantnew::lang.dispatched') }}</span><strong>{{ $orders->where('delivery_status','dispatched')->count() }}</strong></div>
        <div class="rn-card"><span>{{ __('restaurantnew::lang.delivered') }}</span><strong>{{ $orders->where('delivery_status','delivered')->count() }}</strong></div>
    </div>
    <div class="rn-table-card">
        <table class="table table-bordered table-striped rn-datatable">
            <thead><tr><th>#</th><th>{{ __('restaurantnew::lang.customer') }}</th><th>{{ __('restaurantnew::lang.status') }}</th><th>{{ __('restaurantnew::lang.delivery_charge') }}</th><th>{{ __('restaurantnew::lang.cod') }}</th><th>{{ __('restaurantnew::lang.card') }}</th><th>{{ __('restaurantnew::lang.action') }}</th></tr></thead>
            <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td><td>{{ $order->customer_id }}</td><td>{{ ucfirst($order->delivery_status) }}</td><td>{{ number_format($order->delivery_charge, 4) }}</td><td>{{ number_format($order->cod_amount, 4) }}</td><td>{{ number_format($order->card_amount, 4) }}</td>
                    <td><a class="btn btn-sm rn-btn-secondary" href="{{ route('restaurantnew.delivery.show', $order->id) }}">{{ __('restaurantnew::lang.view') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $orders->links() }}
    </div>
</div>
@endsection
