@extends('hrmanager::layouts.app')
@section('hrm_title','Edit Employee')
@section('hrm_subtitle',$employee->display_name)
@section('hrm_content')
<form class="hrm-card hrm-form" method="POST" action="{{ route('hr-manager.employees.update',$employee) }}">@csrf @method('PUT')
<div class="hrm-form-grid"><label>Employee Code<input name="employee_code" value="{{ $employee->employee_code }}" required></label><label>First Name<input name="first_name" value="{{ $employee->first_name }}" required></label><label>Last Name<input name="last_name" value="{{ $employee->last_name }}"></label><label>Mobile<input name="mobile" value="{{ $employee->mobile }}"></label><label>Email<input name="email" type="email" value="{{ $employee->email }}"></label><label>Joining Date<input name="joining_date" type="date" value="{{ $employee->joining_date }}"></label><label>Employment Type<input name="employment_type" value="{{ $employee->employment_type }}"></label><label>Status<select name="status"><option value="active" @selected($employee->status=='active')>Active</option><option value="inactive" @selected($employee->status=='inactive')>Inactive</option></select></label></div>
<button class="hrm-btn primary">Update Employee</button>
</form>
@endsection
