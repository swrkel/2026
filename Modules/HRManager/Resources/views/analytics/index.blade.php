@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Executive HR Analytics Dashboard','subtitle'=>'Enterprise HR intelligence, workforce metrics, alerts, snapshots and executive reports.','section'=>'Analytics'])
<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Total Employees</span><strong>{{ number_format($metrics['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-user"></i><span>Active Employees</span><strong>{{ number_format($metrics['active'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-clock-o"></i><span>Attendance Logs</span><strong>{{ number_format($metrics['attendance'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-calendar"></i><span>Leave Requests</span><strong>{{ number_format($metrics['leave'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-money"></i><span>Payroll Net</span><strong>{{ number_format($metrics['payroll'] ?? 0, 2) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-file-text"></i><span>Claims Total</span><strong>{{ number_format($metrics['claims'] ?? 0, 2) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-graduation-cap"></i><span>Training</span><strong>{{ number_format($metrics['training'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-line-chart"></i><span>Performance</span><strong>{{ number_format($metrics['performance'] ?? 0) }}</strong></div>
</div>

<div class="analytics-grid">
<div class="hr-panel wide">
<h3>Open Analytics Alerts</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search alert no, title, status'])
<table class="hr-table"><thead><tr><th>Alert No</th><th>Metric</th><th>Value</th><th>Title</th><th>Level</th><th>Status</th></tr></thead><tbody>
@forelse($alerts as $row)
<tr><td>{{ $row->alert_no }}</td><td>{{ $row->metric_key }}</td><td>{{ number_format($row->metric_value ?? 0, 2) }}</td><td>{{ $row->alert_title }}</td><td>{{ $row->alert_level }}</td><td><em class="status-pill">{{ ucfirst($row->alert_status) }}</em></td></tr>
@empty<tr><td colspan="6" class="empty-row">No analytics alerts found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($alerts,'links')){{ $alerts->links() }}@endif
</div>
</div>

<div class="analytics-grid bottom">
<div class="hr-panel"><h3>Snapshots</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Date</th><th>Employees</th></tr></thead><tbody>@forelse($snapshots as $row)<tr><td>{{ $row->snapshot_no }}</td><td>{{ $row->snapshot_date }}</td><td>{{ $row->total_employees }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No snapshots.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>KPI Targets</h3><table class="hr-table compact-table"><thead><tr><th>Code</th><th>Metric</th><th>Target</th></tr></thead><tbody>@forelse($targets as $row)<tr><td>{{ $row->target_code }}</td><td>{{ $row->metric_key }}</td><td>{{ $row->target_value }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No targets.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Report Exports</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Report</th><th>Format</th></tr></thead><tbody>@forelse($exports as $row)<tr><td>{{ $row->export_no }}</td><td>{{ $row->report_title }}</td><td>{{ strtoupper($row->export_format) }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No exports.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
