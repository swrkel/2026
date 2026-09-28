@extends('tailoring::layouts.app')
@section('title', 'Department Work Queues')
@section('content')
<div class="tailoring-page tm-large-parcel">
    <div class="tailoring-page-header">
        <h3>Department Work Queues</h3>
        <p class="text-muted">Branch/location wise and consolidated ready. Advanced features are permission controlled.</p>
    </div>
    @include('tailoring::partials.standard_filters')
    <div class="row">
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Total</strong><br><span class="h4">0</span></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Pending</strong><br><span class="h4">0</span></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Completed</strong><br><span class="h4">0</span></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Value</strong><br><span class="h4">0.00</span></div></div></div>
    </div>
    <div class="card mt-3"><div class="card-body">
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Date</th><th>Reference</th><th>Branch</th><th>Status</th><th>Amount/Qty</th><th>Remarks</th></tr></thead>
            <tbody><tr><td colspan="6" class="text-center text-muted">No records found for selected filters.</td></tr></tbody>
        </table></div>
    </div></div>
</div>
@endsection
