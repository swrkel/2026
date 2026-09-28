@extends('layouts.app')
@section('title', 'Doctor Session Schedules')
@section('content')
<section class="content-header"><h1>Doctor Session Schedules <a href="{{ route('myhealth.hospital.schedules.create') }}" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Add Schedule</a></h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Doctor</th><th>Department</th><th>Room</th><th>Day</th><th>Time</th><th>Duration</th><th>Max Patients</th><th>Status</th></tr></thead><tbody>
@forelse($schedules as $row)<tr><td>{{ optional($row->doctor)->name }}</td><td>{{ optional($row->department)->name }}</td><td>{{ optional($row->room)->room_name }}</td><td>{{ $row->day_of_week }}</td><td>{{ $row->start_time }} - {{ $row->end_time }}</td><td>{{ $row->consultation_duration_minutes }} min</td><td>{{ $row->maximum_patients }}</td><td>{{ $row->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="8" class="text-center">No schedules found.</td></tr>@endforelse
</tbody></table></div></div></section>
@endsection
