@extends('layouts.app')

@section('title', 'Recovery Performance Dashboard')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')
@include('layouts.partials.loan-operational-dashboard-style')

<section class="content-header">

    <h1>
        Recovery Performance Dashboard

        <small>
            Enterprise Loan Recovery Intelligence
        </small>
    </h1>

</section>

<section class="content">

    {{-- HEADER PANEL --}}
    <div class="loan-op-header-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="loan-op-title">

                    <i class="fa fa-line-chart"></i>

                    Recovery Portfolio Performance Center

                </div>

                <div class="loan-op-subtitle">

                    Enterprise recovery exposure monitoring,
                    collection intelligence, PAR analytics and
                    portfolio governance performance tracking.

                </div>

            </div>

            <div class="col-md-4 text-right">

                <a href="{{ route('loan.recovery.dashboard') }}"
                   class="loan-op-action-btn">

                    <i class="fa fa-dashboard"></i>

                    View Recovery Dashboard

                </a>

            </div>

        </div>

    </div>

    {{-- KPI CARDS --}}
    <div class="row">

        <div class="col-md-3">

            <div class="loan-op-card loan-op-blue">

                <div class="icon text-primary">
                    <i class="fa fa-briefcase"></i>
                </div>

                <div class="title">
                    Total Loans
                </div>

                <div class="value">
                    {{ number_format($total_loans) }}
                </div>

                <div class="subtext">
                    Total loan accounts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-green">

                <div class="icon text-success">
                    <i class="fa fa-check-circle"></i>
                </div>

                <div class="title">
                    Active Loans
                </div>

                <div class="value">
                    {{ number_format($active_loans) }}
                </div>

                <div class="subtext">
                    Performing accounts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Overdue Loans
                </div>

                <div class="value">
                    {{ number_format($overdue_loans) }}
                </div>

                <div class="subtext">
                    Recovery required
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-ban"></i>
                </div>

                <div class="title">
                    Written Off Loans
                </div>

                <div class="value">
                    {{ number_format($written_off_loans) }}
                </div>

                <div class="subtext">
                    Written-off portfolio
                </div>

            </div>

        </div>

    </div>

    {{-- PERFORMANCE SUMMARY --}}
    <div class="row">

        <div class="col-md-8">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-line-chart"></i>

                    Recovery Portfolio Summary

                </div>

                <table class="table table-bordered enterprise-table">

                    <tr>
                        <th width="60%">
                            Portfolio Value
                        </th>

                        <td class="text-right text-primary">
                            {{ number_format($portfolio_value, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>
                            Outstanding Exposure
                        </th>

                        <td class="text-right text-warning">
                            {{ number_format($outstanding_value, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>
                            Overdue Exposure
                        </th>

                        <td class="text-right text-danger">
                            {{ number_format($overdue_value, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th>
                            Total Collections
                        </th>

                        <td class="text-right text-success">
                            {{ number_format($total_collections, 2) }}
                        </td>
                    </tr>

                </table>

            </div>

        </div>

        <div class="col-md-4">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-warning"></i>

                    Recovery Risk Indicators

                </div>

                <div style="margin-bottom:30px;">

                    <div class="title">
                        PAR / Portfolio At Risk
                    </div>

                    <div class="value text-danger">
                        {{ number_format($par_ratio, 2) }}%
                    </div>

                </div>

                <hr>

                <div style="margin-bottom:30px;">

                    <div class="title">
                        Collection Efficiency
                    </div>

                    <div class="value text-success">
                        {{ number_format($collection_efficiency, 2) }}%
                    </div>

                </div>

                <hr>

                <div class="text-center">

                    <div class="value text-warning">
                        {{ number_format($open_escalations) }}
                    </div>

                    <div class="title">
                        Open Escalations
                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- CHARTS --}}
    <div class="row">

        <div class="col-md-6">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-pie-chart"></i>

                    Recovery Exposure Mix

                </div>

                <div class="loan-op-chart-box">

                    <canvas id="recoveryExposureChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-bar-chart"></i>

                    PAR vs Collection Efficiency

                </div>

                <div class="loan-op-chart-box">

                    <canvas id="parEfficiencyChart"></canvas>

                </div>

            </div>

        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

new Chart(document.getElementById('recoveryExposureChart'), {

    type: 'doughnut',

    data: {

        labels: [

            'Portfolio Value',
            'Outstanding Exposure',
            'Overdue Exposure',
            'Collections'

        ],

        datasets: [{

            data: [

                {{ $portfolio_value ?? 0 }},
                {{ $outstanding_value ?? 0 }},
                {{ $overdue_value ?? 0 }},
                {{ $total_collections ?? 0 }}

            ],

            backgroundColor: [

                '#3498db',
                '#f39c12',
                '#e74c3c',
                '#27ae60'

            ]

        }]
    },

    options: {

        responsive: true,

        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('parEfficiencyChart'), {

    type: 'bar',

    data: {

        labels: [

            'PAR Ratio',
            'Collection Efficiency'

        ],

        datasets: [{

            label: 'Recovery Performance %',

            data: [

                {{ $par_ratio ?? 0 }},
                {{ $collection_efficiency ?? 0 }}

            ],

            backgroundColor: [

                '#e74c3c',
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