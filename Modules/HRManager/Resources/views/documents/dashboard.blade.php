@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div><span class="hr-eyebrow">HR Documents</span><h1>Documents & Employee Lifecycle</h1><p>Contracts, certificates, warnings, reviews, resignations and terminations.</p></div>
        <div class="hr-actions">
            <a href="{{ route('hr.documents.contracts') }}" class="hr-btn hr-btn-primary">Contracts</a>
            <a href="{{ route('hr.documents.certificates') }}" class="hr-btn hr-btn-light">Certificates</a>
            <a href="{{ route('hr.documents.warnings') }}" class="hr-btn hr-btn-light">Warnings</a>
            <a href="{{ route('hr.documents.reviews') }}" class="hr-btn hr-btn-light">Reviews</a>
            <a href="{{ route('hr.documents.exits') }}" class="hr-btn hr-btn-light">Exits</a>
        </div>
    </div>
    <div class="hr-stat-grid">
        <div class="hr-stat-card"><span>Contracts</span><strong>{{ $contractCount }}</strong></div>
        <div class="hr-stat-card"><span>Certificates</span><strong>{{ $certificateCount }}</strong></div>
        <div class="hr-stat-card"><span>Warnings</span><strong>{{ $warningCount }}</strong></div>
        <div class="hr-stat-card"><span>Reviews</span><strong>{{ $reviewCount }}</strong></div>
    </div>
    <div class="hr-card">
        <h3>Pending Expiry Alerts</h3>
        <table class="hr-table"><thead><tr><th>Employee</th><th>Document</th><th>Expiry Date</th><th>Status</th></tr></thead><tbody>
            @forelse($expiryAlerts as $row)
                <tr><td>{{ $row->employee_id }}</td><td>{{ $row->document_title }}</td><td>{{ $row->expiry_date }}</td><td><span class="hr-badge">{{ $row->alert_status }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="hr-empty">No pending expiry alerts.</td></tr>
            @endforelse
        </tbody></table>
    </div>
</div>
@endsection
