@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Job Cards</h1><p>Production job cards, QR/barcode, workflow and materials.</p></div></div>
    <div class="tailoring-kpi-grid">
        <div class="tailoring-kpi-card"><h4>Total</h4><strong>{{ $summary['total'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Pending</h4><strong>{{ $summary['pending'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Active</h4><strong>{{ $summary['active'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Completed</h4><strong>{{ $summary['completed'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Overdue</h4><strong>{{ $summary['overdue'] ?? 0 }}</strong></div>
    </div>
    <div class="tailoring-card">
        <div class="tailoring-toolbar"><input class="form-control" placeholder="Search job card, order, customer"><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div><hr>
        <table class="table table-bordered table-striped"><thead><tr><th>Job Card</th><th>Order</th><th>Customer</th><th>Garment</th><th>Stage</th><th>Due</th><th>Action</th></tr></thead><tbody><tr><td colspan="7" class="text-center">No data loaded</td></tr></tbody></table>
    </div>
</div>
@endsection
