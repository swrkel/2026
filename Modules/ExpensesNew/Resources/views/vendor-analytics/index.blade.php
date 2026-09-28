@extends('expensesnew::layouts.app')
@section('title', 'Vendor Spend Analytics')
@section('content')
<div class="expnew-page">
    <div class="expnew-header"><h3>Vendor Spend Analytics</h3><a class="expnew-btn expnew-btn-primary" href="#">Add New</a></div>
    @include('expensesnew::components.toolbar')
    <div class="expnew-card">
        <table class="table table-bordered expnew-table" id="vendor_spend_analytics_table">
            <thead><tr><th>Date</th><th>Code</th><th>Name</th><th>Status</th><th class="text-right">Amount</th><th>Action</th></tr></thead>
            <tbody><tr><td colspan="6" class="text-center text-muted">Records will appear here</td></tr></tbody>
        </table>
    </div>
</div>
@endsection
