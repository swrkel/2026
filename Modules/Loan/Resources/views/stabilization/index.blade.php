@extends('layouts.app')
@section('title', 'Loan Stabilization')

@section('content')
<section class="content-header no-print">
    <h1>Loan Stabilization <small>Operations, arrears and route-safe monitoring</small></h1>
</section>

<section class="content no-print">
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-briefcase"></i></span><div class="info-box-content"><span class="info-box-text">Total Loans</span><span class="info-box-number">{{ number_format($summary['total_loans'] ?? 0) }}</span></div></div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check-circle"></i></span><div class="info-box-content"><span class="info-box-text">Active Loans</span><span class="info-box-number">{{ number_format($summary['active_loans'] ?? 0) }}</span></div></div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-hourglass-half"></i></span><div class="info-box-content"><span class="info-box-text">Pending</span><span class="info-box-number">{{ number_format($summary['pending_loans'] ?? 0) }}</span></div></div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span><div class="info-box-content"><span class="info-box-text">Arrears Accounts</span><span class="info-box-number">{{ number_format($summary['arrears_accounts'] ?? 0) }}</span></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="small-box bg-blue"><div class="inner"><h3>{{ number_format($summary['portfolio_amount'] ?? 0, 2) }}</h3><p>Portfolio Amount</p></div><div class="icon"><i class="fa fa-line-chart"></i></div></div>
        </div>
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="small-box bg-orange"><div class="inner"><h3>{{ number_format($summary['due_today'] ?? 0) }}</h3><p>Installments Due Today</p></div><div class="icon"><i class="fa fa-calendar"></i></div></div>
        </div>
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="small-box bg-red"><div class="inner"><h3>{{ number_format($summary['overdue_amount'] ?? 0, 2) }}</h3><p>Overdue Amount</p></div><div class="icon"><i class="fa fa-exclamation-triangle"></i></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="box box-danger">
                <div class="box-header with-border"><h3 class="box-title">Arrears Aging</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Bucket</th><th class="text-right">Accounts</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            @foreach($aging as $bucket => $row)
                                <tr>
                                    <td>{{ str_replace('_', '-', $bucket) }} days</td>
                                    <td class="text-right">{{ number_format($row['count'] ?? 0) }}</td>
                                    <td class="text-right">{{ number_format($row['amount'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Recent Operational Items</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>ID</th><th>Status</th><th class="text-right">Amount</th><th>Location</th><th>Created</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($recentLoans as $loan)
                            <tr>
                                <td>{{ $loan->id ?? '' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $loan->status ?? '-')) }}</td>
                                <td class="text-right">{{ number_format($loan->principal_amount ?? $loan->loan_amount ?? $loan->approved_amount ?? $loan->amount ?? 0, 2) }}</td>
                                <td>{{ $loan->location_id ?? '-' }}</td>
                                <td>{{ $loan->created_at ?? '-' }}</td>
                                <td><a class="btn btn-xs btn-default" href="{{ url('loan/loans/' . ($loan->id ?? 0) . '/statement') }}">Statement</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No loan records found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
