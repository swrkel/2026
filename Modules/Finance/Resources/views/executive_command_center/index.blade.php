@extends('layouts.app')

@section('title', 'Executive Command Center')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Executive Command Center
        <small>CEO / CFO Enterprise Intelligence Cockpit</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       EXECUTIVE HEADER
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-dashboard"></i>

            Enterprise Governance Intelligence Center

        </div>

        <p style="margin-top:-10px; color:#7f8c8d;">

            Consolidated executive intelligence across treasury,
            portfolio risk,
            governance monitoring,
            recovery operations,
            productivity analytics,
            and enterprise financial performance.

        </p>

    </div>

    {{-- =========================================================
       EXECUTIVE KPI ROW
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Treasury Net Position
                </div>

                <div class="value">

                    {{ number_format($treasury_net_position, 2) }}

                </div>

                <div class="subtext">
                    Enterprise treasury liquidity
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Open Warnings
                </div>

                <div class="value">

                    {{ number_format($open_warnings) }}

                </div>

                <div class="subtext">
                    Governance & operational alerts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-line-chart"></i>
                </div>

                <div class="title">
                    Portfolio Exposure
                </div>

                <div class="value">

                    {{ number_format($portfolio_exposure, 2) }}

                </div>

                <div class="subtext">
                    Total portfolio principal exposure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-check-circle"></i>
                </div>

                <div class="title">
                    Recovery Workflows
                </div>

                <div class="value">

                    {{ number_format($open_collection_workflows) }}

                </div>

                <div class="subtext">
                    Active recovery workflow cases
                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       EXECUTIVE ANALYTICS CHARTS
    ========================================================== --}}

    <div class="row">

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">

                    <i class="fa fa-pie-chart"></i>

                    Enterprise Risk Distribution

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="enterpriseRiskChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">

                    <i class="fa fa-bar-chart"></i>

                    Treasury vs Portfolio Exposure

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="treasuryPortfolioChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       EXECUTIVE ALERT SUMMARY
    ========================================================== --}}

    <div class="row">

        <div class="col-md-4">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">

                    <i class="fa fa-warning"></i>

                    Governance Alerts

                </div>

                <table class="table table-bordered enterprise-table">

                    <tr>
                        <th>Open Warnings</th>
                        <td class="text-right">
                            {{ number_format($open_warnings) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Critical Warnings</th>
                        <td class="text-right">
                            {{ number_format($critical_warnings) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Liquidity Risks</th>
                        <td class="text-right">
                            {{ number_format($liquidity_risks) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Critical Recovery Priorities</th>
                        <td class="text-right">
                            {{ number_format($critical_recovery_priorities) }}
                        </td>
                    </tr>

                </table>

            </div>

        </div>

        <div class="col-md-8">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">

                    <i class="fa fa-users"></i>

                    Top Recovery Officers

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered table-hover enterprise-table">

                        <thead>

                            <tr>

                                <th>Rank</th>
                                <th>Officer</th>

                                <th class="text-right">
                                    Productivity Score
                                </th>

                                <th class="text-right">
                                    Recovery Efficiency %
                                </th>

                                <th class="text-right">
                                    PTP Success %
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($top_officers as $officer)

                                <tr>

                                    <td>

                                        <span class="label label-primary">

                                            {{ $officer->ranking_position }}

                                        </span>

                                    </td>

                                    <td>

                                        {{ optional($officer->officer)->first_name }}
                                        {{ optional($officer->officer)->last_name }}

                                    </td>

                                    <td class="text-right">

                                        <strong>

                                            {{ number_format($officer->productivity_score, 2) }}

                                        </strong>

                                    </td>

                                    <td class="text-right">

                                        {{ number_format($officer->recovery_efficiency_percent, 2) }}%

                                    </td>

                                    <td class="text-right">

                                        {{ number_format($officer->ptp_success_percent, 2) }}%

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | Enterprise Risk Distribution
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('enterpriseRiskChart'), {

        type: 'doughnut',

        data: {

            labels: [

                'Open Warnings',
                'Liquidity Risks',
                'Critical Priorities',
                'Overdue Exposure'

            ],

            datasets: [{

                data: [

                    {{ $open_warnings }},
                    {{ $liquidity_risks }},
                    {{ $critical_recovery_priorities }},
                    {{ $overdue_exposure }}

                ],

                backgroundColor: [

                    '#e74c3c',
                    '#f39c12',
                    '#8e44ad',
                    '#3498db'

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
    | Treasury vs Portfolio
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('treasuryPortfolioChart'), {

        type: 'bar',

        data: {

            labels: [

                'Treasury Net',
                'Portfolio Exposure',
                'Overdue Exposure'

            ],

            datasets: [{

                label: 'Enterprise Financial Position',

                data: [

                    {{ $treasury_net_position }},
                    {{ $portfolio_exposure }},
                    {{ $overdue_exposure }}

                ],

                backgroundColor: [

                    '#27ae60',
                    '#3498db',
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