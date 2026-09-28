@extends('layouts.app')
@section('title', 'Doctor Schedules')
@section('content')
<section class="content-header"><h1>Doctor Schedules <a href="{{ route('myhealth.telemedicine.schedules.create') }}" class="btn btn-primary pull-right">Add Schedule</a></h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Doctor</th><th>Time</th><th>Slot</th><th>Mode</th><th>Fee</th><th>Status</th></tr></thead><tbody>
@forelse($schedules as $schedule)<tr><td>{{ $schedule->schedule_date }}</td><td>{{ optional($schedule->doctor)->doctor_name }}</td><td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td><td>{{ $schedule->slot_minutes }} min</td><td>{{ ucfirst($schedule->consultation_mode) }}</td><td class="text-right">{{ number_format((float)$schedule->consultation_fee, 4) }}</td><td>{{ ucfirst($schedule->status) }}</td></tr>@empty<tr><td colspan="7" class="text-center">No schedules found.</td></tr>@endforelse
</tbody></table>{{ $schedules->links() }}</div></div></section>
@endsection
