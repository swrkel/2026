@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <div class="bs-page-header"><h3>Resource Booking Board</h3></div>
    <div class="bs-resource-board">
        <div class="bs-board-column"><h4>Available</h4><p>Available staff, rooms and chairs will be shown here.</p></div>
        <div class="bs-board-column"><h4>Booked</h4><p>Booked resources by time slot will be shown here.</p></div>
        <div class="bs-board-column"><h4>Waitlist</h4><p>Waiting customers can be converted into bookings from this board.</p></div>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('modules/beautysaloons/css/bs012_scheduler.css') }}">
@endsection
