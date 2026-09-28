@extends('layouts.app')
@section('title', 'Add Medicine')
@section('content')
<section class="content-header"><h1>Add Medicine</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('myhealth.pharmacy.medicines.store') }}">@csrf
<div class="row">
<div class="col-md-4 form-group"><label>Medicine Name *</label><input name="medicine_name" class="form-control" required></div>
<div class="col-md-4 form-group"><label>Generic Name</label><input name="generic_name" class="form-control"></div>
<div class="col-md-4 form-group"><label>Brand</label><input name="brand" class="form-control"></div>
<div class="col-md-4 form-group"><label>Category</label><input name="category" class="form-control"></div>
<div class="col-md-4 form-group"><label>Dosage Form</label><input name="dosage_form" class="form-control" placeholder="Tablet / Syrup / Injection"></div>
<div class="col-md-4 form-group"><label>Strength</label><input name="strength" class="form-control" placeholder="500mg"></div>
<div class="col-md-4 form-group"><label>Manufacturer</label><input name="manufacturer" class="form-control"></div>
<div class="col-md-4 form-group"><label>Reorder Level</label><input name="reorder_level" type="number" step="0.0001" class="form-control" value="0"></div>
<div class="col-md-4 form-group"><label>Status</label><select name="is_active" class="form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
</div>
<button class="btn btn-primary">Save</button> <a href="{{ route('myhealth.pharmacy.medicines.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
