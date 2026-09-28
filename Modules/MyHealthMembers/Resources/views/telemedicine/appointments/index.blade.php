@extends('layouts.app')
@section('title', 'Telemedicine Appointments')
@section('content')
<section class="content-header"><h1>Telemedicine Appointments <a href="{{ route('myhealth.telemedicine.appointments.create') }}" class="btn btn-primary pull-right">Book Appointment</a></h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<form method="GET" class="form-inline" style="margin-bottom:10px"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search appointment/member"> <button class="btn btn-default">Search</button></form>
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Date</th><th>Time</th><th>Member</th><th>Doctor</th><th>Status</th><th>Consent</th><th>Action</th></tr></thead><tbody>
@forelse($appointments as $appointment)<tr><td>{{ $appointment->appointment_no }}</td><td>{{ $appointment->appointment_date }}</td><td>{{ $appointment->appointment_time }}</td><td>{{ optional($appointment->member)->name }}</td><td>{{ optional($appointment->doctor)->doctor_name }}</td><td>{{ ucfirst($appointment->status) }}</td><td>{{ ucfirst($appointment->consent_status) }}</td><td><a href="{{ route('myhealth.telemedicine.appointments.show', $appointment->id) }}" class="btn btn-xs btn-info">View</a></td></tr>@empty<tr><td colspan="8" class="text-center">No appointments found.</td></tr>@endforelse
</tbody></table>{{ $appointments->links() }}</div></div></section>
@endsection
