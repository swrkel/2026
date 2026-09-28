@extends('layouts.app')
@section('title','Asset Type')
@section('content')
<section class="content-header no-print"><h1>Asset Type</h1></section>
<section class="content no-print">@include('leasing::layouts.nav')
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Name *</label><input name="name" class="form-control" value="{{ old('name',$type->name) }}" required></div>
<div class="col-md-3 form-group"><label>Code</label><input name="code" class="form-control" value="{{ old('code',$type->code) }}"></div>
<div class="col-md-2 form-group"><label>Requires Weight</label><select name="requires_weight" class="form-control"><option value="0">No</option><option value="1" {{ old('requires_weight',$type->requires_weight)==1?'selected':'' }}>Yes</option></select></div>
<div class="col-md-2 form-group"><label>Requires Purity</label><select name="requires_purity" class="form-control"><option value="0">No</option><option value="1" {{ old('requires_purity',$type->requires_purity)==1?'selected':'' }}>Yes</option></select></div>
<div class="col-md-1 form-group"><label>Status</label><select name="status" class="form-control"><option value="1">Active</option><option value="0" {{ old('status',$type->status)==0?'selected':'' }}>Inactive</option></select></div>
<div class="col-md-12 form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description',$type->description) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('leasing.collateral-types.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>@endsection
