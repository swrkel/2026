@extends('layouts.app')
@section('title', 'Add Pharmacy Stock')
@section('content')
<section class="content-header"><h1>Add Pharmacy Stock</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('myhealth.pharmacy.stock.store') }}">@csrf
<div class="row">
<div class="col-md-4 form-group"><label>Medicine *</label><select name="medicine_id" class="form-control" required><option value="">Select</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->medicine_code }} - {{ $medicine->medicine_name }}</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Batch No *</label><input name="batch_no" class="form-control" required></div>
<div class="col-md-4 form-group"><label>Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
<div class="col-md-4 form-group"><label>Quantity *</label><input type="number" step="0.0001" name="quantity" class="form-control" required></div>
<div class="col-md-4 form-group"><label>Purchase Cost</label><input type="number" step="0.0001" name="purchase_cost" class="form-control" value="0"></div>
<div class="col-md-4 form-group"><label>Selling Price</label><input type="number" step="0.0001" name="selling_price" class="form-control" value="0"></div>
<div class="col-md-4 form-group"><label>Transaction Date</label><input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="col-md-4 form-group"><label>Transaction Type</label><select name="transaction_type" class="form-control"><option value="purchase">Purchase</option><option value="opening_stock">Opening Stock</option><option value="adjustment">Adjustment</option></select></div>
<div class="col-md-4 form-group"><label>Reference No</label><input name="reference_no" class="form-control"></div>
<div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
</div>
<button class="btn btn-primary">Save Stock</button> <a href="{{ route('myhealth.pharmacy.stock.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
