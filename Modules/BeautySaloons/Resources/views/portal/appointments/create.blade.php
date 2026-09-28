@extends('beautysaloons::portal.layout')
@section('portal_title', 'Book Appointment')
@section('portal_content')
<form method="POST" action="{{ route('beautysaloons.portal.appointments.store') }}" class="bs-portal-form">
    @csrf
    <div class="row">
        <div class="col-md-4"><label>Branch</label><input name="branch_id" class="form-control"></div>
        <div class="col-md-4"><label>Service</label><input name="service_id" class="form-control" required></div>
        <div class="col-md-4"><label>Staff</label><input name="staff_id" class="form-control"></div>
        <div class="col-md-4"><label>Date</label><input type="date" name="appointment_date" class="form-control" required></div>
        <div class="col-md-4"><label>Time</label><input type="time" name="start_time" class="form-control" required></div>
        <div class="col-md-12"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
    </div>
    <button class="btn btn-primary btn-lg mt-3">Confirm Booking</button>
</form>
@endsection
