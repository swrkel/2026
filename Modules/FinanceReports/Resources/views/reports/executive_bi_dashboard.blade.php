@extends('layouts.app')
@section('title', 'Executive BI Dashboard - New')
@section('content')
<section class="content-header">
    <h1>Executive BI Dashboard - New <small>{{ $context->label() }}</small></h1>
</section>
<section class="content">
    @include('financereports::layouts.filter', ['locations' => $locations, 'start' => $context->start_date, 'end' => $context->end_date, 'location_id' => $context->location_id])
    @include('financereports::layouts.toolbar')

    <div class="row">
        @foreach($dashboard['cards'] as $card)
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>{{ number_format((float) $card['value'], 4) }}</h3>
                        <p>{{ $card['label'] }}</p>
                    </div>
                    <div class="icon"><i class="fa fa-line-chart"></i></div>
                    <a href="{{ route($card['route'], request()->only(['start_date','end_date','location_id'])) }}" class="small-box-footer">Drill down <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Financial Health Indicators</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Indicator</th><th class="text-right">Value</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>Current Ratio</td><td class="text-right">{{ is_null($dashboard['health']['current_ratio']) ? '-' : number_format($dashboard['health']['current_ratio'], 4) }}</td><td>Information</td></tr>
                    <tr><td>Net Margin %</td><td class="text-right">{{ is_null($dashboard['health']['net_margin']) ? '-' : number_format($dashboard['health']['net_margin'], 4) }}</td><td>Information</td></tr>
                    <tr><td>Debt Ratio %</td><td class="text-right">{{ is_null($dashboard['health']['debt_ratio']) ? '-' : number_format($dashboard['health']['debt_ratio'], 4) }}</td><td>Information</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Shared Reporting Engine Status</h3></div>
        <div class="box-body">
            <p><strong>Mode:</strong> {{ $status['mode'] }}</p>
            <p><strong>Date Range:</strong> {{ $status['date_range'] }}</p>
            <p><strong>Read Only:</strong> Yes</p>
            <p><strong>Existing Finance Module Changed:</strong> No</p>
        </div>
    </div>
</section>
@endsection
