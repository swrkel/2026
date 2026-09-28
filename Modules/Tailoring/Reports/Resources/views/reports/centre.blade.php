@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_reports.css') }}">
<div class="tailoring-clean-page">
<div class="tailoring-page-header"><div><h1>Tailoring Reports Centre</h1><p>All Tailoring reports in one clean reporting portal.</p></div><button class="btn btn-default">Refresh</button></div>
<div class="tailoring-kpi-grid">
<div class="tailoring-kpi-card"><h4>Reports</h4><strong>{{ $summary['reports'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Scheduled</h4><strong>{{ $summary['scheduled'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Exports Today</h4><strong>{{ $summary['exports_today'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Pending Checks</h4><strong>{{ $summary['pending_checks'] ?? 0 }}</strong></div>
</div>
<div class="tailoring-card"><div class="tailoring-toolbar"><input class="form-control" placeholder="Search"><select class="form-control tailoring-select2"><option>All Branches</option></select><input class="form-control tailoring-date-range" placeholder="Date Range"><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div></div>

<div class="tailoring-report-grid">
<div class="tailoring-report-tile"><h4>Sales Reports</h4><p>Garment, customer, branch, salesperson and date-wise sales.</p></div>
<div class="tailoring-report-tile"><h4>Production Reports</h4><p>Production queue, stage ageing, department performance and capacity.</p></div>
<div class="tailoring-report-tile"><h4>Material Reports</h4><p>Fabric consumption, wastage, accessory usage and stock valuation.</p></div>
<div class="tailoring-report-tile"><h4>Quality Reports</h4><p>QC results, rework percentage, defect analysis and approval history.</p></div>
<div class="tailoring-report-tile"><h4>Delivery Reports</h4><p>Ready orders, deliveries, delays, signature and payment verification.</p></div>
<div class="tailoring-report-tile"><h4>Executive Reports</h4><p>Branch-wise and consolidated profitability, productivity and KPIs.</p></div>
</div>

</div>
<script src="{{ asset('modules/tailoring/js/tailoring_reports.js') }}"></script>
@endsection
