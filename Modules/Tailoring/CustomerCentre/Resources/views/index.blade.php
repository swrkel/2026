@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_customer_measurement.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Customer & Measurement Centre</h1><p>Customer profile, measurements, wardrobe, orders, trials, alterations and payments.</p></div><a href="{{ route('tailoring.customer_centre.create') }}" class="btn btn-primary">Add Customer</a></div>
    <div class="tailoring-kpi-grid">
        <div class="tailoring-kpi-card"><h4>Total Customers</h4><strong>{{ $summary['total_customers'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>New This Month</h4><strong>{{ $summary['new_this_month'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Active Orders</h4><strong>{{ $summary['active_orders'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Outstanding</h4><strong>{{ number_format($summary['outstanding_balance'] ?? 0, 2) }}</strong></div>
    </div>
    <div class="tailoring-card">
        <div class="tailoring-toolbar"><input type="text" class="form-control" placeholder="Search customer, mobile, NIC, code"><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div><hr>
        <table class="table table-bordered table-striped"><thead><tr><th>Customer</th><th>Mobile</th><th>Measurement Profiles</th><th>Active Orders</th><th>Outstanding</th><th>Action</th></tr></thead><tbody><tr><td colspan="6" class="text-center">No data loaded</td></tr></tbody></table>
    </div>
</div>
<script src="{{ asset('modules/tailoring/js/tailoring_customer_measurement.js') }}"></script>
@endsection
