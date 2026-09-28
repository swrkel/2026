@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_reports.css') }}">
<div class="tailoring-clean-page">
<div class="tailoring-page-header"><div><h1>Tailoring Release Readiness</h1><p>Production readiness checklist for Tailoring v1.0 RC.</p></div><button class="btn btn-default">Refresh</button></div>
<div class="tailoring-kpi-grid">
<div class="tailoring-kpi-card"><h4>Reports</h4><strong>{{ $summary['reports'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Scheduled</h4><strong>{{ $summary['scheduled'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Exports Today</h4><strong>{{ $summary['exports_today'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Pending Checks</h4><strong>{{ $summary['pending_checks'] ?? 0 }}</strong></div>
</div>
<div class="tailoring-card"><div class="tailoring-toolbar"><input class="form-control" placeholder="Search"><select class="form-control tailoring-select2"><option>All Branches</option></select><input class="form-control tailoring-date-range" placeholder="Date Range"><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div></div>

<div class="tailoring-card"><table class="table table-bordered table-striped"><thead><tr><th>Area</th><th>Status</th><th>Remarks</th></tr></thead><tbody><tr><td>Routes</td><td><span class="tailoring-status-pill">Pending Review</span></td><td></td></tr><tr><td>Views</td><td><span class="tailoring-status-pill">Pending Review</span></td><td></td></tr><tr><td>Permissions</td><td><span class="tailoring-status-pill">Pending Review</span></td><td></td></tr><tr><td>SQL</td><td><span class="tailoring-status-pill">Pending Review</span></td><td>New SQL only.</td></tr></tbody></table></div>

</div>
<script src="{{ asset('modules/tailoring/js/tailoring_reports.js') }}"></script>
@endsection
