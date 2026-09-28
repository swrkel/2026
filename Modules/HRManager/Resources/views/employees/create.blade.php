@extends('hrmanager::layouts.master')
@section('hr_content')
<div class="hr-page">
@include('hrmanager::dashboard.partials.header', ['title'=>'Add Employee','subtitle'=>'Create the central employee record used by all HR Manager sub modules.','section'=>'Add Employee'])
<form method="POST" action="{{ route('hrmanager.employees.store') }}" class="hr-form-panel">
@csrf
<div class="hr-form-section"><h3>Personal Details</h3><div class="hr-form-grid">
<label>Employee No<input name="employee_no" placeholder="Auto if blank"></label>
<label>Full Name<input name="full_name" required></label>
<label>First Name<input name="first_name"></label>
<label>Last Name<input name="last_name"></label>
<label>Gender<select name="gender"><option value="">Select</option><option>Male</option><option>Female</option></select></label>
<label>NIC No<input name="nic_no"></label>
<label>Date of Birth<input type="date" name="dob"></label>
<label>Mobile<input name="mobile"></label>
<label>Email<input type="email" name="email"></label>
<label>Address<textarea name="address"></textarea></label>
</div></div>
<div class="hr-form-section"><h3>Employment Details</h3><div class="hr-form-grid">
<label>Department ID<input type="number" name="department_id"></label>
<label>Designation ID<input type="number" name="designation_id"></label>
<label>Shift ID<input type="number" name="shift_id"></label>
<label>Joining Date<input type="date" name="joining_date"></label>
<label>Employment Type<select name="employment_type"><option>Permanent</option><option>Contract</option><option>Temporary</option><option>Intern</option></select></label>
<label>Employment Status<select name="employment_status"><option value="active">Active</option><option value="probation">Probation</option><option value="contract">Contract</option></select></label>
<label>Basic Salary<input type="number" step="0.0001" name="basic_salary"></label>
</div></div>
<div class="hr-form-actions"><a href="{{ route('hrmanager.employees.index') }}" class="hr-light-btn">Cancel</a><button class="hr-primary-btn">Save Employee</button></div>
</form>
</div>
@endsection
