@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'HR Reports','subtitle'=>'Employee, attendance, leave, payroll and compliance reports.','section'=>'HR Reports'])
<div class="hr-card-grid"><div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ $stats['employees'] ?? 0 }}</strong></div><div class="hr-kpi green"><i class="fa fa-check-circle"></i><span>Active</span><strong>{{ $stats['active_employees'] ?? 0 }}</strong></div><div class="hr-kpi blue"><i class="fa fa-sitemap"></i><span>Departments</span><strong>{{ $stats['departments'] ?? 0 }}</strong></div><div class="hr-kpi orange"><i class="fa fa-calendar"></i><span>Leave</span><strong>{{ $stats['leave_requests'] ?? 0 }}</strong></div></div>
<div class="hr-panel"><h3>Report Centre</h3>@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search report centre'])<table class="hr-table"><thead><tr><th>ID</th><th>Reference</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody><tr><td colspan="5" class="empty-row">No records found yet.</td></tr></tbody></table></div>
</div>
@endsection
