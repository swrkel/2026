@extends('distributionnew::layouts.app')
@section('content')
<div class="disnew-page">

@include('distributionnew::partials.erp-standard-styles')
<div class="disnew-card"><div class="disnew-card-header"><h3>Create Settlement</h3></div><form method="post" action="{{ route('distribution-new.settlements.store') }}">@csrf
<div class="row"><div class="col-md-3"><label>Date</label><input type="date" name="settlement_date" class="form-control" value="{{ date('Y-m-d') }}" required></div><div class="col-md-3"><label>Sales Rep</label><input name="sales_rep_id" class="form-control"></div><div class="col-md-3"><label>Vehicle</label><input name="vehicle_id" class="form-control"></div><div class="col-md-3"><label>Location</label><input name="business_location_id" class="form-control"></div></div>
<div class="row mt-3"><div class="col-md-2"><label>Opening</label><input name="opening_stock_value" step="0.0001" type="number" class="form-control"></div><div class="col-md-2"><label>Loaded</label><input name="loaded_value" step="0.0001" type="number" class="form-control"></div><div class="col-md-2"><label>Sold</label><input name="sold_value" step="0.0001" type="number" class="form-control"></div><div class="col-md-2"><label>Returned</label><input name="returned_value" step="0.0001" type="number" class="form-control"></div><div class="col-md-2"><label>Shortage</label><input name="shortage_value" step="0.0001" type="number" class="form-control"></div><div class="col-md-2"><label>Excess</label><input name="excess_value" step="0.0001" type="number" class="form-control"></div></div>
<div class="row mt-3"><div class="col-md-12"><label>Note</label><textarea name="note" class="form-control"></textarea></div></div><button class="btn btn-primary mt-3">Save Draft</button></form></div>
</div>
@endsection
