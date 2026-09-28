@extends('layouts.app')

@section('title', 'Portfolio Intelligence')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.loan-dashboard-style')



<section class="content-header">

    <h1>
        Portfolio Intelligence
    </h1>

    <div class="page-subtitle">
        Enterprise loan portfolio analytics, PAR monitoring, exposure intelligence and recovery governance
    </div>

</section>

<section class="content">

    {{-- KPI ROW --}}
    <div class="row">

        <div class="col-md-3">

            <div class="portfolio-card">

                <div class="icon text-primary">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Total Portfolio
                </div>

                <div class="value">
                    {{ number_format($total_portfolio ?? 0, 2) }}
                </div>

                <div class="desc">
                    Total outstanding loan exposure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="portfolio-card">

                <div class="icon text-success">
                    <i class="fa fa-check-circle"></i>
                </div>

                <div class="title">
                    Active Loans
                </div>

                <div class="value">
                    {{ number_format($active_loans ?? 0) }}
                </div>

                <div class="desc">
                    Total active accounts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="portfolio-card">

                <div class="icon text-warning">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Overdue Exposure
                </div>

                <div class="value">
                    {{ number_format($total_overdue ?? 0, 2) }}
                </div>

                <div class="desc">
                    Portfolio at risk monitoring
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="portfolio-card">

                <div class="icon text-danger">
                    <i class="fa fa-heartbeat"></i>
                </div>

                <div class="title">
                    Health Score
                </div>

                <div class="value">
                    {{ number_format($portfolio_health_score ?? 0) }}%
                </div>

                <div class="desc">
                    Executive portfolio quality index
                </div>

            </div>

        </div>

    </div>

    {{-- EXPOSURE ANALYTICS --}}
    <div class="row">

        <div class="col-md-4">

            <div class="analytics-box">

                <div class="analytics-title">
                    Principal Exposure
                </div>

                <h2>
                    {{ number_format($total_principal_outstanding ?? 0, 2) }}
                </h2>

                <p class="text-muted">
                    Total principal outstanding across all active loans.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="analytics-box">

                <div class="analytics-title">
                    Interest Exposure
                </div>

                <h2>
                    {{ number_format($total_interest_outstanding ?? 0, 2) }}
                </h2>

                <p class="text-muted">
                    Pending interest recovery exposure.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="analytics-box">

                <div class="analytics-title">
                    Portfolio Risk Ratio
                </div>

                @php

                    $portfolio_risk_ratio = 0;

                    if (($total_portfolio ?? 0) > 0) {

                        $portfolio_risk_ratio =
                            (($total_overdue ?? 0)
                            / ($total_portfolio ?? 1)) * 100;
                    }

                @endphp

                <h2>
                    {{ number_format($portfolio_risk_ratio, 2) }}%
                </h2>

                <p class="text-muted">
                    Overdue exposure compared against total portfolio.
                </p>

            </div>

        </div>

    </div>

    {{-- PAR ANALYTICS --}}
    <div class="analytics-box">

        <div class="analytics-title">
            PAR / DPD Analytics
        </div>

        <div class="row">

            <div class="col-md-3">
                <div class="par-card bg-par-green">
                    <h2>{{ number_format($par_1_30 ?? 0) }}</h2>
                    <p>PAR 1 - 30 Days</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="par-card bg-par-yellow">
                    <h2>{{ number_format($par_31_60 ?? 0) }}</h2>
                    <p>PAR 31 - 60 Days</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="par-card bg-par-orange">
                    <h2>{{ number_format($par_61_90 ?? 0) }}</h2>
                    <p>PAR 61 - 90 Days</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="par-card bg-par-red">
                    <h2>{{ number_format($par_90_plus ?? 0) }}</h2>
                    <p>PAR 90+ Days</p>
                </div>
            </div>

        </div>

    </div>

    <div class="row">

        {{-- COLLECTION SUMMARY --}}
        <div class="col-md-6">

            <div class="analytics-box">

                <div class="analytics-title">
                    Collection Status Summary
                </div>

                <div class="table-responsive">

                    <table class="table executive-table">

                        <thead>
                            <tr>
                                <th>Collection Status</th>
                                <th class="text-right">Loans</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($collection_summary ?? [] as $row)

                                <tr>

                                    <td>
                                        <span class="status-badge status-warning">
                                            {{ ucfirst(str_replace('_', ' ', $row->collection_status ?? 'Not Set')) }}
                                        </span>
                                    </td>

                                    <td class="text-right">
                                        {{ number_format($row->total ?? 0) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="2" class="text-center text-muted">
                                        No collection data available
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        {{-- HIGH RISK --}}
        <div class="col-md-6">

            <div class="analytics-box">

                <div class="analytics-title">
                    High Risk Loans
                </div>

                <div class="table-responsive">

                    <table class="table executive-table">

                        <thead>
                            <tr>
                                <th>Loan No</th>
                                <th class="text-right">Outstanding</th>
                                <th class="text-right">DPD</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($high_risk_loans ?? [] as $loan)

                                <tr>

                                    <td>
                                        {{ $loan->loan_number ?? $loan->id }}
                                    </td>

                                    <td class="text-right">
                                        {{ number_format($loan->outstanding_amount ?? 0, 2) }}
                                    </td>

                                    <td class="text-right">
                                        {{ number_format($loan->overdue_days ?? 0) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        No high-risk loans detected
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    {{-- RECENT OVERDUES --}}
    <div class="analytics-box">

        <div class="analytics-title">
            Recent Overdue Portfolio
        </div>

        <div class="table-responsive">

            <table class="table executive-table">

                <thead>

                    <tr>
                        <th>Loan No</th>
                        <th>Status</th>
                        <th>Collection</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Overdue</th>
                        <th class="text-right">DPD</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($recent_overdues ?? [] as $loan)

                        <tr>

                            <td>
                                {{ $loan->loan_number ?? $loan->id }}
                            </td>

                            <td>

                                <span class="status-badge status-active">
                                    {{ ucfirst($loan->status ?? 'N/A') }}
                                </span>

                            </td>

                            <td>

                                <span class="status-badge status-warning">
                                    {{ ucfirst(str_replace('_', ' ', $loan->collection_status ?? 'Not Set')) }}
                                </span>

                            </td>

                            <td class="text-right">
                                {{ number_format($loan->outstanding_amount ?? 0, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($loan->overdue_amount ?? 0, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($loan->overdue_days ?? 0) }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No overdue loans available
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection