@extends('layouts.app')
@section('title', 'Add My Health Appointment')
@section('content')
<section class="content-header"><h1>Add Appointment</h1></section>
<section class="content"><form method="post" action="{{ route('myhealth.hospital.appointments.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Member *</label><select name="member_id" class="form-control" required><option value="">Select</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->member_code }} - {{ $m->name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Doctor</label><select name="doctor_id" class="form-control"><option value="">Select</option>@foreach($doctors as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Department</label><select name="department_id" class="form-control"><option value="">Select</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Room</label><select name="room_id" class="form-control"><option value="">Select</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Date *</label><input type="date" name="appointment_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
<div class="col-md-4 form-group"><label>Time</label><input type="time" name="appointment_time" class="form-control"></div>
<div class="col-md-4 form-group"><label>Visit Type</label><select name="visit_type" class="form-control"><option value="opd">OPD</option><option value="emergency">Emergency</option><option value="follow_up">Follow-up</option><option value="telemedicine">Telemedicine</option></select></div>
<div class="col-md-8 form-group"><label>Reason</label><input type="text" name="reason" class="form-control"></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Appointment</button><a href="{{ route('myhealth.hospital.appointments.index') }}" class="btn btn-default">Cancel</a></div></div></form></section>
@endsection
