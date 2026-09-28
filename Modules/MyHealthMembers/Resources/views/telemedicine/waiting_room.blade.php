@extends('layouts.app')
@section('title', 'Telemedicine Waiting Room')
@section('content')
<section class="content-header"><h1>Waiting Room - {{ $appointment->appointment_no }}</h1></section>
<section class="content"><div class="box"><div class="box-body text-center">
<h3>{{ optional($appointment->doctor)->doctor_name }}</h3><p>{{ optional($appointment->member)->name }} is waiting for consultation.</p><p>Status: {{ ucfirst(optional($appointment->session)->status ?? $appointment->status) }}</p><p>Session Token: {{ optional($appointment->session)->session_token }}</p><a href="{{ route('myhealth.telemedicine.appointments.show', $appointment->id) }}" class="btn btn-default">Back</a>
</div></div></section>
@endsection
