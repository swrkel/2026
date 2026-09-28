@extends('layouts.app')
@section('title', 'Add Laboratory Test')
@section('content')
<section class="content-header"><h1>Add Laboratory Test</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.laboratory.catalogue.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3"><div class="form-group"><label>Test Code</label><input name="test_code" class="form-control" required></div></div>
<div class="col-md-5"><div class="form-group"><label>Test Name</label><input name="test_name" class="form-control" required></div></div>
<div class="col-md-4"><div class="form-group"><label>Department</label><input name="department" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Category</label><input name="category" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Sample Type</label><input name="sample_type" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Turnaround Time</label><input name="turnaround_time" class="form-control"></div></div>
<div class="col-md-3"><div class="form-group"><label>Price</label><input name="price" type="number" step="0.01" class="form-control"></div></div>
<div class="col-md-6"><div class="form-group"><label>Normal Range</label><input name="normal_range" class="form-control"></div></div>
<div class="col-md-6"><div class="form-group"><label>Instructions</label><input name="instructions" class="form-control"></div></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('myhealth.laboratory.catalogue.index') }}" class="btn btn-default">Back</a></div></div>
</form></section>
@endsection
