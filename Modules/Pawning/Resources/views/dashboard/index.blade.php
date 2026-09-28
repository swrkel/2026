@extends('layouts.app')
@section('title', 'Pawning Dashboard')
@section('content')
<section class="content-header no-print"><h1>Pawning <small>Dashboard</small></h1></section>
<section class="content no-print">
@include('pawning::layouts.nav')
@if(! $tablesReady)<div class="alert alert-warning">Pawning database tables are not available yet. Please run the module migrations.</div>@endif
<div class="row">
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-ticket"></i></span><div class="info-box-content"><span class="info-box-text">Active Pledges</span><span class="info-box-number">{{ number_format($summary['active_pledges']) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Outstanding</span><span class="info-box-number">{{ number_format($summary['outstanding_amount'], 2) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-gavel"></i></span><div class="info-box-content"><span class="info-box-text">Auction Due</span><span class="info-box-number">{{ number_format($summary['auction_due']) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-diamond"></i></span><div class="info-box-content"><span class="info-box-text">Articles</span><span class="info-box-number">{{ number_format($summary['articles']) }}</span></div></div></div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Operational Summary</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tbody>
            <tr><th>Products</th><td>{{ number_format($summary['products']) }}</td><th>Collateral Types</th><td>{{ number_format($summary['collateral_types']) }}</td></tr>
            <tr><th>Total Advance</th><td>{{ number_format($summary['advance_amount'],2) }}</td><th>Today's Transactions</th><td>{{ number_format($summary['today_transactions'],2) }}</td></tr>
            <tr><th>Redeemed</th><td>{{ number_format($summary['redeemed_pledges']) }}</td><th>Active Pledges</th><td>{{ number_format($summary['active_pledges']) }}</td></tr>
        </tbody></table></div></div>
        <div class="box box-warning"><div class="box-header with-border"><h3 class="box-title">Upcoming / Due Pledges</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Pledge No</th><th>Customer</th><th>Due On</th><th>Outstanding</th></tr></thead><tbody>@forelse($summary['due_pledges'] as $pledge)<tr><td>{{ $pledge->pledge_no }}</td><td>{{ $pledge->customer_name }}</td><td>{{ $pledge->due_on }}</td><td>{{ number_format($pledge->outstanding_amount,2) }}</td></tr>@empty<tr><td colspan="4" class="text-center">No due pledges</td></tr>@endforelse</tbody></table></div></div>
    </div>
    <div class="col-md-4">
        <div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Quick Actions</h3></div><div class="box-body">
            <a href="{{ route('pawning.pledges.create') }}" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> New Pledge</a>
            <a href="{{ route('pawning.articles.create') }}" class="btn btn-success btn-block"><i class="fa fa-diamond"></i> Register Article</a>
            <a href="{{ route('pawning.valuations.index') }}" class="btn btn-warning btn-block"><i class="fa fa-calculator"></i> Valuation Calculator</a>
            <a href="{{ route('pawning.auction.index') }}" class="btn btn-danger btn-block"><i class="fa fa-gavel"></i> Auction Due</a>
        </div></div>
    </div>
</div>
</section>
@endsection
