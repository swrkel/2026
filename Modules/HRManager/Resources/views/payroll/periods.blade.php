@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Payroll Setup</span><h1>Payroll Periods</h1><p>Create monthly or custom payroll periods.</p></div><div class="hr-actions"><a href="{{ route('hr.payroll.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-grid-2">
        <div class="hr-card"><h3>Add Period</h3>
            <form method="POST" action="{{ route('hr.payroll.periods.store') }}" class="hr-form">@csrf
                <label>Code</label><input name="period_code" placeholder="PAY-2026-07">
                <label>Name</label><input name="period_name" required placeholder="July 2026 Payroll">
                <label>From Date</label><input type="date" name="from_date" required>
                <label>To Date</label><input type="date" name="to_date" required>
                <label>Pay Date</label><input type="date" name="pay_date">
                <button class="hr-btn hr-btn-primary">Save Period</button>
            </form>
        </div>
        <div class="hr-card"><h3>Periods List</h3>
            <table class="hr-table"><thead><tr><th>Code</th><th>Name</th><th>From</th><th>To</th><th>Status</th></tr></thead><tbody>
                @forelse($periods as $row)<tr><td>{{ $row->period_code }}</td><td>{{ $row->period_name }}</td><td>{{ $row->from_date }}</td><td>{{ $row->to_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
                @empty<tr><td colspan="5" class="hr-empty">No payroll periods found.</td></tr>@endforelse
            </tbody></table>{{ $periods->links() }}
        </div>
    </div>
</div>
@endsection
