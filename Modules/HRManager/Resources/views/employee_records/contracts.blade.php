@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
<div class="hr-hero"><div><span class="hr-eyebrow">Employee Records</span><h1>Contracts</h1><p>Employment contracts and probation records.</p></div><div class="hr-actions"><a href="{{ route('hr.employee_records.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
<div class="hr-grid-2">
<div class="hr-card"><h3>Add Contract</h3><form method="POST" action="{{ route('hr.employee_records.contracts.store') }}" class="hr-form">@csrf
<label>Employee ID</label><input type="number" name="employee_id" required>
<label>Contract No</label><input name="contract_no">
<label>Contract Type</label><select name="contract_type"><option value="employment">Employment</option><option value="probation">Probation</option><option value="temporary">Temporary</option><option value="consultant">Consultant</option></select>
<label>Start Date</label><input type="date" name="start_date" required>
<label>End Date</label><input type="date" name="end_date">
<label>Basic Salary</label><input type="number" step="0.0001" name="basic_salary">
<label>Terms</label><textarea name="terms"></textarea>
<button class="hr-btn hr-btn-primary">Save Contract</button>
</form></div>
<div class="hr-card"><h3>Contract List</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Type</th><th>Start</th><th>Salary</th><th>Status</th></tr></thead><tbody>
@forelse($contracts as $row)<tr><td>{{ $row->contract_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->contract_type }}</td><td>{{ $row->start_date }}</td><td>{{ number_format($row->basic_salary,2) }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
@empty<tr><td colspan="6" class="hr-empty">No contracts found.</td></tr>@endforelse
</tbody></table>{{ $contracts->links() }}</div>
</div></div>
@endsection
