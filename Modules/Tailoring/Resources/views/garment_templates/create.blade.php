@extends('tailoring::layouts.app')
@section('title','Add Garment Template')
@section('content')
@include('tailoring::partials.smart_toolbar', ['title'=>'Add Garment Template'])
<form method="post" action="{{ route('tailoring.garment-templates.store') }}">@csrf<div class="row"><div class="col-md-4"><label>Name</label><input name="name" class="form-control"></div><div class="col-md-4"><label>Category</label><input name="category" class="form-control"></div><div class="col-md-4"><label>Edition</label><select name="edition" class="form-control"><option value="basic">Basic</option><option value="professional">Professional</option><option value="enterprise">Enterprise</option></select></div></div><div class="row mt-3"><div class="col-md-4"><label>Default Delivery Days</label><input type="number" name="default_delivery_days" class="form-control"></div><div class="col-md-4"><label>Labour Minutes</label><input type="number" name="estimated_labour_minutes" class="form-control"></div><div class="col-md-4"><label>Base Price</label><input name="base_price" class="form-control"></div></div><button class="btn btn-primary mt-3">Save</button></form>
@endsection
