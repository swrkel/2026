@extends('layouts.app')
@section('title', 'Telemedicine Appointment')
@section('content')
<section class="content-header"><h1>Appointment {{ $appointment->appointment_no }}</h1></section>
<section class="content"><div class="box"><div class="box-body">
<div class="row"><div class="col-md-6"><b>Member:</b> {{ optional($appointment->member)->name }}</div><div class="col-md-6"><b>Doctor:</b> {{ optional($appointment->doctor)->doctor_name }}</div></div><hr>
<div class="row"><div class="col-md-3"><b>Date:</b> {{ $appointment->appointment_date }}</div><div class="col-md-3"><b>Time:</b> {{ $appointment->appointment_time }}</div><div class="col-md-3"><b>Status:</b> {{ ucfirst($appointment->status) }}</div><div class="col-md-3"><b>Consent:</b> {{ ucfirst($appointment->consent_status) }}</div></div><hr>
<p><b>Reason:</b> {{ $appointment->reason }}</p>
@if($appointment->session)<p><b>Meeting URL:</b> <a href="{{ $appointment->session->meeting_url }}">{{ $appointment->session->meeting_url }}</a></p>@endif
<form method="POST" action="{{ route('myhealth.telemedicine.appointments.open-session', $appointment->id) }}">@csrf<button class="btn btn-success">Open Waiting Room</button> <a href="{{ route('myhealth.telemedicine.appointments.index') }}" class="btn btn-default">Back</a></form>
</div></div></section>
@endsection
