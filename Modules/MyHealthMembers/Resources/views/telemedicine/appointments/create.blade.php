@extends('layouts.app')
@section('title', 'Book Telemedicine Appointment')
@section('content')
<section class="content-header"><h1>Book Telemedicine Appointment</h1></section>
<section class="content"><div class="box"><div class="box-body">
<form method="POST" action="{{ route('myhealth.telemedicine.appointments.store') }}">@csrf
<div class="row">
<div class="col-md-4 form-group"><label>Member</label><select name="member_id" class="form-control" required><option value="">Select</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->myhealth_code }} - {{ $member->name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Doctor</label><select name="doctor_id" class="form-control" required><option value="">Select</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Schedule</label><select name="schedule_id" class="form-control"><option value="">Manual / No Schedule</option>@foreach($schedules as $schedule)<option value="{{ $schedule->id }}">{{ $schedule->schedule_date }} {{ $schedule->start_time }} - {{ optional($schedule->doctor)->doctor_name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Date</label><input type="date" name="appointment_date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Time</label><input type="time" name="appointment_time" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Mode</label><select name="consultation_mode" class="form-control"><option value="video">Video</option><option value="audio">Audio</option><option value="chat">Chat</option></select></div>
<div class="col-md-3 form-group"><label>Fee</label><input type="number" step="0.0001" name="consultation_fee" class="form-control" value="0.0000"></div>
<div class="col-md-12 form-group"><label>Reason</label><textarea name="reason" class="form-control"></textarea></div>
</div><button class="btn btn-primary">Book</button><a href="{{ route('myhealth.telemedicine.appointments.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
