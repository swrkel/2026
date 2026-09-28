@extends('layouts.app')
@section('title', 'Hotel Staff Rostering')
@section('content')
<section class="content-header hm-page-header"><h1><i class="fa fa-users"></i> Staff Rostering <small>Hotel duty roster, staffing and attendance control</small></h1></section>
<section class="content hm-pos-scope">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif
<div class="row hm-kpi-row">
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active Staff</div><div class="hm-kpi-value">{{ $staffRoster['active_staff'] }}</div><div class="hm-kpi-sub">Available hotel staff</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Today Shifts</div><div class="hm-kpi-value">{{ $staffRoster['today_shifts'] }}</div><div class="hm-kpi-sub">Current duty plan</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Open Attendance</div><div class="hm-kpi-value">{{ $staffRoster['open_attendance'] }}</div><div class="hm-kpi-sub">Clocked-in not out</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Unassigned</div><div class="hm-kpi-value">{{ $staffRoster['unassigned_shifts'] }}</div><div class="hm-kpi-sub">Needs action</div></div></div>
</div>
<div class="row">
 <div class="col-md-3"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Staff Role</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.staff-roster.role') }}">@csrf
  <div class="form-group"><label>Role Name</label><input name="role_name" class="form-control" required></div>
  <div class="form-group"><label>Department</label><input name="department" class="form-control" placeholder="Front Office / HK / F&B"></div>
  <div class="form-group"><label>Standard Hours</label><input type="number" step="0.25" name="standard_hours" class="form-control"></div>
  <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Role</button></div>
 </form></div></div></div>
 <div class="col-md-3"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Staff Member</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.staff-roster.staff') }}">@csrf
  <div class="form-group"><label>Employee No</label><input name="employee_no" class="form-control" placeholder="Auto if blank"></div>
  <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
  <div class="form-group"><label>Mobile / Email</label><input name="mobile" class="form-control" placeholder="Mobile"><input name="email" class="form-control" style="margin-top:6px" placeholder="Email"></div>
  <div class="form-group"><label>Department</label><input name="department" class="form-control"></div>
  <div class="form-group"><label>Role</label><select name="role_id" class="form-control"><option value="">Select</option>@foreach($staffRoster['roles'] as $r)<option value="{{ $r->id }}">{{ $r->role_name }}</option>@endforeach</select></div>
  <div class="text-right"><button class="btn hm-btn-excel"><i class="fa fa-user-plus"></i> Save Staff</button></div>
 </form></div></div></div>
 <div class="col-md-3"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Roster Shift</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.staff-roster.shift') }}">@csrf
  <div class="form-group"><label>Shift No</label><input name="shift_no" class="form-control" placeholder="Auto if blank"></div>
  <div class="form-group"><label>Staff</label><select name="staff_id" class="form-control" required>@foreach($staffRoster['staff'] as $s)<option value="{{ $s->id }}">{{ $s->employee_no }} - {{ $s->name }}</option>@endforeach</select></div>
  <div class="form-group"><label>Date</label><input type="date" name="shift_date" class="form-control" required></div>
  <div class="form-group"><label>Start / End</label><div class="row"><div class="col-xs-6"><input type="time" name="start_time" class="form-control" required></div><div class="col-xs-6"><input type="time" name="end_time" class="form-control" required></div></div></div>
  <div class="form-group"><label>Department / Station</label><input name="department" class="form-control" placeholder="Department"><input name="station" class="form-control" style="margin-top:6px" placeholder="Station"></div>
  <div class="text-right"><button class="btn hm-btn-pdf"><i class="fa fa-calendar"></i> Save Shift</button></div>
 </form></div></div></div>
 <div class="col-md-3"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Attendance</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.staff-roster.attendance') }}">@csrf
  <div class="form-group"><label>Staff</label><select name="staff_id" class="form-control" required>@foreach($staffRoster['staff'] as $s)<option value="{{ $s->id }}">{{ $s->employee_no }} - {{ $s->name }}</option>@endforeach</select></div>
  <div class="form-group"><label>Date</label><input type="date" name="attendance_date" class="form-control" required></div>
  <div class="form-group"><label>Clock In / Out</label><div class="row"><div class="col-xs-6"><input type="time" name="clock_in" class="form-control"></div><div class="col-xs-6"><input type="time" name="clock_out" class="form-control"></div></div></div>
  <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option><option value="leave">Leave</option></select></div>
  <div class="text-right"><button class="btn hm-btn-print"><i class="fa fa-clock-o"></i> Save Attendance</button></div>
 </form></div></div></div>
</div>
<div class="box hm-card"><div class="box-header with-border"><div class="hm-toolbar"><input class="form-control hm-search-input" style="max-width:260px" placeholder="Search roster..."><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div><h3 class="box-title">Roster Shifts</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped hm-table"><thead><tr><th>Shift No</th><th>Date</th><th>Staff</th><th>Department</th><th>Station</th><th>Time</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($staffRoster['shifts'] as $sh)<tr><td>{{ $sh->shift_no }}</td><td>{{ $sh->shift_date }}</td><td>{{ $sh->staff_id }}</td><td>{{ $sh->department }}</td><td>{{ $sh->station }}</td><td>{{ $sh->start_time }} - {{ $sh->end_time }}</td><td><span class="hm-badge {{ $sh->status }}">{{ ucfirst(str_replace('_',' ',$sh->status)) }}</span></td><td><form method="POST" action="{{ route('hotel-management.staff-roster.shift-status',$sh->id) }}" class="form-inline">@csrf<select name="status" class="form-control input-sm"><option value="planned">Planned</option><option value="confirmed">Confirmed</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><button class="btn btn-xs hm-btn-add">Update</button></form></td></tr>@empty<tr><td colspan="8" class="text-center text-muted">No roster shifts yet.</td></tr>@endforelse</tbody></table></div></div>
<div class="row"><div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Staff Register</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Employee No</th><th>Name</th><th>Department</th><th>Role</th><th>Status</th></tr></thead><tbody>@forelse($staffRoster['staff'] as $s)<tr><td>{{ $s->employee_no }}</td><td>{{ $s->name }}</td><td>{{ $s->department }}</td><td>{{ $s->role_id }}</td><td><span class="hm-badge {{ $s->status }}">{{ ucfirst($s->status) }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No staff saved yet.</td></tr>@endforelse</tbody></table></div></div></div><div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Attendance Logs</h3></div><div class="box-body table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Date</th><th>Staff</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr></thead><tbody>@forelse($staffRoster['attendance'] as $a)<tr><td>{{ $a->attendance_date }}</td><td>{{ $a->staff_id }}</td><td>{{ $a->clock_in }}</td><td>{{ $a->clock_out }}</td><td><span class="hm-badge {{ $a->status }}">{{ ucfirst($a->status) }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No attendance logs yet.</td></tr>@endforelse</tbody></table></div></div></div></div>
</section>
@endsection
