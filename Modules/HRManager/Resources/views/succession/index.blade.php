@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Succession Planning','subtitle'=>'Critical roles, successor candidates, readiness, development plans, talent matrix and succession risks.','section'=>'Succession Planning'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-briefcase"></i><span>Critical Roles</span><strong>{{ number_format($stats['roles'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-sitemap"></i><span>Candidate Pools</span><strong>{{ number_format($stats['pools'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-user-plus"></i><span>Successors</span><strong>{{ number_format($stats['candidates'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-map"></i><span>Development Plans</span><strong>{{ number_format($stats['plans'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-check-square"></i><span>Assessments</span><strong>{{ number_format($stats['assessments'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-warning"></i><span>Risks</span><strong>{{ number_format($stats['risks'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-exclamation"></i><span>Open Risks</span><strong>{{ number_format($stats['open_risks'] ?? 0) }}</strong></div>
</div>

<div class="succession-grid">
<div class="hr-panel">
<h3>Create Critical Role</h3>
<form method="POST" action="{{ route('hrmanager.succession_planning.roles.store') }}" class="hr-form">@csrf
<label>Role Title</label><input name="role_title" required>
<label>Current Employee</label><select name="current_employee_id"><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Criticality</label><select name="criticality_level"><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select>
<label>Vacancy Risk</label><select name="vacancy_risk"><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select>
<button class="hr-primary-btn">Save Role</button>
</form>

<h3 class="mt">Nominate Successor</h3>
<form method="POST" action="{{ route('hrmanager.succession_planning.candidates.store') }}" class="hr-form">@csrf
<label>Critical Role</label><select name="critical_role_id" required><option value="">Select Role</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->role_no }} - {{ $role->role_title }}</option>@endforeach</select>
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Readiness</label><select name="readiness_level"><option value="ready_now">Ready Now</option><option value="short_term">Short Term</option><option value="medium_term">Medium Term</option><option value="long_term">Long Term</option></select>
<button class="hr-primary-btn">Nominate</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Critical Roles</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search role no, title, status'])
<table class="hr-table"><thead><tr><th>Role No</th><th>Title</th><th>Current Employee</th><th>Criticality</th><th>Vacancy Risk</th><th>Status</th></tr></thead><tbody>
@forelse($roles as $row)
<tr><td>{{ $row->role_no }}</td><td>{{ $row->role_title }}</td><td>#{{ $row->current_employee_id }}</td><td>{{ ucfirst($row->criticality_level) }}</td><td>{{ ucfirst($row->vacancy_risk) }}</td><td><em class="status-pill">{{ ucfirst($row->status) }}</em></td></tr>
@empty<tr><td colspan="6" class="empty-row">No critical roles found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($roles,'links')){{ $roles->links() }}@endif
</div>
</div>

<div class="succession-grid bottom">
<div class="hr-panel"><h3>Successor Candidates</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Readiness</th></tr></thead><tbody>@forelse($candidates as $row)<tr><td>{{ $row->candidate_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ $row->readiness_level }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No candidates.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Development Plans</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($plans as $row)<tr><td>{{ $row->plan_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->development_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No plans.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Risks</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Risk</th><th>Status</th></tr></thead><tbody>@forelse($risks as $row)<tr><td>{{ $row->risk_no }}</td><td>{{ $row->risk_title }}</td><td><em class="status-pill">{{ $row->risk_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No risks.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
