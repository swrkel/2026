@extends('expensesnew::layouts.app')
@section('title', 'Budget Control')
@section('content')
<div class="expnew-page">
    <div class="expnew-header"><h3>Budget Control</h3><a class="expnew-btn expnew-btn-primary" href="#">Add New</a></div>
    @include('expensesnew::components.toolbar')
    <div class="expnew-card">
        <table class="table table-bordered expnew-table" id="budget_control_table">
            <thead><tr><th>Date</th><th>Code</th><th>Name</th><th>Status</th><th class="text-right">Amount</th><th>Action</th></tr></thead>
            <tbody><tr><td colspan="6" class="text-center text-muted">Records will appear here</td></tr></tbody>
        </table>
    </div>
</div>
@endsection
