@extends('hrmanager::layouts.master')

@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">Leave Balances</span>
            <h1>Employee Leave Entitlements</h1>
            <p>Opening balances, entitlement, used days, pending days and closing balances.</p>
        </div>
        <div class="hr-actions"><a href="{{ route('hr.leave.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div>
    </div>

    <div class="hr-card">
        <table class="hr-table">
            <thead><tr><th>Year</th><th>Employee</th><th>Leave Type</th><th>Entitled</th><th>Used</th><th>Pending</th><th>Closing</th></tr></thead>
            <tbody>
                @forelse($balances as $row)
                    <tr>
                        <td>{{ $row->leave_year }}</td>
                        <td>{{ $row->employee_id }}</td>
                        <td>{{ $row->leave_type_id }}</td>
                        <td>{{ $row->entitled_days }}</td>
                        <td>{{ $row->used_days }}</td>
                        <td>{{ $row->pending_days }}</td>
                        <td>{{ $row->closing_balance }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="hr-empty">No leave balances found.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $balances->links() }}
    </div>
</div>
@endsection
