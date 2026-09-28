@extends('layouts.app')
@section('title','Leasing LeaseAsset')
@section('content')
<section class="content-header no-print"><h1>Leasing LeaseAsset</h1></section><section class="content no-print">@include('leasing::layouts.nav')
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>LeaseAsset No</label><input name="lease_asset_no" class="form-control" value="{{ old('lease_asset_no',$lease_asset->lease_asset_no) }}"></div>
<div class="col-md-3 form-group"><label>Name *</label><input name="name" class="form-control" value="{{ old('name',$lease_asset->name) }}" required></div>
<div class="col-md-3 form-group"><label>Asset Type</label><select name="lease_asset_type_id" class="form-control"><option value="">Select</option>@foreach($types as $id=>$name)<option value="{{ $id }}" {{ old('lease_asset_type_id',$lease_asset->lease_asset_type_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Location</label><select name="location_id" class="form-control"><option value="">Select</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" {{ old('location_id',$lease_asset->location_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Gross Weight</label><input type="number" step="0.0001" name="gross_weight" class="form-control" value="{{ old('gross_weight',$lease_asset->gross_weight) }}"></div>
<div class="col-md-3 form-group"><label>Net Weight</label><input type="number" step="0.0001" name="net_weight" class="form-control" value="{{ old('net_weight',$lease_asset->net_weight) }}"></div>
<div class="col-md-3 form-group"><label>Purity</label><input type="number" step="0.0001" name="purity" class="form-control" value="{{ old('purity',$lease_asset->purity) }}"></div>
<div class="col-md-3 form-group"><label>Estimated Value</label><input type="number" step="0.01" name="estimated_value" class="form-control" value="{{ old('estimated_value',$lease_asset->estimated_value) }}"></div>
<div class="col-md-3 form-group"><label>Asset Location</label><select name="asset_location_id" class="form-control"><option value="">Select</option>@foreach($assets as $id=>$name)<option value="{{ $id }}" {{ old('asset_location_id',$lease_asset->asset_location_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>QR Code</label><input name="qr_code" class="form-control" value="{{ old('qr_code',$lease_asset->qr_code) }}"></div>
<div class="col-md-3 form-group"><label>Barcode</label><input name="barcode" class="form-control" value="{{ old('barcode',$lease_asset->barcode) }}"></div>
<div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="available" {{ old('status',$lease_asset->status)=='available'?'selected':'' }}>Available</option><option value="lease_contractd" {{ old('status',$lease_asset->status)=='lease_contractd'?'selected':'' }}>LeaseContractd</option><option value="released" {{ old('status',$lease_asset->status)=='released'?'selected':'' }}>Released</option></select></div>
<div class="col-md-12 form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description',$lease_asset->description) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('leasing.lease_assets.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>@endsection
