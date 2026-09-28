@extends('layouts.app')
@section('title', __('distributionnew::lang.kpi_dashboard'))
@section('content')
@include('distributionnew::layouts.partials.header')
<section class="content disnew-pos-page">
    <div class="box box-primary disnew-pos-box">
        <div class="box-header with-border"><h3 class="box-title">{{ __('distributionnew::lang.kpi_dashboard') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-datatable">
                <thead><tr><th>Date</th><th>Code</th><th>Name</th><th>Value</th><th>Target</th><th>Variance</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($metrics as $metric)
                    <tr><td>{{ $metric->metric_date }}</td><td>{{ $metric->metric_code }}</td><td>{{ $metric->metric_name }}</td><td>{{ number_format($metric->metric_value,4) }}</td><td>{{ number_format($metric->target_value,4) }}</td><td>{{ number_format($metric->variance_value,4) }}</td><td>{{ ucfirst($metric->status) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            {{ $metrics->links() }}
        </div>
    </div>
</section>
@endsection
