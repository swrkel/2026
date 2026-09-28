@extends('autoservice::layouts.master')
@section('title','Central Vehicle Registration')
@section('content')
<div class="container-fluid">
  <h3>Central Vehicle Registration</h3>
  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ implode(' | ', $errors->all()) }}</div>@endif
  <form method="POST" action="{{ route('autoservice.central_vehicle.store') }}">
    @csrf
    <div class="card"><div class="card-body">
      <h4>Vehicle Details</h4>
      <div class="row">
        <div class="col-md-3"><label>Vehicle No</label><input name="registration_no" class="form-control" value="{{ old('registration_no') }}"></div>
        <div class="col-md-3"><label>VIN</label><input name="vin" class="form-control" value="{{ old('vin') }}"></div>
        <div class="col-md-3"><label>Chassis No</label><input name="chassis_no" class="form-control" value="{{ old('chassis_no') }}"></div>
        <div class="col-md-3"><label>Engine No</label><input name="engine_no" class="form-control" value="{{ old('engine_no') }}"></div>
        <div class="col-md-3"><label>Make</label><input name="make" class="form-control" value="{{ old('make') }}"></div>
        <div class="col-md-3"><label>Model</label><input name="model" class="form-control" value="{{ old('model') }}"></div>
        <div class="col-md-2"><label>Year</label><input name="year" class="form-control" value="{{ old('year') }}"></div>
        <div class="col-md-2"><label>Fuel Type</label><input name="fuel_type" class="form-control" value="{{ old('fuel_type') }}"></div>
        <div class="col-md-2"><label>Transmission</label><input name="transmission" class="form-control" value="{{ old('transmission') }}"></div>
      </div>
      <hr>
      <h4>Current Owner Details</h4>
      <div class="row">
        <div class="col-md-4"><label>Owner Name</label><input name="owner_name" class="form-control" required value="{{ old('owner_name') }}"></div>
        <div class="col-md-3"><label>Mobile No</label><input name="mobile" class="form-control" required value="{{ old('mobile') }}"></div>
        <div class="col-md-3"><label>Email</label><input name="email" class="form-control" value="{{ old('email') }}"></div>
        <div class="col-md-2"><label>NIC No</label><input name="nic_no" class="form-control" value="{{ old('nic_no') }}"></div>
        <div class="col-md-12"><label>Address</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
      </div>
      <br><button class="btn btn-primary">Register Vehicle & Send SMS OTP</button>
      <a href="{{ route('autoservice.central_vehicle.login') }}" class="btn btn-default">Already Registered Login</a>
    </div></div>
  </form>
</div>
@endsection
