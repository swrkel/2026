@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Payroll Processing</span><h1>Payroll Runs</h1><p>Create and process draft payroll runs for selected payroll periods.</p></div><div class="hr-actions"><a href="{{ route('hr.payroll.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-grid-2">
        <div class="hr-card"><h3>Create Draft Run</h3>
            <form method="POST" action="{{ route('hr.payroll.runs.store') }}" class="hr-form">@csrf
                <label>Payroll Period</label><select name="payroll_period_id" required>@foreach($periods as $period)<option value="{{ $period->id }}">{{ $period->period_name }}</option>@endforeach</select>
                <label>Remarks</label><textarea name="remarks"></textarea>
                <button class="hr-btn hr-btn-primary">Create Draft Run</button>
            </form>
        </div>
        <div class="hr-card"><h3>Payroll Runs</h3>
            <table class="hr-table"><thead><tr><th>Run No</th><th>Date</th><th>Status</th><th>Employees</th><th>Net</th></tr></thead><tbody>
                @forelse($runs as $row)<tr><td>{{ $row->run_no }}</td><td>{{ $row->run_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td><td>{{ $row->employee_count }}</td><td>{{ number_format($row->net_total,2) }}</td></tr>
                @empty<tr><td colspan="5" class="hr-empty">No payroll runs found.</td></tr>@endforelse
            </tbody></table>{{ $runs->links() }}
        </div>
    </div>
</div>
@endsection
