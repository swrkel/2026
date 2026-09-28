@extends('layouts.app')
@section('title', __('distributionnew::production_completion.management_dashboard'))
@section('content')
<section class="content-header"><h1>{ __('distributionnew::production_completion.management_dashboard') }</h1></section>
<section class="content">
    <div class="disnew-production-card">
        <div class="disnew-toolbar">
            <button class="btn btn-primary disnew-run-validation" data-url="#"><i class="fa fa-refresh"></i> { __('distributionnew::production_completion.run_validation') }</button>
        </div>
        <div class="row">
            <div class="col-md-3"><div class="disnew-kpi">0</div><div class="disnew-muted">Pending</div></div>
            <div class="col-md-3"><div class="disnew-kpi">0</div><div class="disnew-muted">Completed</div></div>
            <div class="col-md-3"><div class="disnew-kpi">0</div><div class="disnew-muted">Variance</div></div>
            <div class="col-md-3"><div class="disnew-kpi">0</div><div class="disnew-muted">Alerts</div></div>
        </div>
    </div>
    <div class="box box-solid"><div class="box-body"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Reference</th><th>Status</th><th>Action</th></tr></thead><tbody></tbody></table></div></div>
</section>
@endsection
