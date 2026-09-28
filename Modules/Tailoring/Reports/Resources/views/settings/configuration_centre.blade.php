@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_reports.css') }}">
<div class="tailoring-clean-page">
<div class="tailoring-page-header"><div><h1>Tailoring Configuration Centre</h1><p>Central settings for profiles, templates, workflows and automation.</p></div><button class="btn btn-default">Refresh</button></div>
<div class="tailoring-kpi-grid">
<div class="tailoring-kpi-card"><h4>Reports</h4><strong>{{ $summary['reports'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Scheduled</h4><strong>{{ $summary['scheduled'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Exports Today</h4><strong>{{ $summary['exports_today'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Pending Checks</h4><strong>{{ $summary['pending_checks'] ?? 0 }}</strong></div>
</div>
<div class="tailoring-card"><div class="tailoring-toolbar"><input class="form-control" placeholder="Search"><select class="form-control tailoring-select2"><option>All Branches</option></select><input class="form-control tailoring-date-range" placeholder="Date Range"><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div></div>

<div class="tailoring-report-grid">
<div class="tailoring-report-tile"><h4>Business Profile</h4><p>Home tailor, small shop, boutique, uniform supplier or factory mode.</p></div>
<div class="tailoring-report-tile"><h4>Workflow Templates</h4><p>Configure workflow per garment and business profile.</p></div>
<div class="tailoring-report-tile"><h4>Measurement Templates</h4><p>Configure garment-wise measurement fields.</p></div>
<div class="tailoring-report-tile"><h4>Automation Rules</h4><p>Job card, notification, invoice and delivery automation.</p></div>
</div>

</div>
<script src="{{ asset('modules/tailoring/js/tailoring_reports.js') }}"></script>
@endsection
