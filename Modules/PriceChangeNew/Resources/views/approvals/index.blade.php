@extends('pricechangenew::layouts.app')
@section('pcn_page_title', 'Price Change Approval Queue')
@section('pcn_page_subtitle', 'Review submitted price changes before they become eligible for application.')
@section('pcn_page_actions')
<a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
@endsection
@section('pcn_content')
<div class="ch-card pcn-filter-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-filter text-primary"></i> Filter Queue</h3><div class="ch-card-subtitle">Only submitted records awaiting approval are shown.</div></div></div>
    <div class="ch-card-body">
        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Location</label><select id="pcn_approval_location" class="form-control select2"><option value="">All Permitted Locations</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div></div>
            <div class="col-md-2"><div class="form-group"><label>&nbsp;</label><button id="pcn_approval_reset" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> Reset</button></div></div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-check-square-o text-primary"></i> Awaiting Review</h3><div class="ch-card-subtitle">Open a record for full price details or approve/reject directly from the queue.</div></div></div>
    <div class="ch-card-body">
        <div class="table-responsive">
            <table id="pcn_approvals_table" class="table table-bordered pos-standard-table" data-url="{{ route('pricechangenew.approvals.data') }}">
                <thead><tr><th>Action</th><th>Reference</th><th>Title</th><th>Locations</th><th>Lines</th><th>Application Scope</th><th>Effective At</th><th>Submitted By</th><th>Submitted At</th></tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection
