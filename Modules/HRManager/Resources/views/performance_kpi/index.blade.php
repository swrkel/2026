@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Performance Management & KPI System','subtitle'=>'KPI library, employee KPI assignments, appraisals, competencies, PIPs and rewards connected to Employee Central Record.','section'=>'Performance KPI'])
<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-folder"></i><span>KPI Categories</span><strong>{{ number_format($stats['categories'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-bullseye"></i><span>KPI Library</span><strong>{{ number_format($stats['kpis'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-refresh"></i><span>Cycles</span><strong>{{ number_format($stats['cycles'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-tasks"></i><span>Assignments</span><strong>{{ number_format($stats['assignments'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-edit"></i><span>Appraisals</span><strong>{{ number_format($stats['appraisals'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-warning"></i><span>PIPs</span><strong>{{ number_format($stats['pips'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-gift"></i><span>Rewards</span><strong>{{ number_format($stats['rewards'] ?? 0) }}</strong></div>
</div>
<div class="kpi-grid">
<div class="hr-panel">
<h3>Assign KPIs</h3>
<form method="POST" action="{{ route('hrmanager.performance_kpi.assign_kpis') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Cycle</label><select name="performance_cycle_id" required><option value="">Select Cycle</option>@foreach($cycles as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->cycle_name }}</option>@endforeach</select>
<button class="hr-primary-btn">Assign KPI Set</button>
</form>
<h3 class="mt">Create Appraisal</h3>
<form method="POST" action="{{ route('hrmanager.performance_kpi.appraisals.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Cycle</label><select name="performance_cycle_id" required><option value="">Select Cycle</option>@foreach($cycles as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->cycle_name }}</option>@endforeach</select>
<label>Type</label><select name="appraisal_type"><option value="annual">Annual</option><option value="quarterly">Quarterly</option><option value="monthly">Monthly</option><option value="probation">Probation</option></select>
<button class="hr-primary-btn">Create Appraisal</button>
</form>
</div>
<div class="hr-panel wide">
<h3>KPI Assignments</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search assignment no or status'])
<table class="hr-table"><thead><tr><th>Assignment No</th><th>Employee</th><th>Cycle</th><th>Status</th><th>Assigned At</th></tr></thead><tbody>
@forelse($assignments as $row)
<tr><td>{{ $row->assignment_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ $row->performance_cycle_id }}</td><td><em class="status-pill">{{ ucfirst($row->assignment_status) }}</em></td><td>{{ $row->assigned_at }}</td></tr>
@empty<tr><td colspan="5" class="empty-row">No KPI assignments found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($assignments,'links')){{ $assignments->links() }}@endif
</div>
</div>
<div class="kpi-grid bottom">
<div class="hr-panel"><h3>KPI Library</h3><table class="hr-table compact-table"><thead><tr><th>Code</th><th>KPI</th><th>Weight</th></tr></thead><tbody>@forelse($kpis as $row)<tr><td>{{ $row->kpi_code }}</td><td>{{ $row->kpi_name }}</td><td>{{ $row->default_weightage }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No KPIs found.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Appraisals</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($appraisals as $row)<tr><td>{{ $row->appraisal_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->appraisal_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No appraisals.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>PIPs</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($pips as $row)<tr><td>{{ $row->pip_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->pip_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No PIPs.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
