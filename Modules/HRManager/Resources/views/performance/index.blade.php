@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Enterprise Performance Management','subtitle'=>'Performance cycles, KPIs, goals, reviews, PIPs and promotions connected to Employee Central Record.','section'=>'Performance'])
<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-refresh"></i><span>Cycles</span><strong>{{ number_format($stats['cycles'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-bullseye"></i><span>KPIs</span><strong>{{ number_format($stats['kpis'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-star"></i><span>Competencies</span><strong>{{ number_format($stats['competencies'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-flag"></i><span>Goals</span><strong>{{ number_format($stats['goals'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-edit"></i><span>Reviews</span><strong>{{ number_format($stats['reviews'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-warning"></i><span>PIPs</span><strong>{{ number_format($stats['pips'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-level-up"></i><span>Promotions</span><strong>{{ number_format($stats['promotions'] ?? 0) }}</strong></div>
</div>

<div class="perf-grid">
<div class="hr-panel"><h3>Create Goal</h3><form method="POST" action="{{ route('hrmanager.performance.goals.store') }}" class="hr-form">@csrf<label>Employee</label><select name="employee_id"><option value="">Company / Department Goal</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select><label>Cycle</label><select name="cycle_id"><option value="">Select Cycle</option>@foreach($cycles as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->cycle_name }}</option>@endforeach</select><label>Goal Title</label><input name="goal_title" required><label>Due Date</label><input type="date" name="due_date"><label>Weightage</label><input type="number" step="0.01" name="weightage"><button class="hr-primary-btn">Save Goal</button></form></div>
<div class="hr-panel wide"><h3>Performance Goals</h3>@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search goal no, title, status'])<table class="hr-table"><thead><tr><th>Goal No</th><th>Title</th><th>Employee</th><th>Progress</th><th>Weightage</th><th>Status</th></tr></thead><tbody>@forelse($goals as $row)<tr><td>{{ $row->goal_no }}</td><td>{{ $row->goal_title }}</td><td>{{ $row->employee_id ? '#'.$row->employee_id : 'Company' }}</td><td>{{ number_format($row->progress_percent ?? 0, 2) }}%</td><td>{{ number_format($row->weightage ?? 0, 2) }}</td><td><em class="status-pill">{{ ucfirst($row->goal_status) }}</em></td></tr>@empty<tr><td colspan="6" class="empty-row">No performance goals found.</td></tr>@endforelse</tbody></table>@if(method_exists($goals,'links')){{ $goals->links() }}@endif</div>
</div>

<div class="perf-grid bottom">
<div class="hr-panel"><h3>KPI Library</h3><table class="hr-table compact-table"><thead><tr><th>Code</th><th>KPI</th><th>Weight</th></tr></thead><tbody>@forelse($kpis as $row)<tr><td>{{ $row->kpi_code }}</td><td>{{ $row->kpi_name }}</td><td>{{ $row->weightage }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No KPIs found.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Reviews</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($reviews as $row)<tr><td>{{ $row->review_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->review_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No reviews found.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>PIPs</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($pips as $row)<tr><td>{{ $row->pip_no }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->pip_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No PIPs found.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
