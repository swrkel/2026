@extends('distributionnew::layouts.app')
@section('content')
<div class="disnew-page">

@include('distributionnew::partials.erp-standard-styles')
<div class="disnew-card"><div class="disnew-card-header"><h3>Add Collection</h3></div>
<form method="post" action="{{ route('distribution-new.collections.store') }}">@csrf
<div class="row"><div class="col-md-3"><label>Date</label><input type="date" name="collection_date" class="form-control" value="{{ date('Y-m-d') }}" required></div><div class="col-md-3"><label>Payment Method</label><select name="payment_method" class="form-control"><option value="cash">Cash</option><option value="card">Card</option><option value="cheque">Cheque</option><option value="bank_transfer">Bank Transfer</option></select></div><div class="col-md-3"><label>Amount</label><input type="number" step="0.0001" name="amount" class="form-control" required></div><div class="col-md-3"><label>Reference</label><input name="reference_no" class="form-control"></div></div>
<div class="row mt-3"><div class="col-md-3"><label>Sales Rep</label><input name="sales_rep_id" class="form-control"></div><div class="col-md-3"><label>Customer</label><input name="customer_id" class="form-control"></div><div class="col-md-3"><label>Sales Invoice</label><input name="sales_invoice_id" class="form-control"></div><div class="col-md-3"><label>Location</label><input name="business_location_id" class="form-control"></div></div>
<div class="row mt-3"><div class="col-md-12"><label>Note</label><textarea name="note" class="form-control"></textarea></div></div><button class="btn btn-primary mt-3">Save</button></form></div>
</div>
@endsection
