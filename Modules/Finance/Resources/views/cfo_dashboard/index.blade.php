@extends('layouts.app')

@section('title', 'CFO Executive Dashboard')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        CFO Executive Dashboard
        <small>Enterprise Financial Command Center</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       EXECUTIVE KPI ROW 1
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-line-chart"></i>
                </div>

                <div class="title">
                    Net Profit
                </div>

                <div class="value">
                    {{ number_format(optional($latest_kpi)->net_profit ?? 0, 2) }}
                </div>

                <div class="subtext">
                    Enterprise profitability position
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-money"></i>
                </div>

                <div class="title">
                    Treasury Net
                </div>

                <div class="value">
                    {{ number_format($treasury_net_position, 2) }}
                </div>

                <div class="subtext">
                    Treasury liquidity position
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-hourglass-half"></i>
                </div>

                <div class="title">
                    Receivables
                </div>

                <div class="value">
                    {{ number_format($receivables, 2) }}
                </div>

                <div class="subtext">
                    Outstanding receivables exposure
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-credit-card"></i>
                </div>

                <div class="title">
                    Payables
                </div>

                <div class="value">
                    {{ number_format($payables, 2) }}
                </div>

                <div class="subtext">
                    Supplier obligation exposure
                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       EXECUTIVE KPI ROW 2
    ========================================================== --}}

    <div class="row">

        <div class="col-md-4">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Open Risk Alerts
                </div>

                <div class="value">
                    {{ number_format($open_risk_alerts) }}
                </div>

                <div class="subtext">
                    Active enterprise governance alerts
                </div>

                <br>

                <a href="{{ route('finance.risk.index') }}"
                   class="btn btn-danger btn-xs">

                    View Alerts

                </a>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-purple">

                <div class="icon" style="color:#8e44ad;">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

                <div class="title">
                    Critical Risk Alerts
                </div>

                <div class="value">
                    {{ number_format($critical_risk_alerts) }}
                </div>

                <div class="subtext">
                    High severity governance incidents
                </div>

                <br>

                <a href="{{ route('finance.risk.index') }}"
                   class="btn btn-primary btn-xs">

                    View Critical Alerts

                </a>

            </div>

        </div>

        <div class="col-md-4">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-tint"></i>
                </div>

                <div class="title">
                    Liquidity Risks
                </div>

                <div class="value">
                    {{ number_format($open_liquidity_risks) }}
                </div>

                <div class="subtext">
                    Treasury liquidity risk exposure
                </div>

                <br>

                <a href="{{ route('finance.liquidity_risk.index') }}"
                   class="btn btn-warning btn-xs">

                    View Liquidity Risks

                </a>

            </div>

        </div>

    </div>

    {{-- =========================================================
       KPI SNAPSHOT + BRANCH SCORES
    ========================================================== --}}

    <div class="row">

        <div class="col-md-6">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">

                    <i class="fa fa-bar-chart"></i>

                    Latest KPI Snapshot

                </div>

                <table class="table table-bordered enterprise-table">

                    <tr>
                        <th>Total Income</th>
                        <td class="text-right">
                            {{ number_format(optional($latest_kpi)->total_income ?? 0, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Total Expenses</th>
                        <td class="text-right">
                            {{ number_format(optional($latest_kpi)->total_expenses ?? 0, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Gross Margin %</th>
                        <td class="text-right">
                            {{ number_format(optional($latest_kpi)->gross_margin_percent ?? 0, 2) }}%
                        </td>
                    </tr>

                    <tr>
                        <th>Expense Ratio %</th>
                        <td class="text-right">
                            {{ number_format(optional($latest_kpi)->expense_ratio_percent ?? 0, 2) }}%
                        </td>
                    </tr>

                    <tr>
                        <th>Collection Efficiency %</th>
                        <td class="text-right">
                            {{ number_format(optional($latest_kpi)->collection_efficiency_percent ?? 0, 2) }}%
                        </td>
                    </tr>

                    <tr>
                        <th>Liquidity Score</th>
                        <td class="text-right">
                            {{ number_format(optional($latest_kpi)->liquidity_score ?? 0, 2) }}
                        </td>
                    </tr>

                </table>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">

                    <i class="fa fa-bank"></i>

                    Top Branch Financial Scores

                </div>

                <table class="table table-bordered enterprise-table">

                    <thead>

                        <tr>
                            <th>Rank</th>
                            <th>Branch</th>
                            <th class="text-right">Score</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($top_branches as $branch)

                            <tr>

                                <td>
                                    {{ $branch->ranking_position }}
                                </td>

                                <td>
                                    {{ optional($branch->location)->name }}
                                </td>

                                <td class="text-right">

                                    <strong>

                                        {{ number_format($branch->overall_score, 2) }}

                                    </strong>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

                <a href="{{ route('finance.branch_scores.index') }}"
                   class="btn btn-primary btn-sm">

                    View Full Branch Scores

                </a>

            </div>

        </div>

    </div>

    {{-- =========================================================
       TREASURY POSITION
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-balance-scale"></i>

            Treasury Position Summary

        </div>

        <table class="table table-bordered enterprise-table">

            <tr>

                <th>Treasury Cash In</th>

                <td class="text-right">

                    {{ number_format($treasury_cash_in, 2) }}

                </td>

            </tr>

            <tr>

                <th>Treasury Cash Out</th>

                <td class="text-right">

                    {{ number_format($treasury_cash_out, 2) }}

                </td>

            </tr>

            <tr>

                <th>Net Treasury Position</th>

                <td class="text-right">

                    <strong>

                        {{ number_format($treasury_net_position, 2) }}

                    </strong>

                </td>

            </tr>

        </table>

    </div>

<div class="row">

    <div class="col-md-6">

        <div class="enterprise-chart-panel">

            <div class="enterprise-chart-title">
                <i class="fa fa-pie-chart"></i>
                Financial Exposure Mix
            </div>

            <div class="enterprise-chart-box">
                <canvas id="financialExposureChart"></canvas>
            </div>

        </div>

    </div>

    <div class="col-md-6">

        <div class="enterprise-chart-panel">

            <div class="enterprise-chart-title">
                <i class="fa fa-bar-chart"></i>
                Treasury Position
            </div>

            <div class="enterprise-chart-box">
                <canvas id="treasuryPositionChart"></canvas>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    new Chart(document.getElementById('financialExposureChart'), {
        type: 'doughnut',
        data: {
            labels: ['Receivables', 'Payables', 'Treasury Net'],
            datasets: [{
                data: [
                    {{ $receivables ?? 0 }},
                    {{ $payables ?? 0 }},
                    {{ $treasury_net_position ?? 0 }}
                ],
                backgroundColor: ['#f39c12', '#e74c3c', '#3498db']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('treasuryPositionChart'), {
        type: 'bar',
        data: {
            labels: ['Cash In', 'Cash Out', 'Net Position'],
            datasets: [{
                label: 'Treasury',
                data: [
                    {{ $treasury_cash_in ?? 0 }},
                    {{ $treasury_cash_out ?? 0 }},
                    {{ $treasury_net_position ?? 0 }}
                ],
                backgroundColor: ['#27ae60', '#e74c3c', '#3c8dbc']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>

</section>

@endsection