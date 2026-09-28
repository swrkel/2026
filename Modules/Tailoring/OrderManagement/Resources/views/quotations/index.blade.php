@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header">
        <div><h1>Quotations</h1><p>Create quotations, track approvals and convert to orders.</p></div>
        <a href="{{ route('tailoring.quotations.create') }}" class="btn btn-primary">Add Quotation</a>
    </div>
    <div class="tailoring-kpi-grid">
        <div class="tailoring-kpi-card"><h4>Draft</h4><strong>{{ $summary['draft'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Sent</h4><strong>{{ $summary['sent'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Approved</h4><strong>{{ $summary['approved'] ?? 0 }}</strong></div>
        <div class="tailoring-kpi-card"><h4>Converted</h4><strong>{{ $summary['converted'] ?? 0 }}</strong></div>
    </div>
    <div class="tailoring-card">
        <div class="tailoring-toolbar">
            <input class="form-control" placeholder="Search quotation, customer, mobile">
            <div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div>
        </div><hr>
        <table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Customer</th><th>Date</th><th>Valid Until</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody><tr><td colspan="7" class="text-center">No data loaded</td></tr></tbody></table>
    </div>
</div>
<script src="{{ asset('modules/tailoring/js/order_management.js') }}"></script>
@endsection
