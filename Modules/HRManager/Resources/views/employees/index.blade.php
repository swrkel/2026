@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Employees','subtitle'=>'Central employee records used by all HR Manager sub modules.','section'=>'Employees'])
<div class="hr-card-grid"><div class="hr-kpi purple"><i class="fa fa-users"></i><span>Total Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div><div class="hr-kpi green"><i class="fa fa-check-circle"></i><span>Active Employees</span><strong>{{ number_format($stats['active_employees'] ?? 0) }}</strong></div><div class="hr-kpi blue"><i class="fa fa-sitemap"></i><span>Departments</span><strong>{{ number_format($stats['departments'] ?? 0) }}</strong></div><div class="hr-kpi orange"><i class="fa fa-id-badge"></i><span>Designations</span><strong>{{ number_format($stats['designations'] ?? 0) }}</strong></div></div>
<div class="hr-panel"><h3>Employee Register</h3>@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search employee no, name, mobile, NIC'])
<table class="hr-table"><thead><tr><th>Employee No</th><th>Name</th><th>Mobile</th><th>Email</th><th>Department</th><th>Status</th></tr></thead><tbody>@forelse($employees as $row)<tr><td>{{ $row->employee_no ?? $row->employee_code ?? $row->id }}</td><td>{{ $row->full_name ?? trim(($row->first_name ?? '').' '.($row->last_name ?? '')) }}</td><td>{{ $row->mobile ?? '' }}</td><td>{{ $row->email ?? '' }}</td><td>{{ $row->department_id ?? '' }}</td><td><em class="status-pill">{{ ($row->status ?? 1) ? 'Active' : 'Inactive' }}</em></td></tr>@empty<tr><td colspan="6" class="empty-row">No employees found.</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
