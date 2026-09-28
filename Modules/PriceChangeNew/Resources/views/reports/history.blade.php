@extends('pricechangenew::layouts.app')
@section('pcn_page_title', 'Price Application History')
@section('pcn_page_subtitle', 'Audit-friendly report of draft, approval, scheduling and live-price application activity.')
@section('pcn_page_actions')
<a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
@endsection
@section('pcn_content')
<div class="ch-card pcn-filter-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-filter text-primary"></i> Report Filters</h3><div class="ch-card-subtitle">Filter by status, permitted location and creation date.</div></div></div>
    <div class="ch-card-body">
        <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Status</label><select id="pcn_history_status" class="form-control select2"><option value="">All Statuses</option>@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div></div>
            <div class="col-md-3"><div class="form-group"><label>Location</label><select id="pcn_history_location" class="form-control select2"><option value="">All Permitted Locations</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div></div>
            <div class="col-md-2"><div class="form-group"><label>From Date</label><input type="date" id="pcn_history_start_date" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>To Date</label><input type="date" id="pcn_history_end_date" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button type="button" id="pcn_history_reset" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> Reset</button></div></div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-history text-primary"></i> Application History</h3><div class="ch-card-subtitle">Use View to inspect exact product lines, before/after snapshots and audit events.</div></div></div>
    <div class="ch-card-body">
        <div class="table-responsive">
            <table id="pcn_history_table" class="table table-bordered pos-standard-table" data-url="{{ route('pricechangenew.reports.history.data') }}">
                <thead><tr><th>Action</th><th>Reference</th><th>Title</th><th>Locations</th><th>Lines</th><th>Status</th><th>Application Scope</th><th>Attempts</th><th>Created By</th><th>Submitted At</th><th>Approved By</th><th>Approved At</th><th>Applied By</th><th>Applied At</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
