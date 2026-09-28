@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Employee Movement & Lifecycle Actions','subtitle'=>'Promotions, transfers, salary revisions, probation actions and employee lifecycle history.','section'=>'Movements'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-exchange"></i><span>Movements</span><strong>{{ number_format($stats['movements'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-hourglass"></i><span>Pending</span><strong>{{ number_format($stats['pending'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-check"></i><span>Approved</span><strong>{{ number_format($stats['approved'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-money"></i><span>Salary Revisions</span><strong>{{ number_format($stats['salary_revisions'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-calendar-check-o"></i><span>Probation</span><strong>{{ number_format($stats['probations'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-history"></i><span>Events</span><strong>{{ number_format($stats['events'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-list"></i><span>History</span><strong>{{ number_format($stats['history'] ?? 0) }}</strong></div>
</div>

<div class="movement-grid">
<div class="hr-panel">
<h3>Create Movement</h3>
<form method="POST" action="{{ route('hrmanager.movements.employee_movements.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Movement Type</label><select name="movement_type" required><option value="promotion">Promotion</option><option value="transfer">Transfer</option><option value="acting">Acting Position</option><option value="salary_revision">Salary Revision</option><option value="manager_change">Reporting Manager Change</option><option value="location_change">Location Change</option></select>
<label>Effective Date</label><input type="date" name="effective_date" value="{{ date('Y-m-d') }}">
<label>New Salary</label><input type="number" step="0.0001" name="new_salary" value="0">
<label>Reason</label><input name="reason">
<button class="hr-primary-btn">Submit Movement</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Employee Movements</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search movement no, type, status'])
<table class="hr-table"><thead><tr><th>Movement No</th><th>Employee</th><th>Type</th><th>Effective</th><th>New Salary</th><th>Status</th><th>Approval</th></tr></thead><tbody>
@forelse($movements as $row)
<tr><td>{{ $row->movement_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ ucfirst(str_replace('_',' ',$row->movement_type)) }}</td><td>{{ $row->effective_date }}</td><td>{{ number_format($row->new_salary ?? 0, 2) }}</td><td><em class="status-pill">{{ ucfirst($row->movement_status) }}</em></td><td><em class="status-pill">{{ ucfirst($row->approval_status) }}</em></td></tr>
@empty<tr><td colspan="7" class="empty-row">No employee movements found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($movements,'links')){{ $movements->links() }}@endif
</div>
</div>

<div class="movement-grid bottom">
<div class="hr-panel"><h3>Approvals</h3><table class="hr-table compact-table"><thead><tr><th>Movement</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($approvals as $row)<tr><td>#{{ $row->employee_movement_id }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->approval_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No approvals.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Salary Revisions</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>New Salary</th></tr></thead><tbody>@forelse($salaryRevisions as $row)<tr><td>{{ $row->revision_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ number_format($row->new_salary ?? 0, 2) }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No salary revisions.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Lifecycle Events</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Type</th></tr></thead><tbody>@forelse($events as $row)<tr><td>{{ $row->event_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ $row->event_type }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No events.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
