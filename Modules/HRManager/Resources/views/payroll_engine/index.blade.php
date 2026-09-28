@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Enterprise Payroll Engine','subtitle'=>'Payroll periods, salary structures, salary components, payroll runs and payslips connected to Employee Central Record.','section'=>'Payroll Engine'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-calendar"></i><span>Periods</span><strong>{{ number_format($stats['periods'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-cogs"></i><span>Components</span><strong>{{ number_format($stats['components'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-sitemap"></i><span>Structures</span><strong>{{ number_format($stats['structures'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-id-card"></i><span>Salary Assignments</span><strong>{{ number_format($stats['assignments'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-play"></i><span>Payroll Runs</span><strong>{{ number_format($stats['runs'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-file-text"></i><span>Payslips</span><strong>{{ number_format($stats['payslips'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-money"></i><span>Net Payroll</span><strong>{{ number_format($stats['net_total'] ?? 0, 2) }}</strong></div>
</div>

<div class="payroll-grid">
<div class="hr-panel">
<h3>Create Payroll Run</h3>
<form method="POST" action="{{ route('hrmanager.payroll_engine.runs.store') }}" class="hr-form">@csrf
<label>Payroll Period</label>
<select name="payroll_period_id" required><option value="">Select Period</option>@foreach($periods as $period)<option value="{{ $period->id }}">{{ $period->period_name ?? $period->period_code ?? ('Period #' . $period->id) }}</option>@endforeach</select>
<label>Remarks</label><textarea name="remarks"></textarea>
<button class="hr-primary-btn">Create Payroll Run</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Payroll Runs</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search payroll run no or status'])
<table class="hr-table"><thead><tr><th>Run No</th><th>Date</th><th>Employees</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($runs as $row)
<tr><td>{{ $row->run_no }}</td><td>{{ $row->run_date }}</td><td>{{ $row->employee_count ?? 0 }}</td><td>{{ number_format($row->gross_total ?? 0, 2) }}</td><td>{{ number_format($row->deduction_total ?? 0, 2) }}</td><td>{{ number_format($row->net_total ?? 0, 2) }}</td><td><em class="status-pill">{{ ucfirst($row->run_status ?? 'Draft') }}</em></td><td>@if(($row->run_status ?? '') === 'draft')<form method="POST" action="{{ route('hrmanager.payroll_engine.runs.calculate', $row->id) }}">@csrf<button class="mini-btn process">Calculate</button></form>@else<span class="muted">Calculated</span>@endif</td></tr>
@empty<tr><td colspan="8" class="empty-row">No payroll runs found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($runs,'links')){{ $runs->links() }}@endif
</div>
</div>

<div class="payroll-grid bottom">
<div class="hr-panel"><h3>Salary Components</h3><table class="hr-table compact-table"><thead><tr><th>Code</th><th>Name</th><th>Type</th></tr></thead><tbody>@forelse($components as $row)<tr><td>{{ $row->component_code }}</td><td>{{ $row->component_name }}</td><td>{{ $row->component_type }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No salary components.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Salary Structures</h3><table class="hr-table compact-table"><thead><tr><th>Code</th><th>Name</th><th>Status</th></tr></thead><tbody>@forelse($structures as $row)<tr><td>{{ $row->structure_code }}</td><td>{{ $row->structure_name }}</td><td><em class="status-pill">{{ $row->status }}</em></td></tr>@empty<tr><td colspan="3" class="empty-row">No salary structures.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Payslips</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Employee</th><th>Net</th></tr></thead><tbody>@forelse($payslips as $row)<tr><td>{{ $row->payslip_no }}</td><td>#{{ $row->employee_id }}</td><td>{{ number_format($row->net_salary ?? 0, 2) }}</td></tr>@empty<tr><td colspan="3" class="empty-row">No payslips.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
