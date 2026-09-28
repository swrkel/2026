@extends('restaurantnew::layouts.master')

@section('title', __('restaurantnew::analytics.restaurant_intelligence'))

@section('content')
<section class="rn-page rn-analytics-page">
    <div class="rn-page-header">
        <div>
            <h1>{{ __('restaurantnew::analytics.restaurant_intelligence') }}</h1>
            <p>{{ __('restaurantnew::analytics.subtitle') }}</p>
        </div>
        <form class="rn-toolbar" method="GET">
            <input type="date" name="from" value="{{ $from }}" class="form-control">
            <input type="date" name="to" value="{{ $to }}" class="form-control">
            <button class="btn btn-primary">{{ __('restaurantnew::analytics.apply') }}</button>
        </form>
    </div>

    <div class="rn-kpi-grid">
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::analytics.net_sales') }}</span><strong>{{ number_format($net_sales, 2) }}</strong></div>
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::analytics.gross_profit') }}</span><strong>{{ number_format($gross_profit, 2) }}</strong></div>
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::analytics.food_cost_percent') }}</span><strong>{{ number_format($food_cost_percentage, 2) }}%</strong></div>
        <div class="rn-kpi-card"><span>{{ __('restaurantnew::analytics.average_order_value') }}</span><strong>{{ number_format($average_order_value, 2) }}</strong></div>
    </div>

    <div class="rn-two-column">
        <div class="rn-card">
            <h3>{{ __('restaurantnew::analytics.top_menu_profitability') }}</h3>
            <table class="table table-sm rn-table">
                <thead><tr><th>{{ __('restaurantnew::analytics.menu_item') }}</th><th>{{ __('restaurantnew::analytics.qty') }}</th><th>{{ __('restaurantnew::analytics.sales') }}</th><th>{{ __('restaurantnew::analytics.margin') }}</th></tr></thead>
                <tbody>
                    @forelse($top_items as $item)
                        <tr><td>#{{ $item->menu_item_id }}</td><td>{{ number_format($item->qty_sold, 3) }}</td><td>{{ number_format($item->sales_total, 2) }}</td><td>{{ number_format($item->gross_margin, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ __('restaurantnew::analytics.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="rn-card">
            <h3>{{ __('restaurantnew::analytics.hourly_trends') }}</h3>
            <table class="table table-sm rn-table">
                <thead><tr><th>{{ __('restaurantnew::analytics.hour') }}</th><th>{{ __('restaurantnew::analytics.sales') }}</th><th>{{ __('restaurantnew::analytics.orders') }}</th><th>{{ __('restaurantnew::analytics.guests') }}</th></tr></thead>
                <tbody>
                    @forelse($hourly_trends as $row)
                        <tr><td>{{ str_pad($row->hour_no, 2, '0', STR_PAD_LEFT) }}:00</td><td>{{ number_format($row->net_sales, 2) }}</td><td>{{ $row->order_count }}</td><td>{{ $row->guest_count }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ __('restaurantnew::analytics.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
