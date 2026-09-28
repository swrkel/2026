@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Add Quotation</h1><p>Prepare quote with garment items, measurement profile, material estimate and price.</p></div><a href="{{ route('tailoring.quotations.index') }}" class="btn btn-default">Back</a></div>
    <div class="tailoring-card">
        <form method="POST">@csrf
            <div class="row">
                <div class="col-md-3"><label>Customer</label><select class="form-control tailoring-select2" name="customer_id"></select></div>
                <div class="col-md-3"><label>Quotation Date</label><input type="date" class="form-control" name="quotation_date" value="{{ date('Y-m-d') }}"></div>
                <div class="col-md-3"><label>Valid Until</label><input type="date" class="form-control" name="valid_until"></div>
                <div class="col-md-3"><label>Branch</label><select class="form-control tailoring-select2" name="location_id"></select></div>
            </div><hr>
            <div class="tailoring-item-row">Quotation items will be added here.</div>
            <button class="btn btn-primary">Save Quotation</button>
        </form>
    </div>
</div>
@endsection
