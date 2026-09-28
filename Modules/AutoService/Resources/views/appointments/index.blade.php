@extends('autoservice::layouts.master')
@section('title','Auto Service Appointments')
@section('autoservice_content')
<div class="box"><div class="box-header"><h3 class="box-title">Appointments</h3><a href="{{ route('autoservice.appointments.create') }}" class="btn btn-success pull-right">Add Appointment</a></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Date/Time</th><th>Service</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($appointments as $row)<tr><td>{{ $row->appointment_no }}</td><td>{{ $row->appointment_at }}</td><td>{{ $row->service_type }}</td><td>{{ ucfirst($row->status) }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('autoservice.appointments.edit',$row->id) }}">Edit</a></td></tr>@endforeach
</tbody></table>{{ $appointments->links() }}</div></div>
@endsection
