@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">Leave Requests</span>
            <h1>Request & Approval</h1>
            <p>Submit, approve, reject and audit employee leave requests.</p>
        </div>
        <div class="hr-actions"><a href="{{ route('hr.leave.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div>
    </div>

    <div class="hr-grid-2">
        <div class="hr-card">
            <h3>New Leave Request</h3>
            <form method="POST" action="{{ route('hr.leave.requests.store') }}" class="hr-form">
                @csrf
                <label>Employee ID</label><input type="number" name="employee_id" required>
                <label>Leave Type</label>
                <select name="leave_type_id" required>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                <label>From Date</label><input type="date" name="from_date" required>
                <label>To Date</label><input type="date" name="to_date" required>
                <label>Reason</label><textarea name="reason"></textarea>
                <button class="hr-btn hr-btn-primary">Submit Request</button>
            </form>
        </div>

        <div class="hr-card">
            <h3>Requests</h3>
            <table class="hr-table">
                <thead><tr><th>No</th><th>Employee</th><th>Dates</th><th>Days</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($requests as $row)
                        <tr>
                            <td>{{ $row->request_no }}</td>
                            <td>{{ $row->employee_id }}</td>
                            <td>{{ $row->from_date }} - {{ $row->to_date }}</td>
                            <td>{{ $row->total_days }}</td>
                            <td><span class="hr-badge">{{ $row->status }}</span></td>
                            <td>
                                @if($row->status === 'pending')
                                    <form method="POST" action="{{ route('hr.leave.requests.approve', $row->id) }}" style="display:inline">@csrf<button class="hr-mini-btn">Approve</button></form>
                                    <form method="POST" action="{{ route('hr.leave.requests.reject', $row->id) }}" style="display:inline">@csrf<button class="hr-mini-btn hr-mini-danger">Reject</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="hr-empty">No requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
