@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_customer_measurement.css') }}">
<div class="tailoring-clean-page"><div class="tailoring-page-header"><div><h1>Add Tailoring Customer</h1><p>Create customer profile with tailoring preferences.</p></div><a href="{{ route('tailoring.customer_centre.index') }}" class="btn btn-default">Back</a></div>
<div class="tailoring-card"><form method="POST">@csrf<div class="row"><div class="col-md-3"><label>Name</label><input class="form-control" name="name"></div><div class="col-md-3"><label>Mobile</label><input class="form-control" name="mobile"></div><div class="col-md-3"><label>Email</label><input class="form-control" name="email"></div><div class="col-md-3"><label>Branch</label><select class="form-control tailoring-select2" name="location_id"></select></div></div><br><button class="btn btn-primary">Save Customer</button></form></div></div>
@endsection
