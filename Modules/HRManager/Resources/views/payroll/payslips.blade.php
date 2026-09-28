@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Payroll</span><h1>Payslips</h1><p>Employee payslip foundation and payroll payment status.</p></div><div class="hr-actions"><a href="{{ route('hr.payroll.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-card">
        <table class="hr-table"><thead><tr><th>Payslip No</th><th>Employee</th><th>Basic</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead><tbody>
            @forelse($payslips as $row)<tr><td>{{ $row->payslip_no }}</td><td>{{ $row->employee_id }}</td><td>{{ number_format($row->basic_salary,2) }}</td><td>{{ number_format($row->gross_salary,2) }}</td><td>{{ number_format($row->deduction_total,2) }}</td><td>{{ number_format($row->net_salary,2) }}</td><td><span class="hr-badge">{{ $row->payment_status }}</span></td></tr>
            @empty<tr><td colspan="7" class="hr-empty">No payslips found.</td></tr>@endforelse
        </tbody></table>{{ $payslips->links() }}
    </div>
</div>
@endsection
