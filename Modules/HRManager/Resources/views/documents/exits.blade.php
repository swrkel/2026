@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Employee Exit</span><h1>Resignations & Terminations</h1><p>Employee exit tracking and final settlement foundation.</p></div><div class="hr-actions"><a href="{{ route('hr.documents.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-grid-2">
        <div class="hr-card"><h3>Latest Resignations</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Date</th><th>Status</th></tr></thead><tbody>
            @forelse($resignations as $row)<tr><td>{{ $row->resignation_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->resignation_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
            @empty<tr><td colspan="4" class="hr-empty">No resignations found.</td></tr>@endforelse
        </tbody></table></div>
        <div class="hr-card"><h3>Latest Terminations</h3><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Date</th><th>Status</th></tr></thead><tbody>
            @forelse($terminations as $row)<tr><td>{{ $row->termination_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->termination_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
            @empty<tr><td colspan="4" class="hr-empty">No terminations found.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
