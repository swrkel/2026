@extends('layouts.app')
@section('title', 'Add Insurance Company')
@section('content')
<section class="content-header"><h1>Add Insurance Company</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('myhealth.insurance.companies.store') }}">@csrf
<div class="row">
<div class="col-md-4 form-group"><label>Company Name *</label><input name="company_name" class="form-control" required></div>
<div class="col-md-4 form-group"><label>Contact No</label><input name="contact_no" class="form-control"></div>
<div class="col-md-4 form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
<div class="col-md-8 form-group"><label>Address</label><textarea name="address" class="form-control"></textarea></div>
<div class="col-md-4 form-group"><label>Status</label><select name="is_active" class="form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
</div>
<button class="btn btn-primary">Save</button> <a href="{{ route('myhealth.insurance.companies.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
