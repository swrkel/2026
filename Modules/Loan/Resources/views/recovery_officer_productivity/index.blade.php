@extends('layouts.app')

@section('title', 'Recovery Officer Productivity')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')
@include('layouts.partials.loan-operational-dashboard-style')

<section class="content-header">

    <h1>
        Recovery Officer Productivity

        <small>
            Enterprise Recovery Team Performance Intelligence
        </small>
    </h1>

</section>

<section class="content">

    {{-- TOP CONTROL PANEL --}}
    <div class="loan-op-header-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="loan-op-title">

                    <i class="fa fa-users"></i>

                    Officer Productivity Control Center

                </div>

                <div class="loan-op-subtitle">

                    Monitor recovery officer productivity, collection efficiency,
                    promise-to-pay discipline, escalations and performance ranking.

                </div>

            </div>

            <div class="col-md-4 text-right">

                <a href="{{ route('loan.recovery.officer.productivity.generate') }}"
                   class="loan-op-action-btn">

                    <i class="fa fa-refresh"></i>

                    Generate Productivity

                </a>

            </div>

        </div>

    </div>

    {{-- KPI CARDS --}}
    <div class="row">

        <div class="col-md-3">

            <div class="loan-op-card loan-op-blue">

                <div class="icon text-primary">
                    <i class="fa fa-user"></i>
                </div>

                <div class="title">
                    Total Officers
                </div>

                <div class="value">
                    {{ number_format($records->total()) }}
                </div>

                <div class="subtext">
                    Ranked recovery officers
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-green">

                <div class="icon text-success">
                    <i class="fa fa-check-circle"></i>
                </div>

                <div class="title">
                    Resolved Cases
                </div>

                <div class="value">
                    {{ number_format($records->sum('resolved_cases')) }}
                </div>

                <div class="subtext">
                    Completed recovery cases
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-yellow">

                <div class="icon text-warning">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

                <div class="title">
                    Escalations
                </div>

                <div class="value">
                    {{ number_format($records->sum('escalation_count')) }}
                </div>

                <div class="subtext">
                    Escalated recovery cases
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="loan-op-card loan-op-red">

                <div class="icon text-danger">
                    <i class="fa fa-line-chart"></i>
                </div>

                <div class="title">
                    Avg Productivity Score
                </div>

                <div class="value">
                    {{ number_format($records->avg('productivity_score'), 2) }}
                </div>

                <div class="subtext">
                    Team productivity average
                </div>

            </div>

        </div>

    </div>

    {{-- CHARTS --}}
    <div class="row">

        <div class="col-md-6">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-bar-chart"></i>

                    Officer Productivity Ranking

                </div>

                <div class="loan-op-chart-box">
                    <canvas id="productivityRankingChart"></canvas>
                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="loan-op-section">

                <div class="loan-op-section-title">

                    <i class="fa fa-pie-chart"></i>

                    Case Resolution Mix

                </div>

                <div class="loan-op-chart-box">
                    <canvas id="caseResolutionChart"></canvas>
                </div>

            </div>

        </div>

    </div>

    {{-- TABLE --}}
    <div class="loan-op-section">

        <div class="loan-op-section-title">

            <i class="fa fa-table"></i>

            Recovery Officer Productivity Register

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>

                    <tr>
                        <th>Rank</th>
                        <th>Officer</th>
                        <th>Branch</th>
                        <th class="text-right">Assigned</th>
                        <th class="text-right">Resolved</th>
                        <th class="text-right">Overdue Amount</th>
                        <th class="text-right">Recovered Amount</th>
                        <th class="text-right">PTP Count</th>
                        <th class="text-right">PTP Kept</th>
                        <th class="text-right">Recovery Efficiency %</th>
                        <th class="text-right">PTP Success %</th>
                        <th class="text-right">Score</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($records as $record)

                        <tr>

                            <td>
                                <span class="label label-primary">
                                    {{ $record->ranking_position }}
                                </span>
                            </td>

                            <td>
                                <strong>
                                    {{ optional($record->officer)->first_name }}
                                    {{ optional($record->officer)->last_name }}
                                </strong>
                            </td>

                            <td>
                                {{ optional($record->location)->name }}
                            </td>

                            <td class="text-right">
                                {{ number_format($record->assigned_cases) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($record->resolved_cases) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($record->total_overdue_amount, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($record->recovered_amount, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($record->ptp_count) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($record->ptp_kept_count) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($record->recovery_efficiency_percent, 2) }}%
                            </td>

                            <td class="text-right">
                                {{ number_format($record->ptp_success_percent, 2) }}%
                            </td>

                            <td class="text-right">
                                <strong>
                                    {{ number_format($record->productivity_score, 2) }}
                                </strong>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="text-center" style="margin-top:20px;">
            {{ $records->links() }}
        </div>

    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

new Chart(document.getElementById('productivityRankingChart'), {

    type: 'bar',

    data: {

        labels: [

            @foreach($records->take(10) as $record)

                '{{ trim(optional($record->officer)->first_name . " " . optional($record->officer)->last_name) ?: "Officer" }}',

            @endforeach

        ],

        datasets: [{

            label: 'Productivity Score',

            data: [

                @foreach($records->take(10) as $record)

                    {{ $record->productivity_score }},

                @endforeach

            ],

            backgroundColor: '#3498db'

        }]
    },

    options: {

        responsive: true,

        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('caseResolutionChart'), {

    type: 'doughnut',

    data: {

        labels: [

            'Resolved Cases',
            'Assigned Cases',
            'Escalations'

        ],

        datasets: [{

            data: [

                {{ $records->sum('resolved_cases') }},
                {{ $records->sum('assigned_cases') }},
                {{ $records->sum('escalation_count') }}

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