@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Competency Framework & Skill Matrix','subtitle'=>'Technical, behavioural and leadership competencies with assessments, skill matrix and gap actions.','section'=>'Competencies'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-folder"></i><span>Groups</span><strong>{{ number_format($stats['groups'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-star"></i><span>Competencies</span><strong>{{ number_format($stats['competencies'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-signal"></i><span>Levels</span><strong>{{ number_format($stats['levels'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-check-square"></i><span>Requirements</span><strong>{{ number_format($stats['requirements'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-edit"></i><span>Assessments</span><strong>{{ number_format($stats['assessments'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-th"></i><span>Skill Matrix</span><strong>{{ number_format($stats['matrix'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-bullseye"></i><span>Gap Actions</span><strong>{{ number_format($stats['gap_actions'] ?? 0) }}</strong></div>
</div>

<div class="competency-grid">
<div class="hr-panel">
<h3>Add Competency</h3>
<form method="POST" action="{{ route('hrmanager.competencies.competencies.store') }}" class="hr-form">@csrf
<label>Competency Name</label><input name="competency_name" required>
<label>Group</label><select name="competency_group_id"><option value="">Select Group</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->group_name }}</option>@endforeach</select>
<label>Type</label><select name="competency_type"><option value="technical">Technical</option><option value="behavioral">Behavioural</option><option value="leadership">Leadership</option><option value="certification">Certification</option></select>
<label>Description</label><input name="description">
<button class="hr-primary-btn">Save Competency</button>
</form>

<h3 class="mt">Create Assessment</h3>
<form method="POST" action="{{ route('hrmanager.competencies.assessments.store') }}" class="hr-form">@csrf
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Assessment Date</label><input type="date" name="assessment_date" value="{{ date('Y-m-d') }}">
<label>Type</label><select name="assessment_type"><option value="annual">Annual</option><option value="promotion">Promotion</option><option value="training_gap">Training Gap</option><option value="recruitment">Recruitment</option></select>
<button class="hr-primary-btn">Create Assessment</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Competency Library</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search competency code, name, type'])
<table class="hr-table"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Group</th><th>Scale</th><th>Status</th></tr></thead><tbody>
@forelse($competencies as $row)
<tr><td>{{ $row->competency_code }}</td><td>{{ $row->competency_name }}</td><td>{{ ucfirst($row->competency_type) }}</td><td>#{{ $row->competency_group_id }}</td><td>{{ $row->proficiency_scale }}</td><td><em class="status-pill">{{ $row->status ? 'Active' : 'Inactive' }}</em></td></tr>
@empty<tr><td colspan="6" class="empty-row">No competencies found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($competencies,'links')){{ $competencies->links() }}@endif
</div>
</div>

<div class="competency-grid bottom">
<div class="hr-panel"><h3>Assessments</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($assessments as $row)<tr><td>{{ $row->assessment_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->assessment_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No assessments.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Skill Matrix</h3><table class="hr-table compact-table"><thead><tr><th>Employee</th><th>Competency</th><th>Gap</th></tr></thead><tbody>@forelse($matrix as $row)<tr><td>#{{ $row->employee_id }}</td><td>#{{ $row->competency_id }}</td><td>{{ $row->gap_score }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No matrix records.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Gap Actions</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($actions as $row)<tr><td>{{ $row->action_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->action_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No gap actions.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
