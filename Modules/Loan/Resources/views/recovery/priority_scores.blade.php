@extends('layouts.app')

@section('title', 'Smart Recovery Prioritization')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')
@include('layouts.partials.loan-operational-dashboard-style')

<section class="content-header">

    <h1>
        Smart Recovery Prioritization

        <small>
            AI-ready Recovery Intelligence & Collection Prioritization
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

                    Recovery Priority Intelligence Engine

                </div>

                <div class="loan-op-subtitle">

                    Intelligent recovery prioritization framework for overdue exposure,
                    portfolio risk ranking, escalation analysis and collection optimization.

                </div>

            </div>

            <div class="col-md-4 text-right">

                <a href="{{ route('loan.recovery.priorities.generate') }}"
                   class="loan-op-action-btn">

                    <i class="fa fa-refresh"></i>

                    Generate Priority Scores

                </a>

            </div>

        </div>

    </div>

    {{-- KPI CARDS --}}
    <div class="row">

        <div class="col-md-3">

            <div class="loan-op-card loan-op-red">

                <div class="icon text-danger">
                    <i class="fa fa-warning"></i>
                </div>

                <div class="title">
                    Critical Priorities
                </div>

                <div class="value">
                    {{ number_format($scores->where('priority_level', 'critical')->count()) }}
                </div>

                <div class="subtext">
                    Immediate escalation accounts
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

                <div class="title">
                    High Priorities
                </div>

                <div class="value">
                    {{ number_format($scores->where('priority_level', 'high')->count()) }}
                </div>

                <div class="subtext">
                    Urgent collection attention
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-blue">

                <div class="icon text-primary">
                    <i class="fa fa-bank"></i>
                </div>

                <div class="title">
                    Avg Risk Score
                </div>

                <div class="value">
                    {{ number_format($scores->avg('risk_score'), 2) }}
                </div>

                <div class="subtext">
                    Portfolio recovery risk average
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-green">

                <div class="icon text-success">
                    <i class="fa fa-money"></i>
                </div>

                <div class="title">
                    Total Overdue Exposure
                </div>

                <div class="value">
                    {{ number_format($scores->sum('overdue_amount'), 2) }}
                </div>

                <div class="subtext">
                    Consolidated overdue pressure
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

                    Recovery Priority Distribution

                </div>

                <div class="loan-op-chart-box">

                    <canvas id="priorityDistributionChart"></canvas>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-bar-chart"></i>

                    Overdue vs Outstanding Exposure

                </div>

                <div class="loan-op-chart-box">

                    <canvas id="exposureComparisonChart"></canvas>

                </div>

            </div>

        </div>

    </div>

    {{-- PRIORITY TABLE --}}
    <div class="loan-op-section">

        <div class="loan-op-section-title">

            <i class="fa fa-table"></i>

            Recovery Priority Register

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>

                        <th>Loan</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Priority</th>
                        <th class="text-right">Risk Score</th>
                        <th class="text-right">Priority Score</th>
                        <th class="text-right">Overdue</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Overdue Days</th>
                        <th>Recommended Action</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($scores as $score)

                        <tr>

                            <td>
                                {{ optional($score->loan)->id }}
                            </td>

                            <td>
                                {{ optional($score->customer)->name }}
                            </td>

                            <td>
                                {{ optional($score->location)->name }}
                            </td>

                            <td>

                                @if($score->priority_level == 'critical')

                                    <span class="label label-danger">
                                        Critical
                                    </span>

                                @elseif($score->priority_level == 'high')

                                    <span class="label label-warning">
                                        High
                                    </span>

                                @elseif($score->priority_level == 'medium')

                                    <span class="label label-info">
                                        Medium
                                    </span>

                                @else

                                    <span class="label label-success">
                                        Low
                                    </span>

                                @endif

                            </td>

                            <td class="text-right">
                                {{ number_format($score->risk_score, 2) }}
                            </td>

                            <td class="text-right">

                                <strong>
                                    {{ number_format($score->recovery_priority_score, 2) }}
                                </strong>

                            </td>

                            <td class="text-right">
                                {{ number_format($score->overdue_amount, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($score->outstanding_amount, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($score->overdue_days) }}
                            </td>

                            <td>
                                {{ $score->recommended_action }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">

            {{ $scores->links() }}

        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

new Chart(document.getElementById('priorityDistributionChart'), {

    type: 'doughnut',

    data: {

        labels: ['Critical', 'High', 'Medium', 'Low'],

        datasets: [{

            data: [

                {{ $scores->where('priority_level', 'critical')->count() }},
                {{ $scores->where('priority_level', 'high')->count() }},
                {{ $scores->where('priority_level', 'medium')->count() }},
                {{ $scores->where('priority_level', 'low')->count() }}

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

new Chart(document.getElementById('exposureComparisonChart'), {

    type: 'bar',

    data: {

        labels: [

            'Overdue Exposure',
            'Outstanding Exposure'

        ],

        datasets: [{

            label: 'Portfolio Exposure',

            data: [

                {{ $scores->sum('overdue_amount') }},
                {{ $scores->sum('outstanding_amount') }}

            ],

            backgroundColor: [

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

</script>

@endsection