@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.reports'))
@section('content')
<div class="restaurantnew-page">
    <div class="rn-card rn-command">
        <h3>{{ __('restaurantnew::lang.reports') }}</h3>
        <div class="rn-report-grid">
            <a href="{{ route('restaurantnew.reports.daily-summary') }}">Daily Summary</a>
            <a href="{{ route('restaurantnew.reports.item-sales') }}">Item Sales</a>
            <a href="{{ route('restaurantnew.reports.category-sales') }}">Category Sales</a>
            <a href="{{ route('restaurantnew.reports.waiter-sales') }}">Waiter Sales</a>
            <a href="{{ route('restaurantnew.reports.table-sales') }}">Table Sales</a>
            <a href="{{ route('restaurantnew.reports.payments') }}">Payments</a>
            <a href="{{ route('restaurantnew.reports.tax-service') }}">Tax & Service Charge</a>
            <a href="{{ route('restaurantnew.reports.cancelled-void') }}">Cancelled / Void Bills</a>
        </div>
    </div>
</div>
@endsection
