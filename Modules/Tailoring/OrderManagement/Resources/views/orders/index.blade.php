@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Tailoring Orders</h1><p>Orders, production status, delivery, payments and timeline.</p></div><a href="{{ route('tailoring.orders.create') }}" class="btn btn-primary">Add Order</a></div>
    <div class="tailoring-kpi-grid">
        <div class="tailoring-kpi-card"><h4>Today</h4><strong>{{ $summary['orders_today'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Pending</h4><strong>{{ $summary['pending'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>In Production</h4><strong>{{ $summary['in_production'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Ready</h4><strong>{{ $summary['ready'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Delivered</h4><strong>{{ $summary['delivered'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Outstanding</h4><strong>{{ number_format($summary['outstanding'] ?? 0, 2) }}</strong></div>
    </div>
    <div class="tailoring-card">
        <div class="tailoring-toolbar"><input class="form-control" placeholder="Search order, customer, garment"><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div><hr>
        <table class="table table-bordered table-striped"><thead><tr><th>Order No</th><th>Customer</th><th>Delivery</th><th>Status</th><th>Total</th><th>Outstanding</th><th>Action</th></tr></thead><tbody><tr><td colspan="7" class="text-center">No data loaded</td></tr></tbody></table>
    </div>
</div>
@endsection
