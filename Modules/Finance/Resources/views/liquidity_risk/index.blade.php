@extends('layouts.app')

@section('title', 'Treasury Liquidity Risks')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Treasury Liquidity Risks
        <small>Enterprise Treasury Risk Intelligence & Monitoring</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       EXECUTIVE HEADER PANEL
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="enterprise-panel-title">

                    <i class="fa fa-shield"></i>

                    Liquidity Risk Monitoring

                </div>

                <p style="margin-top:-10px; color:#7f8c8d;">

                    Monitor treasury liquidity exposure,
                    projected balances,
                    threshold breaches,
                    and enterprise risk pressure across branches.

                </p>

            </div>

            <div class="col-md-4 text-right">

                <br>

                <a href="{{ route('finance.liquidity_risk.run') }}"
                   class="btn btn-danger">

                    <i class="fa fa-play"></i>

                    Run Monitoring

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
                    Total Risk Alerts
                </div>

                <div class="value">
                    {{ number_format($risks->total()) }}
                </div>

                <div class="subtext">
                    Active treasury liquidity risks
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

                <div class="title">
                    Critical Risks
                </div>

                <div class="value">

                    {{ number_format($risks->where('risk_level', 'critical')->count()) }}

                </div>

                <div class="subtext">
                    High severity liquidity alerts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Avg Current Balance
                </div>

                <div class="value">

                    {{ number_format($risks->avg('current_balance'), 2) }}

                </div>

                <div class="subtext">
                    Average treasury balance
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-line-chart"></i>
                </div>

                <div class="title">
                    Avg Projected Balance
                </div>

                <div class="value">

                    {{ number_format($risks->avg('projected_balance'), 2) }}

                </div>

                <div class="subtext">
                    Forecasted treasury position
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

                    Liquidity Risk Distribution

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="liquidityRiskChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">

                    <i class="fa fa-bar-chart"></i>

                    Balance vs Threshold

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="balanceThresholdChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       LIQUIDITY RISK TABLE
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-table"></i>

            Treasury Liquidity Risk Register

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th>Risk No</th>
                        <th>Branch</th>
                        <th>Risk Level</th>
                        <th>Subject</th>

                        <th class="text-right">
                            Current Balance
                        </th>

                        <th class="text-right">
                            Projected Balance
                        </th>

                        <th class="text-right">
                            Threshold
                        </th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($risks as $risk)

                        <tr>

                            <td>

                                <strong>

                                    {{ $risk->risk_no }}

                                </strong>

                            </td>

                            <td>

                                {{ optional($risk->location)->name }}

                            </td>

                            <td>

                                @if($risk->risk_level == 'critical')

                                    <span class="label label-danger">
                                        Critical
                                    </span>

                                @elseif($risk->risk_level == 'high')

                                    <span class="label label-warning">
                                        High
                                    </span>

                                @else

                                    <span class="label label-info">
                                        Medium
                                    </span>

                                @endif

                            </td>

                            <td>

                                {{ $risk->subject }}

                            </td>

                            <td class="text-right">

                                {{ number_format($risk->current_balance, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($risk->projected_balance, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($risk->threshold_amount, 2) }}

                            </td>

                            <td>

                                <strong>

                                    {{ ucfirst($risk->status) }}

                                </strong>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">

            {{ $risks->links() }}

        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | Liquidity Risk Distribution
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('liquidityRiskChart'), {

        type: 'doughnut',

        data: {

            labels: [

                'Critical',
                'High',
                'Medium'

            ],

            datasets: [{

                data: [

                    {{ $risks->where('risk_level', 'critical')->count() }},
                    {{ $risks->where('risk_level', 'high')->count() }},
                    {{ $risks->where('risk_level', 'medium')->count() }}

                ],

                backgroundColor: [

                    '#e74c3c',
                    '#f39c12',
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
    | Balance vs Threshold
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('balanceThresholdChart'), {

        type: 'bar',

        data: {

            labels: [

                'Current Balance',
                'Projected Balance',
                'Threshold'

            ],

            datasets: [{

                label: 'Treasury Monitoring',

                data: [

                    {{ $risks->avg('current_balance') ?? 0 }},
                    {{ $risks->avg('projected_balance') ?? 0 }},
                    {{ $risks->avg('threshold_amount') ?? 0 }}

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