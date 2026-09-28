@extends('layouts.app')

@section('title', 'Finance KPI Dashboard')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Finance KPI Dashboard
        <small>Executive KPI Intelligence & Financial Analytics</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       KPI CONTROL PANEL
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-sliders"></i>

            KPI Snapshot Controls

        </div>

        <form method="GET"
              action="{{ route('finance.kpi.generate') }}">

            <div class="row">

                <div class="col-md-4">

                    <div class="form-group">

                        <label>Branch</label>

                        <select name="location_id"
                                class="form-control">

                            <option value="all">
                                Consolidated / All Branches
                            </option>

                            @foreach($locations as $id => $name)

                                <option value="{{ $id }}">
                                    {{ $name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="form-group">

                        <label>&nbsp;</label>

                        <button type="submit"
                                class="btn btn-primary btn-block">

                            <i class="fa fa-refresh"></i>

                            Generate KPI Snapshot

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

    {{-- =========================================================
       KPI SUMMARY CARDS
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-line-chart"></i>
                </div>

                <div class="title">
                    Latest Net Profit
                </div>

                <div class="value">

                    {{ number_format(optional($snapshots->first())->net_profit ?? 0, 2) }}

                </div>

                <div class="subtext">
                    Latest profitability snapshot
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Treasury Position
                </div>

                <div class="value">

                    {{ number_format(optional($snapshots->first())->treasury_net_position ?? 0, 2) }}

                </div>

                <div class="subtext">
                    Latest treasury net position
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-percent"></i>
                </div>

                <div class="title">
                    Gross Margin %
                </div>

                <div class="value">

                    {{ number_format(optional($snapshots->first())->gross_margin_percent ?? 0, 2) }}%

                </div>

                <div class="subtext">
                    Latest margin performance
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Expense Ratio %
                </div>

                <div class="value">

                    {{ number_format(optional($snapshots->first())->expense_ratio_percent ?? 0, 2) }}%

                </div>

                <div class="subtext">
                    Cost management efficiency
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

                    <i class="fa fa-area-chart"></i>

                    Income vs Expenses

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="incomeExpenseChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-chart-panel">

                <div class="enterprise-chart-title">

                    <i class="fa fa-bar-chart"></i>

                    Liquidity vs Collection Efficiency

                </div>

                <div class="enterprise-chart-box">

                    <canvas id="liquidityEfficiencyChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       KPI SNAPSHOT REGISTER
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-table"></i>

            KPI Snapshot Register

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th>Date</th>
                        <th>Branch</th>

                        <th class="text-right">
                            Income
                        </th>

                        <th class="text-right">
                            Expenses
                        </th>

                        <th class="text-right">
                            Net Profit
                        </th>

                        <th class="text-right">
                            Receivables
                        </th>

                        <th class="text-right">
                            Payables
                        </th>

                        <th class="text-right">
                            Treasury Net
                        </th>

                        <th class="text-right">
                            Gross Margin %
                        </th>

                        <th class="text-right">
                            Expense Ratio %
                        </th>

                        <th class="text-right">
                            Collection Efficiency %
                        </th>

                        <th class="text-right">
                            Liquidity Score
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($snapshots as $snapshot)

                        <tr>

                            <td>
                                {{ $snapshot->snapshot_date }}
                            </td>

                            <td>

                                <strong>

                                    {{ optional($snapshot->location)->name ?? 'Consolidated' }}

                                </strong>

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->total_income, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->total_expenses, 2) }}

                            </td>

                            <td class="text-right">

                                <strong>

                                    {{ number_format($snapshot->net_profit, 2) }}

                                </strong>

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->receivables, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->payables, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->treasury_net_position, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->gross_margin_percent, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->expense_ratio_percent, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->collection_efficiency_percent, 2) }}

                            </td>

                            <td class="text-right">

                                {{ number_format($snapshot->liquidity_score, 2) }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">

            {{ $snapshots->links() }}

        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | Income vs Expenses
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('incomeExpenseChart'), {

        type: 'line',

        data: {

            labels: [

                @foreach($snapshots->take(6) as $snapshot)

                    '{{ $snapshot->snapshot_date }}',

                @endforeach

            ],

            datasets: [

                {

                    label: 'Income',

                    data: [

                        @foreach($snapshots->take(6) as $snapshot)

                            {{ $snapshot->total_income }},

                        @endforeach

                    ],

                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39,174,96,0.08)',
                    fill: true

                },

                {

                    label: 'Expenses',

                    data: [

                        @foreach($snapshots->take(6) as $snapshot)

                            {{ $snapshot->total_expenses }},

                        @endforeach

                    ],

                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231,76,60,0.08)',
                    fill: true

                }

            ]

        },

        options: {

            responsive: true,
            maintainAspectRatio: false

        }

    });

    /*
    |--------------------------------------------------------------------------
    | Liquidity vs Collection Efficiency
    |--------------------------------------------------------------------------
    */

    new Chart(document.getElementById('liquidityEfficiencyChart'), {

        type: 'bar',

        data: {

            labels: [

                @foreach($snapshots->take(6) as $snapshot)

                    '{{ $snapshot->snapshot_date }}',

                @endforeach

            ],

            datasets: [

                {

                    label: 'Liquidity Score',

                    data: [

                        @foreach($snapshots->take(6) as $snapshot)

                            {{ $snapshot->liquidity_score }},

                        @endforeach

                    ],

                    backgroundColor: '#3498db'

                },

                {

                    label: 'Collection Efficiency %',

                    data: [

                        @foreach($snapshots->take(6) as $snapshot)

                            {{ $snapshot->collection_efficiency_percent }},

                        @endforeach

                    ],

                    backgroundColor: '#8e44ad'

                }

            ]

        },

        options: {

            responsive: true,
            maintainAspectRatio: false

        }

    });

</script>

@endsection