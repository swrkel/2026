@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Add Tailoring Order</h1><p>Create order with multiple garments, measurements, delivery date and payment plan.</p></div><a href="{{ route('tailoring.orders.index') }}" class="btn btn-default">Back</a></div>
    <div class="tailoring-card">
        <form method="POST">@csrf
            <div class="row">
                <div class="col-md-3"><label>Customer</label><select class="form-control tailoring-select2" name="customer_id"></select></div>
                <div class="col-md-3"><label>Order Date</label><input type="date" class="form-control" name="order_date" value="{{ date('Y-m-d') }}"></div>
                <div class="col-md-3"><label>Delivery Date</label><input type="date" class="form-control" name="delivery_date"></div>
                <div class="col-md-3"><label>Priority</label><select class="form-control"><option>Normal</option><option>Urgent</option></select></div>
            </div><hr>
            <div class="tailoring-item-row">Order garment items will be added here.</div>
            <button class="btn btn-primary">Save Order</button>
        </form>
    </div>
</div>
@endsection
