@extends('layouts.app')
@section('title', __('distributionnew::operational_polish.Reconciliation Exceptions'))
@section('content')
<section class="content-header distribution-new-pos-header">
    <h1>{{ __('distributionnew::operational_polish.Reconciliation Exceptions') }}</h1>
</section>
<section class="content distribution-new-pos-page">
    <div class="row g-3">
        <div class="col-md-3"><div class="disnew-pos-card"><span class="caption">Today</span><h3>0.0000</h3></div></div>
        <div class="col-md-3"><div class="disnew-pos-card"><span class="caption">This Month</span><h3>0.0000</h3></div></div>
        <div class="col-md-3"><div class="disnew-pos-card"><span class="caption">Open Items</span><h3>0</h3></div></div>
        <div class="col-md-3"><div class="disnew-pos-card"><span class="caption">Completed</span><h3>0</h3></div></div>
    </div>
    <div class="box box-primary disnew-pos-box mt-3">
        <div class="box-header with-border"><h3 class="box-title">{{ __('distributionnew::operational_polish.Reconciliation Exceptions') }}</h3></div>
        <div class="box-body">
            <div class="disnew-toolbar">
                <input class="form-control input-sm" placeholder="Search">
                <button class="btn btn-primary btn-sm">CSV</button>
                <button class="btn btn-success btn-sm">Excel</button>
                <button class="btn btn-danger btn-sm">PDF</button>
                <button class="btn btn-info btn-sm">Print</button>
                <button class="btn btn-warning btn-sm">Column Visibility</button>
            </div>
            <table class="table table-bordered table-striped disnew-table"><thead><tr><th>Date</th><th>Reference</th><th>Status</th><th class="text-right">Amount</th><th>Action</th></tr></thead><tbody><tr><td colspan="5" class="text-center text-muted">Ready for live data binding.</td></tr></tbody></table>
        </div>
    </div>
</section>
@endsection
