@extends('layouts.app')
@section('title','Leasing Product')
@section('content')
<section class="content-header no-print"><h1>Leasing Product</h1></section><section class="content no-print">@include('leasing::layouts.nav')
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Name *</label><input name="name" class="form-control" value="{{ old('name',$product->name) }}" required></div>
<div class="col-md-2 form-group"><label>Code</label><input name="code" class="form-control" value="{{ old('code',$product->code) }}"></div>
<div class="col-md-3 form-group"><label>Asset Type</label><select name="lease_asset_type_id" class="form-control"><option value="">Select</option>@foreach($types as $id=>$name)<option value="{{ $id }}" {{ old('lease_asset_type_id',$product->lease_asset_type_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" {{ old('status',$product->status)=='active'?'selected':'' }}>Active</option><option value="inactive" {{ old('status',$product->status)=='inactive'?'selected':'' }}>Inactive</option></select></div>
<div class="col-md-2 form-group"><label>Interest %</label><input type="number" step="0.0001" name="interest_rate" class="form-control" value="{{ old('interest_rate',$product->interest_rate) }}"></div>
<div class="col-md-2 form-group"><label>Penalty %</label><input type="number" step="0.0001" name="penalty_rate" class="form-control" value="{{ old('penalty_rate',$product->penalty_rate) }}"></div>
<div class="col-md-2 form-group"><label>Term Days</label><input type="number" name="term_days" class="form-control" value="{{ old('term_days',$product->term_days ?: 30) }}"></div>
<div class="col-md-2 form-group"><label>Advance %</label><input type="number" step="0.0001" name="advance_percentage" class="form-control" value="{{ old('advance_percentage',$product->advance_percentage) }}"></div>
<div class="col-md-2 form-group"><label>Min Amount</label><input type="number" step="0.01" name="minimum_amount" class="form-control" value="{{ old('minimum_amount',$product->minimum_amount) }}"></div>
<div class="col-md-2 form-group"><label>Max Amount</label><input type="number" step="0.01" name="maximum_amount" class="form-control" value="{{ old('maximum_amount',$product->maximum_amount) }}"></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes',$product->notes) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('leasing.products.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>@endsection
