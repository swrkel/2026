@extends('autoservice::layouts.master')
@section('title','Vehicle Ownership Transfer')
@section('content')
<div class="container-fluid">
  <h3>Vehicle Ownership Transfer Verification</h3>
  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <div class="box box-primary">
    <div class="box-body">
      <p><strong>Vehicle:</strong> {{ optional($transfer->vehicle)->registration_no }} {{ optional($transfer->vehicle)->make }} {{ optional($transfer->vehicle)->model }}</p>
      <p><strong>New Owner:</strong> {{ $transfer->new_owner_name }} / {{ $transfer->new_owner_mobile }}</p>
      <p><strong>Status:</strong> {{ ucwords(str_replace('_',' ', $transfer->status)) }}</p>
      <form method="POST" action="{{ route('autoservice.central_vehicle.transfer.verify_new_owner', $transfer->id) }}">
        @csrf
        <div class="form-group">
          <label>Verification Code</label>
          <input type="text" name="otp" class="form-control" required maxlength="10">
          <small class="help-block">
            First enter the code sent to the current registered owner. After approval, enter the code sent to the new owner.
          </small>
        </div>
        <button type="submit" class="btn btn-primary">Verify & Continue</button>
      </form>
    </div>
  </div>
</div>
@endsection
