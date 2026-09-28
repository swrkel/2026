@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header', ['title'=>$employee->full_name,'subtitle'=>'Central profile connected with attendance, face attendance, leave, payroll, performance, recruitment and assets.','section'=>'Employee Profile'])
<div class="profile-header">
    <div class="profile-avatar">{{ strtoupper(substr($employee->full_name ?? 'E', 0, 1)) }}</div>
    <div><h2>{{ $employee->full_name }}</h2><p>{{ $employee->employee_no }} · {{ $employee->mobile }} · {{ $employee->email }}</p><div class="profile-tags"><span>{{ $employee->employment_type ?? 'Employee' }}</span><span>{{ $employee->employment_status ?? 'Active' }}</span></div></div>
</div>
<div class="profile-tabs"><a class="active">Overview</a><a>Personal</a><a>Employment</a><a>Attendance</a><a>Leave</a><a>Payroll</a><a>Documents</a><a>Performance</a><a>Assets</a><a>Activity</a></div>
<div class="profile-grid">
    <div class="hr-panel"><h3>Employment Overview</h3><div class="detail-list"><p><span>Department</span><strong>{{ $employee->department_id }}</strong></p><p><span>Designation</span><strong>{{ $employee->designation_id }}</strong></p><p><span>Shift</span><strong>{{ $employee->shift_id }}</strong></p><p><span>Joining Date</span><strong>{{ $employee->joining_date }}</strong></p><p><span>Basic Salary</span><strong>{{ number_format($employee->basic_salary ?? 0, 2) }}</strong></p></div></div>
    <div class="hr-panel"><h3>Linked HR Records</h3><div class="mini-grid"><div><span>Attendance</span><strong>{{ $tabs['attendance']->count() }}</strong></div><div><span>Leave</span><strong>{{ $tabs['leave']->count() }}</strong></div><div><span>Payroll</span><strong>{{ $tabs['payroll']->count() }}</strong></div><div><span>Assets</span><strong>{{ $tabs['assets']->count() }}</strong></div></div></div>
</div>
<div class="hr-panel"><h3>Central Record Sections</h3><div class="record-section-grid">@foreach(['family'=>'Family','education'=>'Education','experience'=>'Experience','skills'=>'Skills','banks'=>'Bank Accounts','assets'=>'Assets','notes'=>'Notes','activity'=>'Activity Log'] as $key => $label)<div class="record-box"><strong>{{ $label }}</strong><span>{{ $tabs[$key]->count() }} Records</span></div>@endforeach</div></div>
</div>
@endsection
