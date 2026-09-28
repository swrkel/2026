@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
<div class="hr-hero"><div><span class="hr-eyebrow">Performance</span><h1>Employee Appraisals</h1><p>Review scores, goals, improvements and recommendations.</p></div><div class="hr-actions"><a href="{{ route('hr.employee_records.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
<div class="hr-grid-2">
<div class="hr-card"><h3>Add Appraisal</h3><form method="POST" action="{{ route('hr.employee_records.appraisals.store') }}" class="hr-form">@csrf
<label>Employee ID</label><input type="number" name="employee_id" required>
<label>From</label><input type="date" name="appraisal_period_from" required>
<label>To</label><input type="date" name="appraisal_period_to" required>
<label>Score</label><input type="number" step="0.01" name="score">
<label>Rating</label><input name="rating">
<label>Strengths</label><textarea name="strengths"></textarea>
<label>Improvements</label><textarea name="improvements"></textarea>
<label>Goals</label><textarea name="goals"></textarea>
<button class="hr-btn hr-btn-primary">Save Appraisal</button>
</form></div>
<div class="hr-card"><h3>Appraisal List</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Period</th><th>Score</th><th>Rating</th><th>Status</th></tr></thead><tbody>
@forelse($appraisals as $row)<tr><td>{{ $row->appraisal_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->appraisal_period_from }} - {{ $row->appraisal_period_to }}</td><td>{{ $row->score }}</td><td>{{ $row->rating }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
@empty<tr><td colspan="6" class="hr-empty">No appraisals found.</td></tr>@endforelse
</tbody></table>{{ $appraisals->links() }}</div>
</div></div>
@endsection
