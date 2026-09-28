@extends('customers::layouts.app')
@section('title', 'Customer Workflow')
@section('content')
<section class="content-header"><h1>Customer Workflow &amp; Approvals</h1></section>
<section class="content">
    @if(!empty($missingTables))
        <div class="alert alert-warning" style="border-radius:10px;">
            <i class="fa fa-exclamation-triangle"></i>
            The Workflow page is now open, but these tenant tables must be installed before workflow actions can be used:
            <strong>{{ implode(', ', $missingTables) }}</strong>.
            Run <strong>00_MASTER_CUSTOMERS_IS1805.sql</strong> in this tenant database and refresh the page.
        </div>
    @endif

    <div class="row">
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">Pending</span><span class="info-box-number">{{ $pending }}</span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Approved</span><span class="info-box-number">{{ $approved }}</span></div></div></div>
        <div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-times"></i></span><div class="info-box-content"><span class="info-box-text">Rejected</span><span class="info-box-number">{{ $rejected }}</span></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Workflow Actions</h3></div>
        <div class="box-body">
            @if(empty($missingTables))
                <a href="{{ route('customers.workflow.approvals.index') }}" class="btn btn-primary">Customer Approvals</a>
                <a href="{{ route('customers.workflow.credit_approvals.index') }}" class="btn btn-info">Credit Approvals</a>
                <a href="{{ route('customers.workflow.status.index') }}" class="btn btn-warning">Status Changes</a>
                <a href="{{ route('customers.workflow.history.index') }}" class="btn btn-default">Workflow History</a>
                <a href="{{ route('customers.workflow.audit.index') }}" class="btn btn-default">Approval Audit</a>
            @else
                <button type="button" class="btn btn-primary" disabled>Customer Approvals</button>
                <button type="button" class="btn btn-info" disabled>Credit Approvals</button>
                <button type="button" class="btn btn-warning" disabled>Status Changes</button>
                <button type="button" class="btn btn-default" disabled>Workflow History</button>
                <button type="button" class="btn btn-default" disabled>Approval Audit</button>
            @endif
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">Recent Workflow History</h3></div>
        <div class="box-body table-responsive">
            @include('customers::workflow.history.table', ['history' => $history])
        </div>
    </div>
</section>
@endsection
