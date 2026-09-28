@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Manager Self-Service Portal','subtitle'=>'Team dashboard, approvals, delegation, team notes, manager notifications and department HR actions.','section'=>'MSS'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-user-circle"></i><span>Managers</span><strong>{{ number_format($stats['managers'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-sitemap"></i><span>Team Members</span><strong>{{ number_format($stats['team_members'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-check-square"></i><span>Approvals</span><strong>{{ number_format($stats['approvals'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-hourglass"></i><span>Pending</span><strong>{{ number_format($stats['pending_approvals'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-share"></i><span>Delegations</span><strong>{{ number_format($stats['delegations'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-sticky-note"></i><span>Team Notes</span><strong>{{ number_format($stats['notes'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-bell"></i><span>Notifications</span><strong>{{ number_format($stats['notifications'] ?? 0) }}</strong></div>
</div>

<div class="mss-grid">
<div class="hr-panel">
<h3>Add Team Member</h3>
<form method="POST" action="{{ route('hrmanager.mss.team_members.store') }}" class="hr-form">@csrf
<label>Manager</label><select name="manager_employee_id" required><option value="">Select Manager</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Reporting Type</label><select name="reporting_type"><option value="direct">Direct</option><option value="indirect">Indirect</option><option value="temporary">Temporary</option></select>
<label>Effective From</label><input type="date" name="effective_from" value="{{ date('Y-m-d') }}">
<button class="hr-primary-btn">Save Team Member</button>
</form>

<h3 class="mt">Create Approval</h3>
<form method="POST" action="{{ route('hrmanager.mss.approvals.store') }}" class="hr-form">@csrf
<label>Manager</label><select name="manager_employee_id" required><option value="">Select Manager</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Employee</label><select name="employee_id"><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Reference Type</label><input name="reference_type" value="manual" required>
<label>Reference ID</label><input type="number" name="reference_id" value="0" required>
<label>Approval Title</label><input name="approval_title" required>
<button class="hr-primary-btn">Create Approval</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Team Members</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search reporting type'])
<table class="hr-table"><thead><tr><th>Manager</th><th>Employee</th><th>Type</th><th>Effective From</th><th>Status</th></tr></thead><tbody>
@forelse($teamMembers as $row)
<tr><td>#{{ $row->manager_employee_id }}</td><td>#{{ $row->employee_id }}</td><td>{{ ucfirst($row->reporting_type) }}</td><td>{{ $row->effective_from }}</td><td><em class="status-pill">{{ $row->status ? 'Active' : 'Inactive' }}</em></td></tr>
@empty<tr><td colspan="5" class="empty-row">No team members found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($teamMembers,'links')){{ $teamMembers->links() }}@endif
</div>
</div>

<div class="mss-grid bottom">
<div class="hr-panel"><h3>Approval Queue</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Manager</th><th>Status</th></tr></thead><tbody>@forelse($approvals as $row)<tr><td>{{ $row->approval_no }}</td><td>#{{ $row->manager_employee_id }}</td><td><em class="status-pill">{{ $row->approval_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No approvals.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Delegations</h3><table class="hr-table compact-table"><thead><tr><th>Manager</th><th>Delegate</th><th>Status</th></tr></thead><tbody>@forelse($delegations as $row)<tr><td>#{{ $row->manager_employee_id }}</td><td>#{{ $row->delegate_employee_id }}</td><td><em class="status-pill">{{ $row->status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No delegations.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Notifications</h3><table class="hr-table compact-table"><thead><tr><th>Manager</th><th>Title</th><th>Read</th></tr></thead><tbody>@forelse($notifications as $row)<tr><td>#{{ $row->manager_employee_id }}</td><td>{{ $row->title }}</td><td>{{ $row->read_status ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No notifications.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
