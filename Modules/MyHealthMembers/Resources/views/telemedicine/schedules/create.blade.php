@extends('layouts.app')
@section('title', 'Add Doctor Schedule')
@section('content')
<section class="content-header"><h1>Add Doctor Schedule</h1></section>
<section class="content"><div class="box"><div class="box-body">
<form method="POST" action="{{ route('myhealth.telemedicine.schedules.store') }}">@csrf
<div class="row">
<div class="col-md-4 form-group"><label>Doctor</label><select name="doctor_id" class="form-control" required><option value="">Select</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Date</label><input type="date" name="schedule_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
<div class="col-md-2 form-group"><label>Start</label><input type="time" name="start_time" class="form-control" required></div>
<div class="col-md-2 form-group"><label>End</label><input type="time" name="end_time" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Slot Minutes</label><input type="number" name="slot_minutes" class="form-control" value="15"></div>
<div class="col-md-3 form-group"><label>Mode</label><select name="consultation_mode" class="form-control"><option value="video">Video</option><option value="audio">Audio</option><option value="chat">Chat</option></select></div>
<div class="col-md-3 form-group"><label>Fee</label><input type="number" step="0.0001" name="consultation_fee" class="form-control" value="0.0000"></div>
<div class="col-md-12 form-group"><label>Remarks</label><textarea name="remarks" class="form-control"></textarea></div>
</div><button class="btn btn-primary">Save</button><a href="{{ route('myhealth.telemedicine.schedules.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
