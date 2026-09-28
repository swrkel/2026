@extends('layouts.app')
@section('title', 'Deposits Dashboard')
@section('content')
<section class="content-header no-print">
    <h1>Deposits <small>Dashboard</small></h1>
</section>
<section class="content no-print">
@include('deposits::layouts.nav')
@if(! $tablesReady)
    <div class="alert alert-warning">Deposits database tables are not available yet. Please run the module migrations.</div>
@endif
<div class="row">
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-bank"></i></span><div class="info-box-content"><span class="info-box-text">Active Accounts</span><span class="info-box-number">{{ number_format($summary['active_accounts']) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Total Balance</span><span class="info-box-number">{{ number_format($summary['balance'], 2) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-calendar"></i></span><div class="info-box-content"><span class="info-box-text">Maturity Due</span><span class="info-box-number">{{ number_format($summary['maturity_due']) }}</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-percent"></i></span><div class="info-box-content"><span class="info-box-text">Interest Accrued</span><span class="info-box-number">{{ number_format($summary['interest'], 2) }}</span></div></div></div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Operational Summary</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr><th>Products</th><td>{{ number_format($summary['products']) }}</td><th>Total Accounts</th><td>{{ number_format($summary['accounts']) }}</td></tr>
                        <tr><th>Principal</th><td>{{ number_format($summary['principal'], 2) }}</td><th>Current Balance</th><td>{{ number_format($summary['balance'], 2) }}</td></tr>
                        <tr><th>Maturing Next 30 Days</th><td>{{ number_format($summary['maturity_next_30']) }}</td><th>Today's Transaction Value</th><td>{{ number_format($summary['today_transactions'], 2) }}</td></tr>
                        <tr><th>Closed Accounts</th><td>{{ number_format($summary['closed_accounts']) }}</td><th>Renewed Accounts</th><td>{{ number_format($summary['renewed_accounts']) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-solid">
            <div class="box-header with-border"><h3 class="box-title">Quick Actions</h3></div>
            <div class="box-body">
                <a href="{{ route('deposits.accounts.create') }}" class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Open Deposit Account</a>
                <a href="{{ route('deposits.transactions.create') }}" class="btn btn-success btn-block"><i class="fa fa-exchange"></i> Add Transaction</a>
                <a href="{{ route('deposits.interest.index') }}" class="btn btn-warning btn-block"><i class="fa fa-percent"></i> Post Interest</a>
                <a href="{{ route('deposits.maturity.due') }}" class="btn btn-danger btn-block"><i class="fa fa-calendar-check-o"></i> View Maturity Due</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Recent Deposit Accounts</h3></div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped">
                    <thead><tr><th>Account No</th><th>Customer</th><th>Product</th><th class="text-right">Balance</th></tr></thead>
                    <tbody>
                    @forelse(($summary['recent_accounts'] ?? collect()) as $recent)
                        <tr><td><a href="{{ route('deposits.accounts.show', $recent->id) }}">{{ $recent->account_no }}</a></td><td>{{ $recent->customer_name }}</td><td>{{ optional($recent->product)->name }}</td><td class="text-right">{{ number_format($recent->current_balance, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No recent accounts</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">Upcoming Maturities</h3></div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped">
                    <thead><tr><th>Account No</th><th>Customer</th><th>Maturity</th><th class="text-right">Balance</th></tr></thead>
                    <tbody>
                    @forelse(($summary['upcoming_maturities'] ?? collect()) as $maturity)
                        <tr><td><a href="{{ route('deposits.accounts.show', $maturity->id) }}">{{ $maturity->account_no }}</a></td><td>{{ $maturity->customer_name }}</td><td>{{ $maturity->maturity_on }}</td><td class="text-right">{{ number_format($maturity->current_balance, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No maturities in next 30 days</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</section>
@endsection
