@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero">
        <div>
            <span class="hr-eyebrow">HR Payroll</span>
            <h1>Payroll Command Centre</h1>
            <p>Salary structures, payroll periods, payroll runs and payslip foundation.</p>
        </div>
        <div class="hr-actions">
            <a href="{{ route('hr.payroll.periods') }}" class="hr-btn hr-btn-light">Periods</a>
            <a href="{{ route('hr.payroll.salary_structures') }}" class="hr-btn hr-btn-light">Salary Structures</a>
            <a href="{{ route('hr.payroll.runs') }}" class="hr-btn hr-btn-primary">Payroll Runs</a>
            <a href="{{ route('hr.payroll.payslips') }}" class="hr-btn hr-btn-light">Payslips</a>
        </div>
    </div>
    <div class="hr-stat-grid">
        <div class="hr-stat-card"><span>Periods</span><strong>{{ $periodsCount }}</strong></div>
        <div class="hr-stat-card"><span>Salary Structures</span><strong>{{ $structuresCount }}</strong></div>
        <div class="hr-stat-card"><span>Payroll Runs</span><strong>{{ $runsCount }}</strong></div>
        <div class="hr-stat-card"><span>Net Total</span><strong>{{ number_format($netTotal, 2) }}</strong></div>
    </div>
    <div class="hr-card">
        <h3>Latest Payroll Runs</h3>
        <table class="hr-table">
            <thead><tr><th>Run No</th><th>Date</th><th>Status</th><th>Employees</th><th>Gross</th><th>Deductions</th><th>Net</th></tr></thead>
            <tbody>
                @forelse($runs as $row)
                    <tr><td>{{ $row->run_no }}</td><td>{{ $row->run_date }}</td><td><span class="hr-badge">{{ $row->status }}</span></td><td>{{ $row->employee_count }}</td><td>{{ number_format($row->gross_total,2) }}</td><td>{{ number_format($row->deduction_total,2) }}</td><td>{{ number_format($row->net_total,2) }}</td></tr>
                @empty
                    <tr><td colspan="7" class="hr-empty">No payroll runs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
