@extends('hrmanager::layouts.hr')
@section('title',($employee->exists?'Edit':'Add').' Employee | HR Manager')
@section('content')
<div class="hr-page-head"><div><h1>{{ $employee->exists ? 'Edit Employee' : 'Add Employee' }}</h1><p>Complete employee profile, job information and emergency contact details.</p></div><a class="hr-btn hr-btn-light" href="{{ route('hr.employees.index') }}">Back</a></div>
@if($errors->any())<div class="hr-alert danger">Please check the highlighted fields and try again.</div>@endif
<form method="POST" action="{{ $employee->exists ? route('hr.employees.update',$employee) : route('hr.employees.store') }}" class="hr-form">
@csrf @if($employee->exists) @method('PUT') @endif
<div class="hr-card"><h2>Basic Details</h2><div class="hr-grid-4">
<label>Employee Code *<input name="employee_code" value="{{ old('employee_code',$employee->employee_code) }}" required></label>
<label>First Name *<input name="first_name" value="{{ old('first_name',$employee->first_name) }}" required></label>
<label>Last Name<input name="last_name" value="{{ old('last_name',$employee->last_name) }}"></label>
<label>Display Name<input name="display_name" value="{{ old('display_name',$employee->display_name) }}"></label>
<label>NIC<input name="nic_no" value="{{ old('nic_no',$employee->nic_no) }}"></label>
<label>Passport<input name="passport_no" value="{{ old('passport_no',$employee->passport_no) }}"></label>
<label>Gender<select name="gender"><option value="">Select</option>@foreach(['male','female','other'] as $g)<option value="{{ $g }}" @selected(old('gender',$employee->gender)==$g)>{{ ucfirst($g) }}</option>@endforeach</select></label>
<label>Date of Birth<input type="date" name="date_of_birth" value="{{ old('date_of_birth',optional($employee->date_of_birth)->format('Y-m-d')) }}"></label>
</div></div>
<div class="hr-card"><h2>Contact Details</h2><div class="hr-grid-4">
<label>Mobile<input name="mobile" value="{{ old('mobile',$employee->mobile) }}"></label><label>Phone<input name="phone" value="{{ old('phone',$employee->phone) }}"></label><label>Email<input type="email" name="email" value="{{ old('email',$employee->email) }}"></label><label>City<input name="city" value="{{ old('city',$employee->city) }}"></label>
<label class="span-2">Address Line 1<input name="address_line_1" value="{{ old('address_line_1',$employee->address_line_1) }}"></label><label class="span-2">Address Line 2<input name="address_line_2" value="{{ old('address_line_2',$employee->address_line_2) }}"></label>
</div></div>
<div class="hr-card"><h2>Job Details</h2><div class="hr-grid-4">
<label>Department ID<input type="number" name="department_id" value="{{ old('department_id',$employee->department_id) }}"></label><label>Designation ID<input type="number" name="designation_id" value="{{ old('designation_id',$employee->designation_id) }}"></label><label>Branch ID<input type="number" name="branch_id" value="{{ old('branch_id',$employee->branch_id) }}"></label><label>Shift ID<input type="number" name="shift_id" value="{{ old('shift_id',$employee->shift_id) }}"></label>
<label>Joining Date<input type="date" name="joining_date" value="{{ old('joining_date',optional($employee->joining_date)->format('Y-m-d')) }}"></label><label>Employment Type<select name="employment_type">@foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','probation'=>'Probation','intern'=>'Intern'] as $k=>$v)<option value="{{ $k }}" @selected(old('employment_type',$employee->employment_type)==$k)>{{ $v }}</option>@endforeach</select></label><label>Status<select name="employee_status">@foreach(['active'=>'Active','inactive'=>'Inactive','terminated'=>'Terminated','resigned'=>'Resigned'] as $k=>$v)<option value="{{ $k }}" @selected(old('employee_status',$employee->employee_status)==$k)>{{ $v }}</option>@endforeach</select></label><label>Basic Salary<input type="number" step="0.0001" name="basic_salary" value="{{ old('basic_salary',$employee->basic_salary) }}"></label>
</div></div>
<div class="hr-card"><h2>Bank / Statutory</h2><div class="hr-grid-4"><label>Bank<input name="bank_name" value="{{ old('bank_name',$employee->bank_name) }}"></label><label>Branch<input name="bank_branch" value="{{ old('bank_branch',$employee->bank_branch) }}"></label><label>Account No<input name="bank_account_no" value="{{ old('bank_account_no',$employee->bank_account_no) }}"></label><label>EPF No<input name="epf_no" value="{{ old('epf_no',$employee->epf_no) }}"></label><label>ETF No<input name="etf_no" value="{{ old('etf_no',$employee->etf_no) }}"></label></div></div>
<div class="hr-card"><h2>Emergency Contact</h2><div class="hr-grid-4"><label>Name<input name="emergency[contact_name]" value="{{ old('emergency.contact_name',optional($emergency ?? null)->contact_name) }}"></label><label>Relationship<input name="emergency[relationship]" value="{{ old('emergency.relationship',optional($emergency ?? null)->relationship) }}"></label><label>Mobile<input name="emergency[mobile]" value="{{ old('emergency.mobile',optional($emergency ?? null)->mobile) }}"></label><label>Phone<input name="emergency[phone]" value="{{ old('emergency.phone',optional($emergency ?? null)->phone) }}"></label><label class="span-4">Address<input name="emergency[address]" value="{{ old('emergency.address',optional($emergency ?? null)->address) }}"></label></div></div>
<div class="hr-card"><h2>Notes</h2><textarea name="notes" rows="4">{{ old('notes',$employee->notes) }}</textarea></div>
<div class="hr-actions"><button class="hr-btn hr-btn-primary">{{ $employee->exists ? 'Update Employee' : 'Save Employee' }}</button><a class="hr-btn hr-btn-light" href="{{ route('hr.employees.index') }}">Cancel</a></div>
</form>
@endsection
