@extends('layouts.app')
@section('title', 'Leasing Dashboard')
@section('content')
<section class="content-header no-print"><h1>Leasing <small>Dashboard</small></h1></section>
<section class="content no-print">
@include('leasing::layouts.nav')
@if(! $tablesReady)<div class="alert alert-warning">Leasing database tables are not available yet. Please run the module migrations.</div>@endif
<div class="row">
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-ticket"></i></span><div class="info-box-content"><span class="info-box-text">Active LeaseContracts</span><span class="info-box-number">{{ number_format($summary['active_lease_contracts']) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Outstanding</span><span class="info-box-number">{{ number_format($summary['outstanding_amount'], 2) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-gavel"></i></span><div class="info-box-content"><span class="info-box-text">Insurance Due</span><span class="info-box-number">{{ number_format($summary['insurance_due']) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-diamond"></i></span><div class="info-box-content"><span class="info-box-text">LeaseAssets</span><span class="info-box-number">{{ number_format($summary['lease_assets']) }}</span></div></div></div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Operational Summary</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tbody>
            <tr><th>Products</th><td>{{ number_format($summary['products']) }}</td><th>Asset Types</th><td>{{ number_format($summary['lease_asset_types']) }}</td></tr>
            <tr><th>Total Advance</th><td>{{ number_format($summary['advance_amount'],2) }}</td><th>Today's Transactions</th><td>{{ number_format($summary['today_transactions'],2) }}</td></tr>
            <tr><th>Redeemed</th><td>{{ number_format($summary['redeemed_lease_contracts']) }}</td><th>Active LeaseContracts</th><td>{{ number_format($summary['active_lease_contracts']) }}</td></tr>
        </tbody></table></div></div>
        <div class="box box-warning"><div class="box-header with-border"><h3 class="box-title">Upcoming / Due LeaseContracts</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>LeaseContract No</th><th>Customer</th><th>Due On</th><th>Outstanding</th></tr></thead><tbody>@forelse($summary['due_lease_contracts'] as $lease_contract)<tr><td>{{ $lease_contract->lease_contract_no }}</td><td>{{ $lease_contract->customer_name }}</td><td>{{ $lease_contract->due_on }}</td><td>{{ number_format($lease_contract->outstanding_amount,2) }}</td></tr>@empty<tr><td colspan="4" class="text-center">No due lease_contracts</td></tr>@endforelse</tbody></table></div></div>
    </div>
    <div class="col-md-4">
        <div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Quick Actions</h3></div><div class="box-body">
            <a href="{{ route('leasing.lease_contracts.create') }}" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> New LeaseContract</a>
            <a href="{{ route('leasing.lease_assets.create') }}" class="btn btn-success btn-block"><i class="fa fa-diamond"></i> Register LeaseAsset</a>
            <a href="{{ route('leasing.applications.index') }}" class="btn btn-warning btn-block"><i class="fa fa-calculator"></i> Application Calculator</a>
            <a href="{{ route('leasing.insurance.index') }}" class="btn btn-danger btn-block"><i class="fa fa-gavel"></i> Insurance Due</a>
        </div></div>
    </div>
</div>
</section>
@endsection
