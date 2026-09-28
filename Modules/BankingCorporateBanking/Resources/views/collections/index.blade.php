@extends('bankingcorporatebanking::layout')
@section('banking_corporate_content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle ?? 'Corporate Banking Dashboard' }}</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-sm btn-primary">Search</button>
            <button class="btn btn-sm btn-default">CSV</button>
            <button class="btn btn-sm btn-default">Excel</button>
            <button class="btn btn-sm btn-default">PDF</button>
            <button class="btn btn-sm btn-default">Print</button>
            <button class="btn btn-sm btn-default">Column Visibility</button>
        </div>
    </div>
    <div class="box-body">
        <div class="alert alert-info">
            This page is part of the standalone Corporate Banking module. Functional workflows are separated under Modules/BankingCorporateBanking.
        </div>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th>Reference</th><th>Description</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <tr><td>CB-001</td><td>{{ $pageTitle ?? 'Corporate Banking' }}</td><td><span class="label label-warning">Ready for UI Testing</span></td><td><button class="btn btn-xs btn-primary">View</button></td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
