@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header',['title'=>'Employee Assets Management','subtitle'=>'Assets, employee assignments, returns, maintenance and damage tracking connected to Employee Central Record.','section'=>'Employee Assets'])

<div class="hr-card-grid">
<div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-folder"></i><span>Categories</span><strong>{{ number_format($stats['categories'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-archive"></i><span>Total Assets</span><strong>{{ number_format($stats['assets'] ?? 0) }}</strong></div>
<div class="hr-kpi green"><i class="fa fa-check"></i><span>Available</span><strong>{{ number_format($stats['available'] ?? 0) }}</strong></div>
<div class="hr-kpi purple"><i class="fa fa-user"></i><span>Assigned</span><strong>{{ number_format($stats['assigned'] ?? 0) }}</strong></div>
<div class="hr-kpi blue"><i class="fa fa-undo"></i><span>Returns</span><strong>{{ number_format($stats['returns'] ?? 0) }}</strong></div>
<div class="hr-kpi orange"><i class="fa fa-wrench"></i><span>Maintenance</span><strong>{{ number_format($stats['maintenance'] ?? 0) }}</strong></div>
<div class="hr-kpi red"><i class="fa fa-warning"></i><span>Damages</span><strong>{{ number_format($stats['damages'] ?? 0) }}</strong></div>
</div>

<div class="asset-grid">
<div class="hr-panel">
<h3>Assign Asset</h3>
<form method="POST" action="{{ route('hrmanager.assets.assign') }}" class="hr-form">@csrf
<label>Asset</label><select name="asset_id" required><option value="">Select Available Asset</option>@foreach($availableAssets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_no }} - {{ $asset->asset_name }}</option>@endforeach</select>
<label>Employee</label><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>@endforeach</select>
<label>Assigned Date</label><input type="date" name="assigned_date" value="{{ date('Y-m-d') }}">
<label>Expected Return Date</label><input type="date" name="expected_return_date">
<label>Remarks</label><textarea name="remarks"></textarea>
<button class="hr-primary-btn">Assign Asset</button>
</form>
</div>

<div class="hr-panel wide">
<h3>Asset Register</h3>
@include('hrmanager::components.list-toolbar',['searchPlaceholder'=>'Search asset no, name, serial no'])
<table class="hr-table"><thead><tr><th>Asset No</th><th>Name</th><th>Category</th><th>Serial</th><th>Condition</th><th>Status</th><th>Value</th></tr></thead><tbody>
@forelse($assets as $row)
<tr><td>{{ $row->asset_no }}</td><td>{{ $row->asset_name }}</td><td>{{ $row->asset_category_id }}</td><td>{{ $row->serial_no }}</td><td>{{ $row->asset_condition }}</td><td><em class="status-pill">{{ ucfirst($row->asset_status) }}</em></td><td>{{ number_format($row->current_value ?? 0, 2) }}</td></tr>
@empty<tr><td colspan="7" class="empty-row">No assets found.</td></tr>@endforelse
</tbody></table>
@if(method_exists($assets,'links')){{ $assets->links() }}@endif
</div>
</div>

<div class="asset-grid bottom">
<div class="hr-panel"><h3>Assignments</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Asset</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($assignments as $row)<tr><td>{{ $row->assignment_no }}</td><td>#{{ $row->asset_id }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->assignment_status }}</em></td></tr>@empty<tr><td colspan="4" class="empty-row">No assignments found.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Returns</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Asset</th><th>Employee</th><th>Status</th></tr></thead><tbody>@forelse($returns as $row)<tr><td>{{ $row->return_no }}</td><td>#{{ $row->asset_id }}</td><td>#{{ $row->employee_id }}</td><td><em class="status-pill">{{ $row->return_status }}</em></td></tr>@empty<tr><td colspan="4" class="empty-row">No returns found.</td></tr>@endforelse</tbody></table></div>
<div class="hr-panel"><h3>Maintenance</h3><table class="hr-table compact-table"><thead><tr><th>No</th><th>Asset</th><th>Date</th><th>Status</th></tr></thead><tbody>@forelse($maintenance as $row)<tr><td>{{ $row->maintenance_no }}</td><td>#{{ $row->asset_id }}</td><td>{{ $row->maintenance_date }}</td><td><em class="status-pill">{{ $row->maintenance_status }}</em></td></tr>@empty<tr><td colspan="4" class="empty-row">No maintenance records.</td></tr>@endforelse</tbody></table></div>
</div>
</div>
@endsection
