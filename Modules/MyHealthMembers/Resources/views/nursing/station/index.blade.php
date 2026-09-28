@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'Nursing Station')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Patient Queue</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Token</th><th>Member</th><th>Doctor</th><th>Status</th><th>Appointment Time</th></tr></thead><tbody>
@forelse($patients as $patient)<tr><td>{{ $patient->token_no ?? $patient->queue_no ?? '-' }}</td><td>{{ optional($patient->member)->name ?? $patient->member_name ?? '-' }}</td><td>{{ $patient->doctor_id ?? '-' }}</td><td>{{ ucfirst($patient->status ?? '-') }}</td><td>{{ $patient->appointment_at ?? $patient->created_at }}</td></tr>@empty
<tr><td colspan="5" class="text-center text-muted">No patients found.</td></tr>@endforelse
</tbody></table></div></div>
</section>
@endsection
