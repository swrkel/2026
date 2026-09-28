@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
    @include('financereports::layouts.filter', ['locations' => $locations, 'location_id' => $context->location_id, 'start' => $context->start_date, 'end' => $context->end_date])
    @include('financereports::layouts.toolbar')
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">{{ $title }} | {{ $context->is_consolidated ? 'Consolidated' : 'Branch / Location' }}</h3></div>
        <div class="box-body table-responsive">
            <form method="get" class="form-inline" style="margin-bottom:10px;">
                <input type="hidden" name="location_id" value="{{ $context->location_id ?: 'all' }}">
                <input type="hidden" name="start_date" value="{{ $context->start_date }}">
                <input type="hidden" name="end_date" value="{{ $context->end_date }}">
                <label>Months</label>
                <input type="number" name="months" min="1" max="24" value="{{ $months }}" class="form-control input-sm">
                <button class="btn btn-primary btn-sm">Refresh Forecast</button>
            </form>
            <table class="table table-bordered table-striped">
                <thead><tr><th>Month</th><th class="text-right">Projected Amount</th><th>Basis</th></tr></thead>
                <tbody>
                    @foreach($report['rows'] as $row)
                        <tr><td>{{ $row->month }}</td><td class="text-right">{{ number_format($row->projected_amount, 4) }}</td><td>{{ $row->basis }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th>Total</th><th class="text-right">{{ number_format($report['totals']['projected_total'], 4) }}</th><th>Records: {{ $report['totals']['record_count'] }}</th></tr></tfoot>
            </table>
            <p class="text-muted">Read-only forecast based on existing finance data. No transaction posting or existing Finance module logic is changed.</p>
        </div>
    </div>
</section>
@endsection
