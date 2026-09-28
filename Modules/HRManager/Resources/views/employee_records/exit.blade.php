@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
<div class="hr-hero"><div><span class="hr-eyebrow">Employee Exit</span><h1>Resignation & Termination</h1><p>Exit management structure for handover and final settlement workflows.</p></div><div class="hr-actions"><a href="{{ route('hr.employee_records.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
<div class="hr-grid-2">
<div class="hr-card"><h3>Recent Resignations</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Date</th><th>Last Day</th><th>Status</th></tr></thead><tbody>
@forelse($resignations as $row)<tr><td>{{ $row->resignation_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->resignation_date }}</td><td>{{ $row->last_working_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
@empty<tr><td colspan="5" class="hr-empty">No resignation records found.</td></tr>@endforelse
</tbody></table></div>
<div class="hr-card"><h3>Recent Terminations</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Date</th><th>Effective</th><th>Status</th></tr></thead><tbody>
@forelse($terminations as $row)<tr><td>{{ $row->termination_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->termination_date }}</td><td>{{ $row->effective_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
@empty<tr><td colspan="5" class="hr-empty">No termination records found.</td></tr>@endforelse
</tbody></table></div>
</div></div>
@endsection
