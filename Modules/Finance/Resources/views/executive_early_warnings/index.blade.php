@extends('layouts.app')

@section('title', 'Executive Early Warning Engine')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Executive Early Warning Engine
        <small>Enterprise Governance Intelligence & Predictive Risk Monitoring</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       HEADER PANEL
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="enterprise-panel-title">

                    <i class="fa fa-warning"></i>

                    Executive Early Warning Monitoring

                </div>

                <p style="margin-top:-10px; color:#7f8c8d;">

                    Centralized governance intelligence engine for
                    overdue exposure,
                    liquidity pressure,
                    abnormal portfolio behavior,
                    and enterprise operational risk monitoring.

                </p>

            </div>

            <div class="col-md-4 text-right">

                <br>

                <a href="{{ route('finance.executive_early_warnings.generate') }}"
                   class="btn btn-danger">

                    <i class="fa fa-refresh"></i>

                    Generate Warnings

                </a>

            </div>

        </div>

    </div>

    {{-- =========================================================
       KPI SUMMARY ROW
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Open Warnings
                </div>

                <div class="value">
                    {{ number_format($warnings->where('status', 'open')->count()) }}
                </div>

                <div class="subtext">
                    Active governance warnings
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

                <div class="title">
                    Critical Alerts
                </div>

                <div class="value">
                    {{ number_format($warnings->where('severity', 'critical')->count()) }}
                </div>

                <div class="subtext">
                    Severe governance exposures
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Loan Warnings
                </div>

                <div class="value">
                    {{ number_format($warnings->where('module', 'Loan')->count()) }}
                </div>

                <div class="subtext">
                    Loan governance risk alerts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-check-circle"></i>
                </div>

                <div class="title">
                    Resolved Warnings
                </div>

                <div class="value">
                    {{ number_format($warnings->where('status', 'resolved')->count()) }}
                </div>

                <div class="subtext">
                    Closed governance incidents
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

                    Warning Severity Distribution

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="severityDistributionChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">

                    <i class="fa fa-bar-chart"></i>

                    Module Risk Exposure

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="moduleExposureChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       WARNING REGISTER
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-table"></i>

            Executive Warning Register

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th>Warning No</th>
                        <th>Module</th>
                        <th>Severity</th>
                        <th>Subject</th>
                        <th>Branch</th>

                        <th class="text-right">
                            Amount
                        </th>

                        <th>Status</th>

                        <th>Date</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($warnings as $warning)

                        <tr>

                            <td>

                                <strong>

                                    {{ $warning->warning_no }}

                                </strong>

                            </td>

                            <td>

                                {{ $warning->module }}

                            </td>

                            <td>

                                @if($warning->severity == 'critical')

                                    <span class="label label-danger">
                                        Critical
                                    </span>

                                @elseif($warning->severity == 'high')

                                    <span class="label label-warning">
                                        High
                                    </span>

                                @elseif($warning->severity == 'medium')

                                    <span class="label label-info">
                                        Medium
                                    </span>

                                @else

                                    <span class="label label-success">
                                        Low
                                    </span>

                                @endif

                            </td>

                            <td>

                                {{ $warning->subject }}

                            </td>

                            <td>

                                {{ optional($warning->location)->name }}

                            </td>

                            <td class="text-right">

                                {{ number_format($warning->amount ?? 0, 2) }}

                            </td>

                            <td>

                                <strong>

                                    {{ ucfirst($warning->status) }}

                                </strong>

                            </td>

                            <td>

                                {{ optional($warning->created_at)->format('Y-m-d') }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">

            {{ $warnings->links() }}

        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | Severity Distribution
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('severityDistributionChart'), {

        type: 'doughnut',

        data: {

            labels: [

                'Critical',
                'High',
                'Medium',
                'Low'

            ],

            datasets: [{

                data: [

                    {{ $warnings->where('severity', 'critical')->count() }},
                    {{ $warnings->where('severity', 'high')->count() }},
                    {{ $warnings->where('severity', 'medium')->count() }},
                    {{ $warnings->where('severity', 'low')->count() }}

                ],

                backgroundColor: [

                    '#e74c3c',
                    '#f39c12',
                    '#3498db',
                    '#27ae60'

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
    | Module Exposure
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('moduleExposureChart'), {

        type: 'bar',

        data: {

            labels: [

                'Loan',
                'Finance',
                'Treasury'

            ],

            datasets: [{

                label: 'Warning Count',

                data: [

                    {{ $warnings->where('module', 'Loan')->count() }},
                    {{ $warnings->where('module', 'Finance')->count() }},
                    {{ $warnings->where('module', 'Treasury')->count() }}

                ],

                backgroundColor: [

                    '#3498db',
                    '#8e44ad',
                    '#27ae60'

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