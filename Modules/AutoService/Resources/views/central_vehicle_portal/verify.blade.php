@extends('autoservice::layouts.master')
@section('title','Verify Vehicle Owner')
@section('content')
<div class="container-fluid">
  <h3>Verify Vehicle Owner</h3>
  <p>Vehicle: <strong>{{ $vehicle->registration_no ?? $vehicle->vin ?? $vehicle->chassis_no }}</strong></p>
  <p>SMS sent to current owner mobile: <strong>{{ optional($vehicle->currentOwner)->mobile }}</strong></p>
  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ implode(' | ', $errors->all()) }}</div>@endif
  <form method="POST" action="{{ route('autoservice.central_vehicle.verify', $vehicle->id) }}">
    @csrf
    <div class="row">
      <div class="col-md-3"><input name="otp" class="form-control" placeholder="Enter SMS Code" required></div>
      <div class="col-md-2"><button class="btn btn-success">Verify & View Details</button></div>
    </div>
  </form>
</div>
@endsection
