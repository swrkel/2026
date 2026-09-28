@extends('layouts.app')
@section('title', 'Add Doctor Schedule')
@section('content')
<section class="content-header"><h1>Add Doctor Schedule</h1></section>
<section class="content"><form method="post" action="{{ route('myhealth.hospital.schedules.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Doctor *</label><select name="doctor_id" class="form-control" required><option value="">Select</option>@foreach($doctors as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Department</label><select name="department_id" class="form-control"><option value="">Select</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Room</label><select name="room_id" class="form-control"><option value="">Select</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Day *</label><select name="day_of_week" class="form-control" required>@foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Start Time *</label><input type="time" name="start_time" class="form-control" required></div>
<div class="col-md-4 form-group"><label>End Time *</label><input type="time" name="end_time" class="form-control" required></div>
<div class="col-md-4 form-group"><label>Consultation Duration Minutes</label><input type="number" name="consultation_duration_minutes" value="15" class="form-control" min="1"></div>
<div class="col-md-4 form-group"><label>Maximum Patients</label><input type="number" name="maximum_patients" class="form-control" min="1"></div>
<div class="col-md-2 form-group"><label>Break Start</label><input type="time" name="break_start_time" class="form-control"></div>
<div class="col-md-2 form-group"><label>Break End</label><input type="time" name="break_end_time" class="form-control"></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Schedule</button><a href="{{ route('myhealth.hospital.schedules.index') }}" class="btn btn-default">Cancel</a></div></div></form></section>
@endsection
