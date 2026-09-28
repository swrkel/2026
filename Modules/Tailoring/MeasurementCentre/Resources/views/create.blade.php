@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_customer_measurement.css') }}">
<div class="tailoring-clean-page"><div class="tailoring-page-header"><div><h1>Add Measurement Profile</h1><p>Create garment-specific measurements with version support.</p></div><a href="{{ route('tailoring.measurement_centre.index') }}" class="btn btn-default">Back</a></div>
<div class="tailoring-card"><form method="POST">@csrf<div class="row"><div class="col-md-3"><label>Customer</label><select class="form-control tailoring-select2" name="customer_id"></select></div><div class="col-md-3"><label>Profile Name</label><input class="form-control" name="profile_name"></div><div class="col-md-3"><label>Garment Type</label><select class="form-control tailoring-select2" name="garment_type"></select></div><div class="col-md-3"><label>Fit Preference</label><select class="form-control"><option>Regular</option><option>Slim</option><option>Loose</option></select></div></div><hr><p>Measurement fields will be loaded from the selected garment template.</p><button class="btn btn-primary">Save Measurement Profile</button></form></div></div>
@endsection
