@extends('tailoring::layouts.app')
@section('title', 'Workflow Designer')
@section('content')
<div class="tailoring-page tm-enterprise-operations">
    <div class="tailoring-page-header">
        <h3>Workflow Designer</h3>
        <p class="text-muted">Branch/location wise and consolidated ready. Advanced functionality remains permission controlled so small shops can keep a simple interface.</p>
    </div>
    @include('tailoring::partials.standard_filters')
    <div class="row">
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Total</strong><br><span class="h4">{{ $summary['total'] ?? 0 }}</span></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Pending</strong><br><span class="h4">{{ $summary['pending'] ?? 0 }}</span></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Completed</strong><br><span class="h4">{{ $summary['completed'] ?? 0 }}</span></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Value</strong><br><span class="h4">{{ number_format($summary['value'] ?? 0, 2) }}</span></div></div></div>
    </div>
    <div class="card mt-3"><div class="card-body">
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Date</th><th>Reference</th><th>Branch</th><th>Status</th><th>Amount/Qty</th><th>Remarks</th></tr></thead>
            <tbody><tr><td colspan="6" class="text-center text-muted">No records found for selected filters.</td></tr></tbody>
        </table></div>
    </div></div>
</div>
@endsection
