@extends('layouts.app')
@section('title', 'MyHealth Telemedicine')
@section('content')
<section class="content-header"><h1>MyHealth Telemedicine</h1></section>
<section class="content">
    @includeIf('myhealthmembers::partials.sidebar')
    <div class="row">
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-video-camera"></i></span><div class="info-box-content"><span class="info-box-text">Today Appointments</span><span class="info-box-number">{{ $todayAppointments }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">Waiting</span><span class="info-box-number">{{ $waitingAppointments }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-calendar-check-o"></i></span><div class="info-box-content"><span class="info-box-text">Booked</span><span class="info-box-number">{{ $bookedAppointments }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-blue"><i class="fa fa-user-md"></i></span><div class="info-box-content"><span class="info-box-text">Available Schedules</span><span class="info-box-number">{{ $availableSchedules }}</span></div></div></div>
    </div>
    <div class="box"><div class="box-body">
        <a href="{{ route('myhealth.telemedicine.schedules.index') }}" class="btn btn-primary">Doctor Schedules</a>
        <a href="{{ route('myhealth.telemedicine.appointments.index') }}" class="btn btn-success">Appointments</a>
    </div></div>
</section>
@endsection
