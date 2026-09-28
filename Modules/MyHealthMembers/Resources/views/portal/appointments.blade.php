@extends('myhealthmembers::portal.layout')
@section('title', 'Appointments')
@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="mh-card"><div class="mh-card-header">Request Appointment</div><div class="mh-card-body">
            <form method="POST" action="{{ route('myhealth.member.portal.appointments.request') }}">
                @csrf
                <div class="form-group"><label>Date *</label><input type="date" name="appointment_date" class="form-control" required value="{{ old('appointment_date') }}"></div>
                <div class="form-group"><label>Preferred Time</label><input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time') }}"></div>
                <div class="form-group"><label>Reason</label><textarea name="reason" class="form-control" rows="4" placeholder="Briefly describe your appointment request">{{ old('reason') }}</textarea></div>
                <button class="btn btn-mh" type="submit"><i class="fa fa-calendar-plus-o"></i> Submit Request</button>
            </form>
        </div></div>
    </div>
    <div class="col-md-7">
        <div class="mh-card"><div class="mh-card-header">Appointments</div><div class="mh-card-body">
        @include('myhealthmembers::portal.partials.simple_list', ['rows' => $appointments ?? collect(), 'empty' => 'No appointments found.'])
        @if(isset($appointments) && method_exists($appointments, 'links')) <div class="text-center">{!! $appointments->links() !!}</div> @endif
        </div></div>
    </div>
</div>
@endsection
