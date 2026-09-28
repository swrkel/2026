@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Organization Structure & Organization Chart','subtitle'=>'Organization units, positions, reporting hierarchy, employee assignments and vacancy map.','section'=>'Organization'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-building"></i><span>Units</span><strong>{{ number_format($stats['units'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-briefcase"></i><span>Positions</span><strong>{{ number_format($stats['positions'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-id-badge"></i><span>Assignments</span><strong>{{ number_format($stats['assignments'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-sitemap"></i><span>Reporting</span><strong>{{ number_format($stats['relationships'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-camera"></i><span>Snapshots</span><strong>{{ number_format($stats['snapshots'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-map-marker"></i><span>Vacancies</span><strong>{{ number_format($stats['vacancies'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-bullhorn"></i><span>Open Vacancies</span><strong>{{ number_format($stats['open_vacancies'] ?? 0) }}</strong></div>
</div>

<div class="org-grid">
<div class="hr-panel">
<h3>Create Unit</h3>
<form method="POST" action="{{ route('hrmanager.organization.units.store') }}" class="hr-form">@csrf
<label>Unit Name</label><input name="unit_name" required>
<label>Unit Type</label><select name="unit_type"><option value="company">Company</option><option value="division">Division</option><option value="department">Department</option><option value="section">Section</option><option value="unit">Unit</option></select>
<label>Parent Unit</label><select name="parent_unit_id"><option value="">None</option>@foreach($allUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>@endforeach</select>
<label>Manager</label><select name="manager_employee_id"><option value="">Select Manager</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<button class="hr-primary-btn">Save Unit</button>
</form>

<h3 class="mt">Create Position</h3>
<form method="POST" action="{{ route('hrmanager.organization.positions.store') }}" class="hr-form">@csrf
<label>Position Title</label><input name="position_title" required>
<label>Organization Unit</label><select name="organization_unit_id"><option value="">Select Unit</option>@foreach($allUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>@endforeach</select>
<label>Approved Headcount</label><input type="number" name="approved_headcount" value="1">
<button class="hr-primary-btn">Save Position</button>
</form>

<h3 class="mt">Assign Employee</h3>
<form method="POST" action="{{ route('hrmanager.organization.assignments.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Position</label><select name="position_id" required><option value="">Select Position</option>@foreach($positions as $position)<option value="{{ $position->id }}">{{ $position->position_title }}</option>@endforeach</select>
<label>Effective From</label><input type="date" name="effective_from" value="{{ date('Y-m-d') }}">
<button class="hr-primary-btn">Assign</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Organization Units</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search unit no, name, type'])
<table class="hr-table"><thead><tr><th>Unit No</th><th>Name</th><th>Type</th><th>Parent</th><th>Manager</th><th>Status</th></tr></thead><tbody>
@forelse($units as $row)
<tr><td>{{ $row->unit_no }}</td><td>{{ $row->unit_name }}</td><td>{{ ucfirst($row->unit_type) }}</td><td>#{{ $row->parent_unit_id }}</td><td>#{{ $row->manager_employee_id }}</td><td><em class="status-pill">{{ ucfirst($row->unit_status) }}</em></td></tr>
@empty<tr><td colspan="6" class="empty-row">No organization units found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($units,'links')){{ $units->links() }}@endif
</div>
</div>

<div class="org-grid bottom">
<div class="hr-panel"><h3>Positions</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Title</th><th>Status</th></tr></thead><tbody>@forelse($positions as $row)<tr><td>{{ $row->position_no }}</td><td>{{ $row->position_title }}</td><td><em class="status-pill">{{ $row->position_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No positions.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Assignments</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($assignments as $row)<tr><td>{{ $row->assignment_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->assignment_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No assignments.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Vacancy Map</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Position</th><th>Status</th></tr></thead><tbody>@forelse($vacancies as $row)<tr><td>{{ $row->vacancy_no }}</td><td>#{{ $row->position_id }}</td><td><em class="status-pill">{{ $row->vacancy_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No vacancies.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
