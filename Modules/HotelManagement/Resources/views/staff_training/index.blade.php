@extends('layouts.app')
@section('title', 'Hotel Staff Training')
@section('content')
<section class="content-header hm-page-header"><h1><i class="fa fa-graduation-cap"></i> Staff Training & Compliance <small>Hotel skill, safety and compliance training control</small></h1></section>
<section class="content hm-pos-scope">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif
<div class="row hm-kpi-row">
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active Courses</div><div class="hm-kpi-value">{{ $staffTraining['active_courses'] }}</div><div class="hm-kpi-sub">Ready for assignment</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Planned Sessions</div><div class="hm-kpi-value">{{ $staffTraining['planned_sessions'] }}</div><div class="hm-kpi-sub">Upcoming training</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Passed Records</div><div class="hm-kpi-value">{{ $staffTraining['completed_records'] }}</div><div class="hm-kpi-sub">Completed successfully</div></div></div>
 <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Expired</div><div class="hm-kpi-value">{{ $staffTraining['expired_records'] }}</div><div class="hm-kpi-sub">Needs renewal</div></div></div>
</div>
<div class="row">
 <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Training Course</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.staff-training.course') }}">@csrf
  <div class="form-group"><label>Course Code / Name</label><input name="course_code" class="form-control" placeholder="Auto if blank"><input name="course_name" class="form-control" style="margin-top:6px" required placeholder="Course name"></div>
  <div class="form-group"><label>Department / Type</label><div class="row"><div class="col-xs-6"><input name="department" class="form-control" placeholder="Department"></div><div class="col-xs-6"><select name="training_type" class="form-control" required><option value="safety">Safety</option><option value="service">Service</option><option value="technical">Technical</option><option value="compliance">Compliance</option></select></div></div></div>
  <div class="form-group"><label>Validity Days</label><input type="number" name="validity_days" class="form-control" min="0" placeholder="0 = no expiry"></div>
  <div class="checkbox"><label><input type="checkbox" name="is_mandatory" value="1"> Mandatory training</label></div>
  <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
  <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Course</button></div>
 </form></div></div></div>
 <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Plan Session</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.staff-training.session') }}">@csrf
  <div class="form-group"><label>Course</label><select name="course_id" class="form-control" required><option value="">Select Course</option>@foreach($staffTraining['courses'] as $c)<option value="{{ $c->id }}">{{ $c->course_code }} - {{ $c->course_name }}</option>@endforeach</select></div>
  <div class="form-group"><label>Trainer / Venue</label><input name="trainer_name" class="form-control" placeholder="Trainer"><input name="venue" class="form-control" style="margin-top:6px" placeholder="Venue"></div>
  <div class="form-group"><label>Date / Time</label><input type="date" name="training_date" class="form-control" required><div class="row" style="margin-top:6px"><div class="col-xs-6"><input type="time" name="start_time" class="form-control"></div><div class="col-xs-6"><input type="time" name="end_time" class="form-control"></div></div></div>
  <div class="form-group"><label>Capacity</label><input type="number" name="capacity" class="form-control" min="0"></div>
  <div class="text-right"><button class="btn hm-btn-excel"><i class="fa fa-calendar"></i> Plan Session</button></div>
 </form></div></div></div>
 <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Assign Staff</h3></div><div class="box-body"><form method="POST" action="{{ route('hotel-management.staff-training.assign') }}">@csrf
  <div class="form-group"><label>Session</label><select name="session_id" class="form-control" required><option value="">Select Session</option>@foreach($staffTraining['sessions'] as $s)<option value="{{ $s->id }}">{{ $s->session_no }} - {{ $s->training_date }}</option>@endforeach</select></div>
  <div class="form-group"><label>Staff</label><select name="staff_id" class="form-control" required><option value="">Select Staff</option>@foreach($staffTraining['staff'] as $st)<option value="{{ $st->id }}">{{ $st->employee_no ?? $st->id }} - {{ $st->name ?? 'Staff' }}</option>@endforeach</select></div>
  <div class="form-group"><label>Status / Score</label><div class="row"><div class="col-xs-6"><select name="attendance_status" class="form-control"><option value="assigned">Assigned</option><option value="attended">Attended</option><option value="absent">Absent</option></select></div><div class="col-xs-6"><input type="number" step="0.01" name="score" class="form-control" placeholder="Score"></div></div></div>
  <div class="form-group"><label>Result</label><select name="result_status" class="form-control"><option value="assigned">Assigned</option><option value="passed">Passed</option><option value="failed">Failed</option><option value="expired">Expired</option></select></div>
  <div class="text-right"><button class="btn hm-btn-pdf"><i class="fa fa-user-plus"></i> Save Assignment</button></div>
 </form></div></div></div>
</div>
<div class="box hm-card"><div class="box-header with-border"><div class="hm-toolbar"><input class="form-control hm-search-input" style="max-width:260px" placeholder="Search training..."><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div><h3 class="box-title">Training Records</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped hm-table"><thead><tr><th>Session</th><th>Course</th><th>Staff</th><th>Attendance</th><th>Score</th><th>Result</th><th>Completed</th><th>Valid Until</th><th>Update</th></tr></thead><tbody>@forelse($staffTraining['records'] as $r)<tr><td>{{ $r->session_id }}</td><td>{{ $r->course_id }}</td><td>{{ $r->staff_id }}</td><td><span class="hm-badge {{ $r->attendance_status }}">{{ ucfirst($r->attendance_status) }}</span></td><td>{{ $r->score }}</td><td><span class="hm-badge {{ $r->result_status }}">{{ ucfirst($r->result_status) }}</span></td><td>{{ $r->completed_at }}</td><td>{{ $r->valid_until }}</td><td><form method="POST" action="{{ route('hotel-management.staff-training.result',$r->id) }}" class="form-inline">@csrf<select name="attendance_status" class="form-control input-sm"><option value="attended">Attended</option><option value="absent">Absent</option><option value="assigned">Assigned</option></select><input type="number" step="0.01" name="score" class="form-control input-sm" style="width:70px" placeholder="Score"><select name="result_status" class="form-control input-sm"><option value="passed">Passed</option><option value="failed">Failed</option><option value="expired">Expired</option><option value="assigned">Assigned</option></select><button class="btn btn-xs hm-btn-add">Save</button></form></td></tr>@empty<tr><td colspan="9" class="text-center text-muted">No training records yet.</td></tr>@endforelse</tbody></table></div></div>
<div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Training Control Notes</h3></div><div class="box-body"><ul class="hm-note-list">@foreach($staffTraining['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div>
</section>
@endsection
