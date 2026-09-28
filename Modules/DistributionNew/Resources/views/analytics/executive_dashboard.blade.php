@extends('layouts.app')
@section('title', __('distributionnew::lang.executive_dashboard'))
@section('content')
@include('distributionnew::layouts.partials.header')
<section class="content disnew-pos-page">
    <div class="row disnew-widget-row">
        @foreach($metrics as $metric)
            <div class="col-md-3 col-sm-6">
                <div class="disnew-pos-card">
                    <div class="disnew-card-title">{{ $metric->metric_name }}</div>
                    <div class="disnew-card-value">{{ number_format($metric->metric_value, 4) }}</div>
                    <div class="disnew-card-sub">{{ $metric->metric_date }}</div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="box box-primary disnew-pos-box">
        <div class="box-header with-border"><h3 class="box-title">{{ __('distributionnew::lang.analytics_snapshots') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-datatable">
                <thead><tr><th>Date</th><th>Type</th><th>Orders</th><th>Invoices</th><th>Deliveries</th><th>Net Sales</th><th>Collections</th><th>Profit</th></tr></thead>
                <tbody>
                @foreach($snapshots as $row)
                    <tr><td>{{ $row->snapshot_date }}</td><td>{{ $row->snapshot_type }}</td><td>{{ $row->orders_count }}</td><td>{{ $row->invoices_count }}</td><td>{{ $row->deliveries_count }}</td><td>{{ number_format($row->net_sales,4) }}</td><td>{{ number_format($row->collections,4) }}</td><td>{{ number_format($row->profit_amount,4) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
