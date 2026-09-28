@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
<div class="hr-hero"><div><span class="hr-eyebrow">Disciplinary</span><h1>Warnings</h1><p>Employee warning and disciplinary action records.</p></div><div class="hr-actions"><a href="{{ route('hr.employee_records.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
<div class="hr-grid-2">
<div class="hr-card"><h3>Add Warning</h3><form method="POST" action="{{ route('hr.employee_records.warnings.store') }}" class="hr-form">@csrf
<label>Employee ID</label><input type="number" name="employee_id" required>
<label>Warning Date</label><input type="date" name="warning_date" required>
<label>Type</label><input name="warning_type">
<label>Severity</label><select name="severity"><option value="normal">Normal</option><option value="serious">Serious</option><option value="critical">Critical</option></select>
<label>Subject</label><input name="subject" required>
<label>Description</label><textarea name="description"></textarea>
<label>Action Required</label><textarea name="action_required"></textarea>
<button class="hr-btn hr-btn-primary">Save Warning</button>
</form></div>
<div class="hr-card"><h3>Warning List</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Date</th><th>Severity</th><th>Subject</th><th>Status</th></tr></thead><tbody>
@forelse($warnings as $row)<tr><td>{{ $row->warning_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->warning_date }}</td><td>{{ $row->severity }}</td><td>{{ $row->subject }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
@empty<tr><td colspan="6" class="hr-empty">No warnings found.</td></tr>@endforelse
</tbody></table>{{ $warnings->links() }}</div>
</div></div>
@endsection
