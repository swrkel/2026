@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">HR Leave Management</span>
            <h1>Leave Command Centre</h1>
            <p>Leave requests, approvals, balances, entitlements and employee leave calendar foundation.</p>
        </div>
        <div class="hr-actions">
            <a href="{{ route('hr.leave.requests') }}" class="hr-btn hr-btn-primary">New Request</a>
            <a href="{{ route('hr.leave.types') }}" class="hr-btn hr-btn-light">Leave Types</a>
            <a href="{{ route('hr.leave.balances') }}" class="hr-btn hr-btn-light">Balances</a>
        </div>
    </div>

    <div class="hr-stat-grid">
        <div class="hr-stat-card"><span>Pending</span><strong>{{ $pendingCount }}</strong></div>
        <div class="hr-stat-card"><span>Approved</span><strong>{{ $approvedCount }}</strong></div>
        <div class="hr-stat-card"><span>Rejected</span><strong>{{ $rejectedCount }}</strong></div>
        <div class="hr-stat-card"><span>Leave Types</span><strong>{{ $typesCount }}</strong></div>
    </div>

    <div class="hr-card">
        <h3>Latest Leave Requests</h3>
        <table class="hr-table">
            <thead><tr><th>Request No</th><th>Employee</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($requests as $row)
                    <tr>
                        <td>{{ $row->request_no }}</td>
                        <td>{{ $row->employee_id }}</td>
                        <td>{{ $row->from_date }}</td>
                        <td>{{ $row->to_date }}</td>
                        <td>{{ $row->total_days }}</td>
                        <td><span class="hr-badge">{{ ucfirst($row->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hr-empty">No leave requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
