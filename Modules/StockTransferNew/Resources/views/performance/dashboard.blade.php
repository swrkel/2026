@extends('layouts.app')
@section('title', __('stocktransfernew::lang.performance_dashboard'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-performance.css') }}">
<section class="content-header"><h1>@lang('stocktransfernew::lang.performance_dashboard')</h1></section>
<section class="content stn-perf">
    <div class="stn-kpi-grid">
        @foreach($summary as $label => $value)
            <div class="stn-kpi"><span>{{ ucwords(str_replace('_',' ', $label)) }}</span><strong>{{ number_format($value) }}</strong></div>
        @endforeach
    </div>
    <div class="box box-solid"><div class="box-body">
        <a class="btn btn-primary" href="{{ route('stock-transfer-new.performance.query-health') }}">Query Health</a>
        <a class="btn btn-info" href="{{ route('stock-transfer-new.performance.cache-control') }}">Cache Control</a>
        <a class="btn btn-warning" href="{{ route('stock-transfer-new.performance.export-queue') }}">Export Queue</a>
    </div></div>
</section>
@endsection
