@extends('layouts.app')

@section('title', 'Cash Flow Analytics')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')

<section class="content-header">

    <h1>
        Cash Flow Analytics
        <small>Enterprise Treasury Intelligence & Forecasting</small>
    </h1>

</section>

<section class="content">

    {{-- =========================================================
       FILTER PANEL
    ========================================================== --}}

    <div class="enterprise-panel">

        <div class="enterprise-panel-title">

            <i class="fa fa-filter"></i>

            Analytics Filters

        </div>

        <form method="GET">

            <div class="row">

                <div class="col-md-4">

                    <div class="form-group">

                        <label>From Date</label>

                        <input type="date"
                               name="from_date"
                               class="form-control"
                               value="{{ $from_date }}">

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="form-group">

                        <label>To Date</label>

                        <input type="date"
                               name="to_date"
                               class="form-control"
                               value="{{ $to_date }}">

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="form-group">

                        <label>&nbsp;</label>

                        <button type="submit"
                                class="btn btn-primary btn-block">

                            <i class="fa fa-search"></i>

                            Generate Analytics

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

    {{-- =========================================================
       EXECUTIVE KPI ROW
    ========================================================== --}}

    <div class="row">

        <div class="col-md-3">

            <div class="enterprise-card enterprise-green">

                <div class="icon text-success">
                    <i class="fa fa-arrow-down"></i>
                </div>

                <div class="title">
                    Projected Inflows
                </div>

                <div class="value">
                    {{ number_format($projected_inflows, 2) }}
                </div>

                <div class="subtext">
                    Forecasted treasury inflows
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-red">

                <div class="icon text-danger">
                    <i class="fa fa-arrow-up"></i>
                </div>

                <div class="title">
                    Projected Outflows
                </div>

                <div class="value">
                    {{ number_format($projected_outflows, 2) }}
                </div>

                <div class="subtext">
                    Forecasted treasury outflows
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-blue">

                <div class="icon text-primary">
                    <i class="fa fa-balance-scale"></i>
                </div>

                <div class="title">
                    Projected Net
                </div>

                <div class="value">
                    {{ number_format($projected_net_position, 2) }}
                </div>

                <div class="subtext">
                    Forecasted net treasury position
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="enterprise-card enterprise-purple">

                <div class="icon" style="color:#8e44ad;">
                    <i class="fa fa-line-chart"></i>
                </div>

                <div class="title">
                    Actual Net
                </div>

                <div class="value">
                    {{ number_format($actual_net_position, 2) }}
                </div>

                <div class="subtext">
                    Actual treasury position
                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
       CASH MOVEMENT + FORECAST SUMMARY
    ========================================================== --}}

    <div class="row">

        <div class="col-md-6">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">

                    <i class="fa fa-exchange"></i>

                    Actual Cash Movement

                </div>

                <table class="table table-bordered enterprise-table">

                    <tr>

                        <th>Actual Cash In</th>

                        <td class="text-right">

                            {{ number_format($actual_cash_in, 2) }}

                        </td>

                    </tr>

                    <tr>

                        <th>Actual Cash Out</th>

                        <td class="text-right">

                            {{ number_format($actual_cash_out, 2) }}

                        </td>

                    </tr>

                    <tr>

                        <th>Actual Net Position</th>

                        <td class="text-right">

                            <span class="enterprise-metric">

                                {{ number_format($actual_net_position, 2) }}

                            </span>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

        <div class="col-md-6">

            <div class="enterprise-panel">

                <div class="enterprise-panel-title">

                    <i class="fa fa-area-chart"></i>

                    Forecast Summary

                </div>

                <table class="table table-bordered enterprise-table">

                    <thead>

                        <tr>

                            <th>
                                Forecast Type
                            </th>

                            <th class="text-right">
                                Amount
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($forecast_summary as $summary)

                            <tr>

                                <td>

                                    {{ ucfirst($summary->forecast_type) }}

                                </td>

                                <td class="text-right">

                                    <strong>

                                        {{ number_format($summary->total_amount, 2) }}

                                    </strong>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>
    
    <div class="row">

    <div class="col-md-6">

        <div class="enterprise-chart-panel">

            <div class="enterprise-chart-title">

                <i class="fa fa-line-chart"></i>

                Treasury Net Position

            </div>

            <div class="enterprise-chart-box">

                <canvas id="treasuryNetChart"></canvas>

            </div>

        </div>

    </div>

    <div class="col-md-6">

        <div class="enterprise-chart-panel">

            <div class="enterprise-chart-title">

                <i class="fa fa-bar-chart"></i>

                Forecast vs Actual

            </div>

            <div class="enterprise-chart-box">

                <canvas id="forecastActualChart"></canvas>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    /*
    |--------------------------------------------------------------------------
    | Treasury Net Position Chart
    |--------------------------------------------------------------------------
    */

    const treasuryCtx =
        document.getElementById('treasuryNetChart');

    new Chart(treasuryCtx, {

        type: 'doughnut',

        data: {

            labels: [

                'Cash In',
                'Cash Out',
                'Net Position'

            ],

            datasets: [{

                data: [

                    {{ $treasury_cash_in ?? 0 }},
                    {{ $treasury_cash_out ?? 0 }},
                    {{ $projected_net_position ?? 0 }}

                ],

                backgroundColor: [

                    '#27ae60',
                    '#e74c3c',
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
    | Forecast vs Actual Chart
    |--------------------------------------------------------------------------
    */

    const forecastCtx =
        document.getElementById('forecastActualChart');

    new Chart(forecastCtx, {

        type: 'bar',

        data: {

            labels: [

                'Projected',
                'Actual'

            ],

            datasets: [{

                label: 'Treasury Position',

                data: [

                    {{ $projected_net_position ?? 0 }},
                    {{ $actual_net_position ?? 0 }}

                ],

                backgroundColor: [

                    '#3c8dbc',
                    '#8e44ad'

                ]

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