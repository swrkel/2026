@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">Face Attempts</span>
            <h1>Face Attendance Attempt Logs</h1>
            <p>Track successful and failed verification attempts with confidence score and device details.</p>
        </div>
        <div class="hr-actions">
            <a href="{{ route('hr.face.dashboard') }}" class="hr-btn hr-btn-light">Back</a>
        </div>
    </div>

    <div class="hr-card">
        <table class="hr-table">
            <thead>
                <tr><th>Time</th><th>Employee</th><th>Type</th><th>Decision</th><th>Matched</th><th>Score</th><th>Reason</th></tr>
            </thead>
            <tbody>
                @forelse($attempts as $row)
                    <tr>
                        <td>{{ $row->attempt_time }}</td>
                        <td>{{ $row->employee_id }}</td>
                        <td>{{ $row->attempt_type }}</td>
                        <td><span class="hr-badge">{{ $row->decision }}</span></td>
                        <td>{{ $row->matched ? 'Yes' : 'No' }}</td>
                        <td>{{ $row->confidence_score }}</td>
                        <td>{{ $row->failure_reason }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="hr-empty">No face attendance attempts found.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $attempts->links() }}
    </div>
</div>
@endsection
