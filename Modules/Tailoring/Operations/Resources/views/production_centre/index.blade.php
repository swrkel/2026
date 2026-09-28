@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_operations.css') }}">
<div class="tailoring-clean-page">
<div class="tailoring-page-header"><div><h1>Production Control Centre</h1><p>Work queue, allocation, priority, department workload and capacity planning.</p></div><button class="btn btn-default">Refresh</button></div>
<div class="tailoring-kpi-grid">
<div class="tailoring-kpi-card"><h4>Today</h4><strong>{{ $summary['today'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Pending</h4><strong>{{ $summary['pending'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>In Progress</h4><strong>{{ $summary['in_progress'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card tailoring-alert-green"><h4>Completed</h4><strong>{{ $summary['completed'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card tailoring-alert-red"><h4>Overdue</h4><strong>{{ $summary['overdue'] ?? 0 }}</strong></div>
<div class="tailoring-kpi-card"><h4>Efficiency</h4><strong>{{ $summary['efficiency'] ?? 0 }}%</strong></div>
</div>
<div class="tailoring-card"><div class="tailoring-toolbar"><input type="text" class="form-control" placeholder="Search"><select class="form-control tailoring-select2"><option>All Branches</option></select><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div></div>
<div class="tailoring-kanban"><div class="tailoring-kanban-column"><h4>Pending</h4><div class="tailoring-mini-card">No records loaded</div></div><div class="tailoring-kanban-column"><h4>In Progress</h4><div class="tailoring-mini-card">No records loaded</div></div><div class="tailoring-kanban-column"><h4>Trial</h4><div class="tailoring-mini-card">No records loaded</div></div><div class="tailoring-kanban-column"><h4>QC</h4><div class="tailoring-mini-card">No records loaded</div></div><div class="tailoring-kanban-column"><h4>Packing</h4><div class="tailoring-mini-card">No records loaded</div></div><div class="tailoring-kanban-column"><h4>Ready</h4><div class="tailoring-mini-card">No records loaded</div></div></div></div>
<script src="{{ asset('modules/tailoring/js/tailoring_operations.js') }}"></script>
@endsection
