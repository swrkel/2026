@extends('autoservice::layouts.master')
@section('title','Vehicle Owner Login')
@section('content')
<div class="container-fluid">
  <h3>Vehicle Owner Login</h3>
  <p>Enter Vehicle No, VIN, Chassis No, Engine No or Mobile No. The SMS verification code will be sent to the last registered owner.</p>
  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ implode(' | ', $errors->all()) }}</div>@endif
  <form method="POST" action="{{ route('autoservice.central_vehicle.request_otp') }}">
    @csrf
    <div class="row">
      <div class="col-md-6"><input name="keyword" class="form-control" placeholder="Vehicle No / VIN / Chassis No / Mobile No" required></div>
      <div class="col-md-3"><button class="btn btn-primary">Send Verification SMS</button></div>
    </div>
  </form>
  <hr><a href="{{ route('autoservice.central_vehicle.register') }}">Register your vehicle</a>
</div>
@endsection
