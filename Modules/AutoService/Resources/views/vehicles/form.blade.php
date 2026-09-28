@extends('autoservice::layouts.master')
@section('title', $vehicle->id ? 'Edit Vehicle' : 'Add Vehicle')
@section('autoservice_content')
<form method="post" action="{{ $vehicle->id ? route('autoservice.vehicles.update',$vehicle->id) : route('autoservice.vehicles.store') }}">@csrf @if($vehicle->id) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="form-group col-md-4"><label>Customer</label><select name="contact_id" class="form-control"><option value="">Please Select</option>@foreach($customers as $c)<option value="{{ $c->id }}" {{ $vehicle->contact_id==$c->id?'selected':'' }}>{{ $c->name }} {{ $c->mobile ? ' - '.$c->mobile : '' }}</option>@endforeach</select></div>
<div class="form-group col-md-4"><label>Registration No</label><input required name="registration_no" class="form-control" value="{{ old('registration_no',$vehicle->registration_no) }}"></div>
<div class="form-group col-md-4"><label>Make</label><input name="make" class="form-control" value="{{ old('make',$vehicle->make) }}"></div>
<div class="form-group col-md-4"><label>Model</label><input name="model" class="form-control" value="{{ old('model',$vehicle->model) }}"></div>
<div class="form-group col-md-4"><label>Year</label><input name="year" class="form-control" value="{{ old('year',$vehicle->year) }}"></div>
<div class="form-group col-md-4"><label>VIN</label><input name="vin" class="form-control" value="{{ old('vin',$vehicle->vin) }}"></div>
<div class="form-group col-md-4"><label>Engine No</label><input name="engine_no" class="form-control" value="{{ old('engine_no',$vehicle->engine_no) }}"></div>
<div class="form-group col-md-4"><label>Chassis No</label><input name="chassis_no" class="form-control" value="{{ old('chassis_no',$vehicle->chassis_no) }}"></div>
<div class="form-group col-md-4"><label>Current Odometer</label><input name="current_odometer" type="number" class="form-control" value="{{ old('current_odometer',$vehicle->current_odometer) }}"></div>
<div class="form-group col-md-4"><label>Last Service Date</label><input name="last_service_date" type="date" class="form-control" value="{{ old('last_service_date',$vehicle->last_service_date) }}"></div>
<div class="form-group col-md-4"><label>Next Service Date</label><input name="next_service_date" type="date" class="form-control" value="{{ old('next_service_date',$vehicle->next_service_date) }}"></div>
<div class="form-group col-md-4"><label>Next Service Odometer</label><input name="next_service_odometer" type="number" class="form-control" value="{{ old('next_service_odometer',$vehicle->next_service_odometer) }}"></div>
<div class="form-group col-md-12"><label>Notes</label><textarea name="notes" class="form-control">{{ old('notes',$vehicle->notes) }}</textarea></div>
</div><div class="box-footer"><button class="btn btn-primary">Save</button></div></div></form>
@endsection
