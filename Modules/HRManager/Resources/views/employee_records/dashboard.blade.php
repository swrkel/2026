@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div><span class="hr-eyebrow">HR Employee Records</span><h1>Documents, Contracts & Performance</h1><p>Contracts, certifications, appraisals, warnings, resignation and termination records.</p></div>
        <div class="hr-actions">
            <a href="{{ route('hr.employee_records.contracts') }}" class="hr-btn hr-btn-primary">Contracts</a>
            <a href="{{ route('hr.employee_records.appraisals') }}" class="hr-btn hr-btn-light">Appraisals</a>
            <a href="{{ route('hr.employee_records.warnings') }}" class="hr-btn hr-btn-light">Warnings</a>
            <a href="{{ route('hr.employee_records.exit') }}" class="hr-btn hr-btn-light">Exit Records</a>
        </div>
    </div>
    <div class="hr-stat-grid">
        <div class="hr-stat-card"><span>Contracts</span><strong>{{ $contractsCount }}</strong></div>
        <div class="hr-stat-card"><span>Certifications</span><strong>{{ $certificationsCount }}</strong></div>
        <div class="hr-stat-card"><span>Appraisals</span><strong>{{ $appraisalsCount }}</strong></div>
        <div class="hr-stat-card"><span>Warnings</span><strong>{{ $warningsCount }}</strong></div>
    </div>
    <div class="hr-card"><h3>Latest Contracts</h3>
        <table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Type</th><th>Start</th><th>End</th><th>Status</th></tr></thead><tbody>
        @forelse($contracts as $row)<tr><td>{{ $row->contract_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->contract_type }}</td><td>{{ $row->start_date }}</td><td>{{ $row->end_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
        @empty<tr><td colspan="6" class="hr-empty">No contracts found.</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
