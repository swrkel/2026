@extends('layouts.app')
@section('title', 'Add Vaccine')
@section('content')
<section class="content-header"><h1>Add Vaccine</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.vaccination.vaccines.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Vaccine Code *</label><input name="vaccine_code" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Vaccine Name *</label><input name="vaccine_name" class="form-control" required></div>
<div class="col-md-3 form-group"><label>Type</label><select name="vaccine_type" class="form-control"><option value="">Select</option>@foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Manufacturer</label><input name="manufacturer" class="form-control"></div>
<div class="col-md-3 form-group"><label>Dose Schedule</label><input name="dose_schedule" class="form-control"></div>
<div class="col-md-3 form-group"><label>Storage Temperature</label><input name="storage_temperature" class="form-control"></div>
<div class="col-md-3 form-group"><label>Default Interval Days</label><input type="number" min="0" name="default_interval_days" class="form-control"></div>
<div class="col-md-3 form-group"><label>Booster Interval Days</label><input type="number" min="0" name="booster_interval_days" class="form-control"></div>
<div class="col-md-12 form-group"><label><input type="checkbox" name="booster_required" value="1"> Booster Required</label></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button> <a href="{{ route('myhealth.vaccination.vaccines.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>
@endsection
