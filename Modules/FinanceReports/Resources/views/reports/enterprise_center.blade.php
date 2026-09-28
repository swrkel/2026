@extends('layouts.app')
@section('title', 'Finance Reports Enterprise Center')
@section('content')
<section class="content-header"><h1>Finance Reports Enterprise Center</h1></section>
<section class="content">
    @include('financereports::layouts.filter', ['locations' => $locations, 'location_id' => $context->location_id, 'start' => $context->start_date, 'end' => $context->end_date])
@include('financereports::layouts.toolbar')
    <div class="row">
        @foreach($center['dashboard']['cards'] as $card)
            <div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ number_format($card['value'], 4) }}</h3><p>{{ $card['label'] }}</p></div></div></div>
        @endforeach
    </div>
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Enterprise RC-1 Status</h3></div><div class="box-body">
        <p><strong>Release:</strong> {{ $center['release'] }}</p>
        <p><strong>Read Only:</strong> {{ $center['read_only'] ? 'Yes' : 'No' }}</p>
        <p><strong>Existing Finance Module Touched:</strong> {{ $center['existing_finance_module_touched'] ? 'Yes' : 'No' }}</p>
        <p><strong>Consolidation Mode:</strong> {{ $center['consolidation']['mode'] }}</p>
        <p><strong>Cash Flow Forecast Total:</strong> {{ number_format($center['forecast_cash_flow']['totals']['projected_total'], 4) }}</p>
        <p><strong>Profit Forecast Total:</strong> {{ number_format($center['forecast_profit']['totals']['projected_total'], 4) }}</p>
    </div></div>
</section>
@endsection
