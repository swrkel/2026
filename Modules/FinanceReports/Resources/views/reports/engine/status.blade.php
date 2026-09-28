@extends('layouts.app')
@section('title', 'Finance Reports Engine Status')
@section('content')
<section class="content-header"><h1>Finance Reports Engine Status</h1></section>
<section class="content">
    @include('financereports::layouts.filter', ['locations' => $locations, 'start' => $context->start_date, 'end' => $context->end_date, 'location_id' => $context->location_id])
@include('financereports::layouts.toolbar')
    <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title">Core Engine</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr><th>Engine</th><td>{{ $status['engine'] }}</td></tr>
                    <tr><th>Mode</th><td>{{ $status['mode'] }}</td></tr>
                    <tr><th>Date Range</th><td>{{ $status['date_range'] }}</td></tr>
                    <tr><th>As At</th><td>{{ $status['as_at'] }}</td></tr>
                    <tr><th>Read Only</th><td>Yes</td></tr>
                    <tr><th>Existing Finance Module Touched</th><td>No</td></tr>
                    <tr><th>Shared Services</th><td>{{ implode(', ', $status['shared_services']) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
