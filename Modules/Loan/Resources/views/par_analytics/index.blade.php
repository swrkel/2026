@extends('layouts.app')

@section('title', 'PAR Analytics')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.loan-dashboard-style')

<section class="content-header">

    <h1>
        PAR Analytics
    </h1>

    <div class="page-subtitle">
        Portfolio-at-risk aging, delinquency exposure and critical overdue account monitoring
    </div>

</section>

<section class="content">

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
                    Outstanding exposure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="portfolio-card">

                <div class="icon text-warning">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Total Overdue
                </div>

                <div class="value">
                    {{ number_format($total_overdue ?? 0, 2) }}
                </div>

                <div class="desc">
                    Delinquent exposure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="portfolio-card">

                <div class="icon text-danger">
                    <i class="fa fa-fire"></i>
                </div>

                <div class="title">
                    PAR 90+ Accounts
                </div>

                <div class="value">
                    {{ number_format($par_90_plus ?? 0) }}
                </div>

                <div class="desc">
                    Critical delinquency
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="portfolio-card">

                <div class="icon text-success">
                    <i class="fa fa-list"></i>
                </div>

                <div class="title">
                    Total Loans
                </div>

                <div class="value">
                    {{ number_format($total_loans ?? 0) }}
                </div>

                <div class="desc">
                    Accounts monitored
                </div>

            </div>

        </div>

    </div>

    <div class="analytics-box">

        <div class="analytics-title">
            PAR Aging Buckets
        </div>

        <div class="row">

            <div class="col-md-3">
                <div class="par-card bg-par-green">
                    <h2>{{ number_format($par_1_30 ?? 0) }}</h2>
                    <p>PAR 1 - 30 Days</p>
                    <small>{{ number_format($par_amount_1_30 ?? 0, 2) }}</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="par-card bg-par-yellow">
                    <h2>{{ number_format($par_31_60 ?? 0) }}</h2>
                    <p>PAR 31 - 60 Days</p>
                    <small>{{ number_format($par_amount_31_60 ?? 0, 2) }}</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="par-card bg-par-orange">
                    <h2>{{ number_format($par_61_90 ?? 0) }}</h2>
                    <p>PAR 61 - 90 Days</p>
                    <small>{{ number_format($par_amount_61_90 ?? 0, 2) }}</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="par-card bg-par-red">
                    <h2>{{ number_format($par_90_plus ?? 0) }}</h2>
                    <p>PAR 90+ Days</p>
                    <small>{{ number_format($par_amount_90_plus ?? 0, 2) }}</small>
                </div>
            </div>

        </div>

    </div>

    <div class="analytics-box">

        <div class="analytics-title">
            Critical PAR 90+ Accounts
        </div>

        <div class="table-responsive">

            <table class="table executive-table">

                <thead>
                    <tr>
                        <th>Loan No</th>
                        <th>Status</th>
                        <th>Collection Status</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Overdue</th>
                        <th class="text-right">DPD</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($critical_accounts ?? [] as $loan)

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
                                No critical PAR accounts found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection