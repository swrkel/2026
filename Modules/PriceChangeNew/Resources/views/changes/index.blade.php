@extends('pricechangenew::layouts.app')
@section('pcn_page_title', 'List Price Changes')
@section('pcn_page_subtitle', 'Search, review and continue the price-change workflow for the logged-in business.')
@section('pcn_page_actions')
<a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
@if($canCreateChanges)
<a href="{{ route('pricechangenew.changes.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Price Change</a>
@endif
@endsection
@section('pcn_content')
<div class="ch-card pcn-filter-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-filter text-primary"></i> Filters</h3>
            <div class="ch-card-subtitle">Filter by workflow status, permitted location and created date.</div>
        </div>
    </div>
    <div class="ch-card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group"><label>Status</label>
                    <select id="pcn_filter_status" class="form-control select2">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group"><label>Location</label>
                    <select id="pcn_filter_location" class="form-control select2">
                        <option value="">All Permitted Locations</option>
                        @foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2"><div class="form-group"><label>From Date</label><input type="date" id="pcn_filter_start_date" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>To Date</label><input type="date" id="pcn_filter_end_date" class="form-control"></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button type="button" id="pcn_filter_reset" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> Reset</button></div></div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-list-alt text-primary"></i> Price Changes</h3>
            <div class="ch-card-subtitle">Use Action to view, edit, submit, approve, reject, apply or cancel according to status and permission.</div>
        </div>
    </div>
    <div class="ch-card-body">
        <div class="table-responsive">
            <table id="pcn_changes_table" class="table table-bordered pos-standard-table pcn-index-table" data-url="{{ route('pricechangenew.changes.data') }}">
                <thead><tr><th>Action</th><th>Reference</th><th>Title</th><th>Locations</th><th class="text-right pcn-num">Lines</th><th>Status</th><th class="pcn-col-scope">Application<br>Scope</th><th>Effective At</th><th>Created By</th><th class="pcn-col-created">Created At</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
