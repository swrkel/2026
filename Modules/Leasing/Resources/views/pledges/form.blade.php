@extends('layouts.app')
@section('title','Leasing LeaseContract')
@section('content')
<section class="content-header no-print"><h1>Leasing LeaseContract</h1></section><section class="content no-print">@include('leasing::layouts.nav')
<form method="POST" action="{{ $action }}">@csrf
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">LeaseContract Details</h3></div><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>LeaseContract No *</label><input name="lease_contract_no" class="form-control" value="{{ old('lease_contract_no',$lease_contract->lease_contract_no) }}" required></div>
<div class="col-md-3 form-group"><label>Product</label><select name="leasing_product_id" class="form-control"><option value="">Select</option>@foreach($products as $id=>$name)<option value="{{ $id }}" {{ old('leasing_product_id',$lease_contract->leasing_product_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Location</label><select name="location_id" class="form-control"><option value="">Select</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" {{ old('location_id',$lease_contract->location_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Banking Customer ID</label><input type="number" name="banking_customer_id" class="form-control" value="{{ old('banking_customer_id',$lease_contract->banking_customer_id) }}"></div>
<div class="col-md-4 form-group"><label>Customer Name</label><input name="customer_name" class="form-control" value="{{ old('customer_name',$lease_contract->customer_name) }}"></div>
<div class="col-md-2 form-group"><label>Mobile</label><input name="customer_mobile" class="form-control" value="{{ old('customer_mobile',$lease_contract->customer_mobile) }}"></div>
<div class="col-md-3 form-group"><label>LeaseContractd On</label><input type="date" name="lease_contractd_on" class="form-control" value="{{ old('lease_contractd_on', $lease_contract->lease_contractd_on ? substr($lease_contract->lease_contractd_on,0,10) : '') }}"></div>
<div class="col-md-3 form-group"><label>Due On</label><input type="date" name="due_on" class="form-control" value="{{ old('due_on', $lease_contract->due_on ? substr($lease_contract->due_on,0,10) : '') }}"></div>
<div class="col-md-3 form-group"><label>Assessed Value</label><input type="number" step="0.01" name="assessed_value" class="form-control" value="{{ old('assessed_value',$lease_contract->assessed_value) }}"></div>
<div class="col-md-3 form-group"><label>Advance Amount</label><input type="number" step="0.01" name="advance_amount" class="form-control" value="{{ old('advance_amount',$lease_contract->advance_amount) }}"></div>
<div class="col-md-3 form-group"><label>Interest %</label><input type="number" step="0.0001" name="interest_rate" class="form-control" value="{{ old('interest_rate',$lease_contract->interest_rate) }}"></div>
<div class="col-md-3 form-group"><label>Outstanding</label><input type="number" step="0.01" name="outstanding_amount" class="form-control" value="{{ old('outstanding_amount',$lease_contract->outstanding_amount) }}"></div>
<div class="col-md-3 form-group"><label>Workflow</label><select name="workflow_status" class="form-control">@foreach(config('leasing.workflow_statuses',[]) as $status)<option value="{{ $status }}" {{ old('workflow_status',$lease_contract->workflow_status)==$status?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
<div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control">@foreach(['active','redeemed','renewed','insuranceed','closed','cancelled'] as $status)<option value="{{ $status }}" {{ old('status',$lease_contract->status ?: 'active')==$status?'selected':'' }}>{{ ucfirst($status) }}</option>@endforeach</select></div>
<div class="col-md-12 form-group"><label>LeaseAssets</label><select name="lease_asset_ids[]" class="form-control" multiple size="6">@foreach($lease_assets as $lease_asset)<option value="{{ $lease_asset->id }}" {{ in_array($lease_asset->id,$selectedLeaseAssets)?'selected':'' }}>{{ $lease_asset->lease_asset_no }} - {{ $lease_asset->name }} ({{ number_format($lease_asset->estimated_value,2) }})</option>@endforeach</select></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes',$lease_contract->notes) }}</textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button><a href="{{ route('leasing.lease_contracts.index') }}" class="btn btn-default">Cancel</a></div></div>
</form></section>@endsection
