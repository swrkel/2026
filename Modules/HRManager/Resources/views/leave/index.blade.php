@extends('hrmanager::layouts.master')

@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header', [
    'title' => 'Leave Management',
    'subtitle' => 'Leave requests, approvals, balances and calendar connected to the Employee Central Record.',
    'section' => 'Leave Management'
])

<div class="hr-card-grid">
    <div class="hr-kpi purple"><i class="fa fa-users"></i><span>Employees</span><strong>{{ number_format($stats['employees'] ?? 0) }}</strong></div>
    <div class="hr-kpi blue"><i class="fa fa-calendar"></i><span>Leave Types</span><strong>{{ number_format($stats['leave_types'] ?? 0) }}</strong></div>
    <div class="hr-kpi orange"><i class="fa fa-hourglass-half"></i><span>Pending Requests</span><strong>{{ number_format($stats['pending'] ?? 0) }}</strong></div>
    <div class="hr-kpi green"><i class="fa fa-check-circle"></i><span>Approved Requests</span><strong>{{ number_format($stats['approved'] ?? 0) }}</strong></div>
</div>

<div class="leave-grid">
    <div class="hr-panel">
        <h3>New Leave Request</h3>
        <form method="POST" action="{{ route('hrmanager.leave.store') }}" class="leave-form">
            @csrf
            <label>Employee</label>
            <select name="employee_id" required>
                <option value="">Select Employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->employee_no ?? $employee->id }} - {{ $employee->full_name ?? '' }}</option>
                @endforeach
            </select>

            <label>Leave Type</label>
            <select name="leave_type_id" required>
                <option value="">Select Leave Type</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}">{{ $type->name ?? ('Leave Type #' . $type->id) }}</option>
                @endforeach
            </select>

            <label>From Date</label>
            <input type="date" name="from_date" required>

            <label>To Date</label>
            <input type="date" name="to_date" required>

            <label>Reason</label>
            <textarea name="reason"></textarea>

            <button class="hr-primary-btn"><i class="fa fa-save"></i> Submit Request</button>
        </form>
    </div>

    <div class="hr-panel wide">
        <div class="hr-panel-head">
            <h3>Leave Requests</h3>
        </div>

        @include('hrmanager::components.list-toolbar', ['searchPlaceholder' => 'Search leave request no, employee, status'])

        <table class="hr-table">
            <thead>
                <tr>
                    <th>Request No</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                    <th>Status</th>
                    <th>Approval</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $row)
                    <tr>
                        <td>{{ $row->request_no }}</td>
                        <td>#{{ $row->employee_id }}</td>
                        <td>{{ $row->leave_type_id }}</td>
                        <td>{{ $row->from_date }}</td>
                        <td>{{ $row->to_date }}</td>
                        <td>{{ $row->total_days }}</td>
                        <td><em class="status-pill">{{ ucfirst($row->status ?? 'Pending') }}</em></td>
                        <td>
                            @if(($row->status ?? '') === 'pending')
                                <form method="POST" action="{{ route('hrmanager.leave.approve', $row->id) }}" style="display:inline">@csrf<button class="mini-btn approve">Approve</button></form>
                                <form method="POST" action="{{ route('hrmanager.leave.reject', $row->id) }}" style="display:inline">@csrf<button class="mini-btn reject">Reject</button></form>
                            @else
                                <span class="muted">Completed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-row">No leave requests found.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($requests, 'links'))
            {{ $requests->links() }}
        @endif
    </div>
</div>

<div class="leave-grid">
    <div class="hr-panel">
        <h3>Leave Balances</h3>
        <table class="hr-table compact-table">
            <thead><tr><th>Employee</th><th>Type</th><th>Year</th><th>Closing</th></tr></thead>
            <tbody>
                @forelse($balances as $row)
                    <tr><td>#{{ $row->employee_id }}</td><td>{{ $row->leave_type_id }}</td><td>{{ $row->leave_year }}</td><td>{{ $row->closing_balance }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty-row">No leave balances found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="hr-panel wide">
        <h3>Leave Calendar</h3>
        <table class="hr-table compact-table">
            <thead><tr><th>Date</th><th>Employee</th><th>Leave Type</th><th>Status</th><th>Title</th></tr></thead>
            <tbody>
                @forelse($calendar as $row)
                    <tr><td>{{ $row->calendar_date }}</td><td>#{{ $row->employee_id }}</td><td>{{ $row->leave_type_id }}</td><td><em class="status-pill">{{ $row->calendar_status }}</em></td><td>{{ $row->display_title }}</td></tr>
                @empty
                    <tr><td colspan="5" class="empty-row">No leave calendar days found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
