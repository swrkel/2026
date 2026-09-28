@extends('distributionnew::layouts.app')
@section('title','Create Loading Plan')
@section('subtitle','Select vehicle, driver, sales rep and planned order items.')
@section('module_content')
<form method="POST" action="{{ route('distributionnew.loading-plans.store') }}" class="disnew-card">@csrf
    <div class="row">
        <div class="col-md-3"><label>Plan Date</label><input type="date" name="plan_date" value="{{ date('Y-m-d') }}" class="form-control"></div>
        <div class="col-md-3"><label>Vehicle ID</label><input name="vehicle_id" class="form-control" placeholder="Vehicle"></div>
        <div class="col-md-3"><label>Driver ID</label><input name="driver_id" class="form-control" placeholder="Driver"></div>
        <div class="col-md-3"><label>Sales Rep ID</label><input name="sales_rep_id" class="form-control" placeholder="Sales Rep"></div>
    </div>
    <hr>
    <h5>Items</h5>
    <div class="table-responsive">
        <table class="table table-bordered disnew-table" id="disnew-plan-lines">
            <thead><tr><th>Sales Order ID</th><th>Product ID</th><th>Product Name</th><th>Planned Qty</th></tr></thead>
            <tbody>
                <tr><td><input name="lines[0][sales_order_id]" class="form-control"></td><td><input name="lines[0][product_id]" class="form-control"></td><td><input name="lines[0][product_name]" class="form-control"></td><td><input name="lines[0][planned_qty]" class="form-control text-right" value="0"></td></tr>
            </tbody>
        </table>
    </div>
    <label>Note</label><textarea name="note" class="form-control" rows="3"></textarea>
    <div class="text-right mt-3"><button class="btn btn-primary">Save Loading Plan</button></div>
</form>
@endsection
