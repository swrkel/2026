@extends('layouts.app')

@section('title', 'Enterprise Portfolio Intelligence')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Enterprise Portfolio Intelligence
        <small>Advanced Portfolio Analytics & Recovery Governance</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       EXECUTIVE KPI ROW 1
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-list"></i>
                </div>

                <div class="title">
                    Total Loans
                </div>

                <div class="value">
                    {{ number_format($total_loans) }}
                </div>

                <div class="subtext">
                    Total portfolio accounts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-check"></i>
                </div>

                <div class="title">
                    Active Loans
                </div>

                <div class="value">
                    {{ number_format($active_loans) }}
                </div>

                <div class="subtext">
                    Active performing facilities
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-folder"></i>
                </div>

                <div class="title">
                    Closed Loans
                </div>

                <div class="value">
                    {{ number_format($closed_loans) }}
                </div>

                <div class="subtext">
                    Closed facilities portfolio
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-money"></i>
                </div>

                <div class="title">
                    Total Disbursed
                </div>

                <div class="value">
                    {{ number_format($total_disbursed, 2) }}
                </div>

                <div class="subtext">
                    Enterprise disbursement exposure
                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       EXECUTIVE KPI ROW 2
    ========================================================== --}}

    <div class="row">

        <div class="col-md-6">

            <div class="enterprise-card enterprise-purple">

                <div class="icon" style="color:#8e44ad;">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Principal Outstanding
                </div>

                <div class="value">
                    {{ number_format($principal_outstanding, 2) }}
                </div>

                <div class="subtext">
                    Outstanding principal exposure
                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Total Overdue
                </div>

                <div class="value">
                    {{ number_format($total_overdue, 2) }}
                </div>

                <div class="subtext">
                    Total overdue recovery pressure
                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       PAR BUCKET ANALYTICS
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-clock-o"></i>
                </div>

                <div class="title">
                    PAR 1-30 Days
                </div>

                <div class="value">
                    {{ number_format($par_1_30, 2) }}
                </div>

                <div class="subtext">
                    Early delinquency exposure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    PAR 31-60 Days
                </div>

                <div class="value">
                    {{ number_format($par_31_60, 2) }}
                </div>

                <div class="subtext">
                    Moderate recovery pressure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-purple">

                <div class="icon" style="color:#8e44ad;">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>

                <div class="title">
                    PAR 61-90 Days
                </div>

                <div class="value">
                    {{ number_format($par_61_90, 2) }}
                </div>

                <div class="subtext">
                    High delinquency exposure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-gray">

                <div class="icon text-muted">
                    <i class="fa fa-ban"></i>
                </div>

                <div class="title">
                    PAR 90+ Days
                </div>

                <div class="value">
                    {{ number_format($par_90_plus, 2) }}
                </div>

                <div class="subtext">
                    Severe recovery risk exposure
                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       EXECUTIVE CHARTS
    ========================================================== --}}

    <div class="row">

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">

                    <i class="fa fa-pie-chart"></i>

                    PAR Bucket Distribution

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="parDistributionChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">

                    <i class="fa fa-bar-chart"></i>

                    Portfolio Exposure Analytics

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="portfolioExposureChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       PORTFOLIO ANALYTICS TABLE
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-table"></i>

            Portfolio Intelligence Summary

        </div>

        <table class="table table-bordered enterprise-table">

            <tr>

                <th>Total Loans</th>

                <td class="text-right">

                    {{ number_format($total_loans) }}

                </td>

            </tr>

            <tr>

                <th>Active Loans</th>

                <td class="text-right">

                    {{ number_format($active_loans) }}

                </td>

            </tr>

            <tr>

                <th>Closed Loans</th>

                <td class="text-right">

                    {{ number_format($closed_loans) }}

                </td>

            </tr>

            <tr>

                <th>Total Disbursed</th>

                <td class="text-right">

                    {{ number_format($total_disbursed, 2) }}

                </td>

            </tr>

            <tr>

                <th>Principal Outstanding</th>

                <td class="text-right">

                    {{ number_format($principal_outstanding, 2) }}

                </td>

            </tr>

            <tr>

                <th>Total Overdue</th>

                <td class="text-right">

                    {{ number_format($total_overdue, 2) }}

                </td>

            </tr>

        </table>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | PAR Distribution Chart
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('parDistributionChart'), {

        type: 'doughnut',

        data: {

            labels: [

                'PAR 1-30',
                'PAR 31-60',
                'PAR 61-90',
                'PAR 90+'

            ],

            datasets: [{

                data: [

                    {{ $par_1_30 ?? 0 }},
                    {{ $par_31_60 ?? 0 }},
                    {{ $par_61_90 ?? 0 }},
                    {{ $par_90_plus ?? 0 }}

                ],

                backgroundColor: [

                    '#f39c12',
                    '#e74c3c',
                    '#8e44ad',
                    '#2c3e50'

                ]

            }]

        },

        options: {

            responsive: true,
            maintainAspectRatio: false

        }

    });

    /*
    |--------------------------------------------------------------------------
    | Portfolio Exposure Analytics
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('portfolioExposureChart'), {

        type: 'bar',

        data: {

            labels: [

                'Disbursed',
                'Outstanding',
                'Overdue'

            ],

            datasets: [{

                label: 'Portfolio Analytics',

                data: [

                    {{ $total_disbursed ?? 0 }},
                    {{ $principal_outstanding ?? 0 }},
                    {{ $total_overdue ?? 0 }}

                ],

                backgroundColor: [

                    '#3498db',
                    '#27ae60',
                    '#e74c3c'

                ]

            }]

        },

        options: {

            responsive: true,
            maintainAspectRatio: false

        }

    });

</script>

@endsection