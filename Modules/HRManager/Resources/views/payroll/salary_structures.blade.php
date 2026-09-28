@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Payroll Setup</span><h1>Salary Structures</h1><p>Employee basic salary, bank details and statutory references.</p></div><div class="hr-actions"><a href="{{ route('hr.payroll.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-grid-2">
        <div class="hr-card"><h3>Add Salary Structure</h3>
            <form method="POST" action="{{ route('hr.payroll.salary_structures.store') }}" class="hr-form">@csrf
                <label>Employee ID</label><input type="number" name="employee_id" required>
                <label>Effective From</label><input type="date" name="effective_from" required>
                <label>Basic Salary</label><input type="number" step="0.0001" name="basic_salary" required>
                <label>Frequency</label><select name="salary_frequency"><option value="monthly">Monthly</option><option value="daily">Daily</option><option value="hourly">Hourly</option></select>
                <label>Bank Name</label><input name="bank_name">
                <label>Bank Account No</label><input name="bank_account_no">
                <label>EPF No</label><input name="epf_no">
                <label>ETF No</label><input name="etf_no">
                <button class="hr-btn hr-btn-primary">Save Structure</button>
            </form>
        </div>
        <div class="hr-card"><h3>Salary Structures</h3>
            <table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Effective</th><th>Basic</th><th>Status</th></tr></thead><tbody>
                @forelse($structures as $row)<tr><td>{{ $row->structure_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->effective_from }}</td><td>{{ number_format($row->basic_salary,2) }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
                @empty<tr><td colspan="5" class="hr-empty">No salary structures found.</td></tr>@endforelse
            </tbody></table>{{ $structures->links() }}
        </div>
    </div>
</div>
@endsection
