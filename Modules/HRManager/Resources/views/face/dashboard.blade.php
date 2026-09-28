@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">HR Face Recognition</span>
            <h1>Face Attendance Command Centre</h1>
            <p>Enrollment, consent, kiosk attempts and attendance verification audit trail.</p>
        </div>
        <div class="hr-actions">
            <a href="{{ route('hr.face.enrollment') }}" class="hr-btn hr-btn-primary">Start Enrollment</a>
            <a href="{{ route('hr.face.attempts') }}" class="hr-btn hr-btn-light">View Attempts</a>
        </div>
    </div>

    <div class="hr-stat-grid">
        <div class="hr-stat-card"><span>Recent Enrollments</span><strong>{{ $sessions->count() }}</strong></div>
        <div class="hr-stat-card"><span>Recent Attempts</span><strong>{{ $attempts->count() }}</strong></div>
        <div class="hr-stat-card"><span>Provider</span><strong>Ready</strong></div>
        <div class="hr-stat-card"><span>Fallback</span><strong>PIN / Manual</strong></div>
    </div>

    <div class="hr-grid-2">
        <div class="hr-card">
            <h3>Latest Enrollment Sessions</h3>
            <table class="hr-table">
                <thead><tr><th>Session</th><th>Employee</th><th>Status</th><th>Started</th></tr></thead>
                <tbody>
                    @forelse($sessions as $row)
                        <tr><td>{{ $row->session_code }}</td><td>{{ $row->employee_id }}</td><td><span class="hr-badge">{{ $row->status }}</span></td><td>{{ $row->started_at }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="hr-empty">No sessions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="hr-card">
            <h3>Latest Face Attempts</h3>
            <table class="hr-table">
                <thead><tr><th>Time</th><th>Type</th><th>Decision</th><th>Score</th></tr></thead>
                <tbody>
                    @forelse($attempts as $row)
                        <tr><td>{{ $row->attempt_time }}</td><td>{{ $row->attempt_type }}</td><td><span class="hr-badge">{{ $row->decision }}</span></td><td>{{ $row->confidence_score }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="hr-empty">No attempts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
