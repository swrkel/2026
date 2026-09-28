@extends('layouts.app')
@section('title','Pawning Article')
@section('content')
<section class="content-header no-print"><h1>Pawning Article</h1></section><section class="content no-print">@include('pawning::layouts.nav')
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Article No</label><input name="article_no" class="form-control" value="{{ old('article_no',$article->article_no) }}"></div>
<div class="col-md-3 form-group"><label>Name *</label><input name="name" class="form-control" value="{{ old('name',$article->name) }}" required></div>
<div class="col-md-3 form-group"><label>Collateral Type</label><select name="collateral_type_id" class="form-control"><option value="">Select</option>@foreach($types as $id=>$name)<option value="{{ $id }}" {{ old('collateral_type_id',$article->collateral_type_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Location</label><select name="location_id" class="form-control"><option value="">Select</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" {{ old('location_id',$article->location_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Gross Weight</label><input type="number" step="0.0001" name="gross_weight" class="form-control" value="{{ old('gross_weight',$article->gross_weight) }}"></div>
<div class="col-md-3 form-group"><label>Net Weight</label><input type="number" step="0.0001" name="net_weight" class="form-control" value="{{ old('net_weight',$article->net_weight) }}"></div>
<div class="col-md-3 form-group"><label>Purity</label><input type="number" step="0.0001" name="purity" class="form-control" value="{{ old('purity',$article->purity) }}"></div>
<div class="col-md-3 form-group"><label>Estimated Value</label><input type="number" step="0.01" name="estimated_value" class="form-control" value="{{ old('estimated_value',$article->estimated_value) }}"></div>
<div class="col-md-3 form-group"><label>Vault Location</label><select name="vault_location_id" class="form-control"><option value="">Select</option>@foreach($vaults as $id=>$name)<option value="{{ $id }}" {{ old('vault_location_id',$article->vault_location_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>QR Code</label><input name="qr_code" class="form-control" value="{{ old('qr_code',$article->qr_code) }}"></div>
<div class="col-md-3 form-group"><label>Barcode</label><input name="barcode" class="form-control" value="{{ old('barcode',$article->barcode) }}"></div>
<div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="available" {{ old('status',$article->status)=='available'?'selected':'' }}>Available</option><option value="pledged" {{ old('status',$article->status)=='pledged'?'selected':'' }}>Pledged</option><option value="released" {{ old('status',$article->status)=='released'?'selected':'' }}>Released</option></select></div>
<div class="col-md-12 form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description',$article->description) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('pawning.articles.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>@endsection
