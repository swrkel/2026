@extends('layouts.app')
@section('title', 'Add Vehicle Service Bill')
@section('content')
<section class="content-header"><h1>Add Vehicle Service Bill</h1></section>
<section class="content">
    @if($errors->any())
        <div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="post" action="{{ route('vehicleservice.jobs.store') }}" id="vehicle_service_form">
        @csrf
        <div class="box box-solid"><div class="box-body">
            <div class="row">
                <div class="col-md-3"><label>Transaction Date</label><input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $job->transaction_date) }}" required></div>
                <div class="col-md-3"><label>Vehicle No</label><input type="text" name="vehicle_no" class="form-control" value="{{ old('vehicle_no') }}" required></div>
                <div class="col-md-3"><label>Make</label><input type="text" name="vehicle_make" class="form-control" value="{{ old('vehicle_make') }}"></div>
                <div class="col-md-3"><label>Model</label><input type="text" name="vehicle_model" class="form-control" value="{{ old('vehicle_model') }}"></div>
                <div class="col-md-3"><label>Meter Reading</label><input type="text" name="meter_reading" class="form-control" value="{{ old('meter_reading') }}"></div>
                <div class="col-md-3"><label>Customer Name</label><input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}"></div>
                <div class="col-md-3"><label>Customer Mobile</label><input type="text" name="customer_mobile" class="form-control" value="{{ old('customer_mobile') }}"></div>
                <div class="col-md-3"><label>Status</label><select name="status" class="form-control"><option value="draft">Draft</option><option value="completed">Completed</option></select></div>
            </div>
        </div></div>

        <div class="box box-solid">
            <div class="box-header with-border"><h3 class="box-title">Products / Services</h3><button type="button" class="btn btn-success btn-sm pull-right" id="add_vehicle_line"><i class="fa fa-plus"></i> Add Line</button></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered" id="vehicle_lines_table">
                    <thead><tr><th style="width:22%">Product from Product Module / Custom Item</th><th>Description</th><th style="width:9%">Qty</th><th style="width:12%">Unit Price</th><th style="width:10%">Discount</th><th style="width:10%">Tax</th><th style="width:12%">Line Total</th><th style="width:5%"></th></tr></thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr><th colspan="6" class="text-right">Subtotal</th><th class="text-right" id="vs_subtotal">0.00</th><th></th></tr>
                        <tr><th colspan="6" class="text-right">Discount Total</th><th class="text-right" id="vs_discount_total">0.00</th><th></th></tr>
                        <tr><th colspan="6" class="text-right">Tax Total</th><th class="text-right" id="vs_tax_total">0.00</th><th></th></tr>
                        <tr><th colspan="6" class="text-right">Grand Total</th><th class="text-right" id="vs_total_amount">0.00</th><th></th></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="box box-solid"><div class="box-body">
            <div class="row">
                <div class="col-md-3"><label>Payment Type</label><select name="payment_type" class="form-control"><option value="cash">Cash</option><option value="card">Card</option><option value="credit">Credit</option><option value="cheque">Cheque</option><option value="bank_transfer">Bank Transfer</option></select></div>
                <div class="col-md-3"><label>Paid Amount</label><input type="number" step="0.0001" name="paid_amount" class="form-control vs-paid" value="{{ old('paid_amount', 0) }}"></div>
                <div class="col-md-6"><label>Service Notes</label><textarea name="service_notes" class="form-control" rows="2">{{ old('service_notes') }}</textarea></div>
            </div>
            <br><button type="submit" class="btn btn-primary">Save Vehicle Service Bill</button>
            <a href="{{ route('vehicleservice.jobs.index') }}" class="btn btn-default">Cancel</a>
        </div></div>
    </form>
</section>
<script>
window.vehicleServiceProductSearchUrl = "{{ route('vehicleservice.products.search') }}";
</script>
<script src="{{ asset('modules/vehicleservice/js/vehicle-service.js') }}"></script>
@endsection
