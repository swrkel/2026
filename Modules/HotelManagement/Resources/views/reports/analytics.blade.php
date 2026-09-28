@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header"><h1>Hotel Management Analytics <small>Executive KPI, department revenue and saved snapshots</small></h1></section>
<section class="content hotel-management hotel-management-reports">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Filters</h3></div><div class="box-body">@include('hotelmanagement::reports.partials.filters')</div></div>

<div class="hm-kpi-grid hm-analytics-grid">
    <div class="hm-kpi-card"><span>Total Rooms</span><strong>{{ number_format($summary['occupancy']['total_rooms'] ?? 0) }}</strong><small>Available inventory</small></div>
    <div class="hm-kpi-card"><span>Occupancy %</span><strong>{{ number_format($summary['occupancy']['occupancy_percent'] ?? 0, 2) }}%</strong><small>{{ $summary['period']['from'] ?? '' }} to {{ $summary['period']['to'] ?? '' }}</small></div>
    <div class="hm-kpi-card"><span>Room Revenue</span><strong>{{ number_format($summary['revenue']['room_revenue'] ?? 0, 4) }}</strong><small>Folio line revenue</small></div>
    <div class="hm-kpi-card"><span>Payments</span><strong>{{ number_format($summary['revenue']['payments'] ?? 0, 4) }}</strong><small>Guest collections</small></div>
    <div class="hm-kpi-card"><span>Open Folios</span><strong>{{ number_format($summary['counts']['openFolios'] ?? 0) }}</strong><small>Balance control</small></div>
    <div class="hm-kpi-card"><span>Dirty / Cleaning Rooms</span><strong>{{ number_format($summary['counts']['dirtyRooms'] ?? 0) }}</strong><small>Housekeeping control</small></div>
</div>

<div class="row">
    <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Department Revenue</h3></div><div class="box-body table-responsive"><table class="table table-bordered hotel-management-report-table"><thead><tr><th>Department</th><th class="text-right">Amount</th></tr></thead><tbody>@foreach(($summary['department_revenue'] ?? []) as $name => $amount)<tr><td>{{ ucwords(str_replace('_',' ', $name)) }}</td><td class="text-right">{{ number_format($amount, 4) }}</td></tr>@endforeach</tbody></table></div></div></div>
    <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Operational Counts</h3></div><div class="box-body table-responsive"><table class="table table-bordered hotel-management-report-table"><thead><tr><th>Metric</th><th class="text-right">Count</th></tr></thead><tbody>@foreach(($summary['counts'] ?? []) as $name => $amount)<tr><td>{{ ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $name)) }}</td><td class="text-right">{{ number_format($amount) }}</td></tr>@endforeach</tbody></table></div></div></div>
</div>

<div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Toolbar</h3></div><div class="box-body hm-toolbar">
    <a class="btn btn-primary" href="{{ route('hotel-management.reports.analytics', array_merge($filters ?? [], ['export'=>'csv'])) }}">CSV</a>
    <a class="btn btn-success" href="{{ route('hotel-management.reports.analytics', array_merge($filters ?? [], ['export'=>'excel'])) }}">Excel</a>
    <a class="btn btn-danger" href="{{ route('hotel-management.reports.analytics', array_merge($filters ?? [], ['export'=>'pdf'])) }}">PDF</a>
    <button class="btn btn-default" onclick="window.print()">Print</button>
    <form method="POST" action="{{ route('hotel-management.reports.analytics.snapshot') }}" style="display:inline-block">@csrf
        <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}"><input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}"><input type="hidden" name="report_key" value="hotel_analytics">
        <button class="btn btn-info" type="submit">Save Snapshot</button>
    </form>
</div></div>

<div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Saved Snapshots</h3></div><div class="box-body table-responsive"><table class="table table-bordered hotel-management-report-table"><thead><tr><th>Date</th><th>Report</th><th>Created</th><th class="text-right">ID</th></tr></thead><tbody>@forelse($snapshots as $snapshot)<tr><td>{{ $snapshot->snapshot_date }}</td><td>{{ $snapshot->report_key }}</td><td>{{ $snapshot->created_at }}</td><td class="text-right">{{ $snapshot->id }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No snapshots for the selected period.</td></tr>@endforelse</tbody></table></div></div>
</section>
@endsection
