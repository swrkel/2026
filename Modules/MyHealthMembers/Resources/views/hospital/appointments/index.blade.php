@extends('layouts.app')
@section('title', 'My Health Appointments')
@section('content')
<section class="content-header"><h1>Appointments <a href="{{ route('myhealth.hospital.appointments.create') }}" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Add Appointment</a></h1></section>
<section class="content">
<div class="box box-primary"><div class="box-body table-responsive">
<form method="get" class="form-inline" style="margin-bottom:15px"><input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="form-control"> <select name="status" class="form-control"><option value="">All Status</option>@foreach(['waiting','in_consultation','completed','cancelled','no_show'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select> <button class="btn btn-default">Search</button></form>
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Date</th><th>Time</th><th>Token</th><th>Member</th><th>Doctor</th><th>Room</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($appointments as $row)
<tr><td>{{ $row->appointment_no }}</td><td>{{ $row->appointment_date }}</td><td>{{ $row->appointment_time }}</td><td>{{ $row->token_no }}</td><td>{{ optional($row->member)->name }}</td><td>{{ optional($row->doctor)->name }}</td><td>{{ optional($row->room)->room_name }}</td><td>{{ ucfirst(str_replace('_',' ',$row->status)) }}</td><td><form method="post" action="{{ route('myhealth.hospital.appointments.status', $row) }}">@csrf<select name="status" class="form-control input-sm" onchange="this.form.submit()">@foreach(['waiting','in_consultation','completed','cancelled','no_show'] as $s)<option value="{{ $s }}" @selected($row->status==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></form></td></tr>
@empty<tr><td colspan="9" class="text-center">No appointments found.</td></tr>@endforelse
</tbody></table>{{ $appointments->links() }}</div></div>
</section>
@endsection
