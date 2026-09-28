@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page bs-scheduler-page">
    <div class="bs-page-header">
        <h3>Advanced Appointment Scheduler</h3>
        <div class="bs-header-actions">
            <a href="{{ route('beautysaloons.waitlist.index') }}" class="btn btn-warning btn-sm">Waitlist</a>
            <a href="{{ route('beautysaloons.recurring-appointments.index') }}" class="btn btn-info btn-sm">Recurring</a>
            <a href="{{ route('beautysaloons.advanced-scheduler.board') }}" class="btn btn-primary btn-sm">Resource Board</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="box box-solid">
                <div class="box-header with-border"><h4 class="box-title">Filters</h4></div>
                <div class="box-body">
                    <label>Branch / Location</label>
                    <input type="text" id="bs_scheduler_location" class="form-control" placeholder="Location ID">
                    <label class="mt-10">Staff</label>
                    <input type="text" id="bs_scheduler_staff" class="form-control" placeholder="Staff ID">
                    <button type="button" id="bs_scheduler_refresh" class="btn btn-primary btn-block mt-15">Refresh Calendar</button>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="box box-solid">
                <div class="box-body">
                    <div id="bs_advanced_calendar" data-events-url="{{ route('beautysaloons.advanced-scheduler.events') }}"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('modules/beautysaloons/css/bs012_scheduler.css') }}">
<script src="{{ asset('modules/beautysaloons/js/bs012_scheduler.js') }}"></script>
@endsection
