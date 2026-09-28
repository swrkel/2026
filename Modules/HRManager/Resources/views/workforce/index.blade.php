@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Workforce Planning','subtitle'=>'Headcount planning, workforce budgets, forecasts, scenarios and risk planning connected to Employee Central Record.','section'=>'Workforce Planning'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-map"></i><span>Plans</span><strong>{{ number_format($stats['plans'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-money"></i><span>Budgets</span><strong>{{ number_format($stats['budgets'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-line-chart"></i><span>Forecasts</span><strong>{{ number_format($stats['forecasts'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-random"></i><span>Scenarios</span><strong>{{ number_format($stats['scenarios'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-warning"></i><span>Risks</span><strong>{{ number_format($stats['risks'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-exclamation"></i><span>Open Risks</span><strong>{{ number_format($stats['open_risks'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-check"></i><span>Approved Plans</span><strong>{{ number_format($stats['approved_plans'] ?? 0) }}</strong></div>
</div>

<div class="workforce-grid">
<div class="hr-panel">
<h3>Create Workforce Plan</h3>
<form method="POST" action="{{ route('hrmanager.workforce_planning.plans.store') }}" class="hr-form">@csrf
<label>Plan Name</label><input name="plan_name" required>
<label>Plan Year</label><input type="number" name="plan_year" value="{{ date('Y') }}" required>
<label>Department ID</label><input type="number" name="department_id">
<label>Location ID</label><input type="number" name="location_id">
<button class="hr-primary-btn">Create Plan</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Workforce Plans</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search plan no, name, status'])
<table class="hr-table"><thead><tr><th>Plan No</th><th>Name</th><th>Year</th><th>Status</th><th>Approval</th></tr></thead><tbody>
@forelse($plans as $row)
<tr><td>{{ $row->plan_no }}</td><td>{{ $row->plan_name }}</td><td>{{ $row->plan_year }}</td><td><em class="status-pill">{{ ucfirst($row->plan_status) }}</em></td><td><em class="status-pill">{{ ucfirst($row->approval_status) }}</em></td></tr>
@empty<tr><td colspan="5" class="empty-row">No workforce plans found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($plans,'links')){{ $plans->links() }}@endif
</div>
</div>

<div class="workforce-grid bottom">
<div class="hr-panel"><h3>Headcount Budgets</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Year</th><th>Available</th></tr></thead><tbody>@forelse($budgets as $row)<tr><td>{{ $row->budget_no }}</td><td>{{ $row->budget_year }}</td><td>{{ $row->available_headcount }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No budgets.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Forecasts</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Date</th><th>Value</th></tr></thead><tbody>@forelse($forecasts as $row)<tr><td>{{ $row->forecast_no }}</td><td>{{ $row->forecast_date }}</td><td>{{ $row->forecast_value }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No forecasts.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Risks</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Risk</th><th>Status</th></tr></thead><tbody>@forelse($risks as $row)<tr><td>{{ $row->risk_no }}</td><td>{{ $row->risk_title }}</td><td><em class="status-pill">{{ $row->risk_status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No risks.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
