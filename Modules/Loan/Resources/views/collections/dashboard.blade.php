@extends('layouts.app')

@section('title', 'Collections Command Center')

@section('content')

<style>

    .dashboard-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        border-top: 4px solid #3c8dbc;
        min-height: 135px;
    }

    .dashboard-card h2 {
        font-size: 30px;
        font-weight: 700;
        margin: 0;
        color: #2c3e50;
    }

    .dashboard-card p {
        margin-top: 10px;
        color: #7f8c8d;
        font-size: 14px;
        font-weight: 600;
    }

    .dashboard-icon {
        float: right;
        font-size: 42px;
        opacity: 0.10;
        margin-top: -50px;
    }

    .governance-box {
        background: #ffffff;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    .governance-title {
        font-size: 22px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 20px;
    }

    .modern-table thead {
        background: #f4f6f9;
    }

    .modern-table th {
        border: none !important;
        text-transform: uppercase;
        font-size: 12px;
        color: #7f8c8d;
    }

    .modern-table td {
        vertical-align: middle !important;
        font-size: 14px;
    }

</style>

<section class="content-header">

    <h1>

        Enterprise Collections Command Center

        <small>
            Recovery Intelligence • Escalation Governance • Autonomous Monitoring
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- Executive KPI Cards -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ number_format($critical_urgency_cases) }}

                </h2>

                <p>
                    Critical Urgency Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-exclamation-circle"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ number_format($high_urgency_cases) }}

                </h2>

                <p>
                    High Urgency Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2>

                    {{ number_format($medium_urgency_cases) }}

                </h2>

                <p>
                    Medium Urgency Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-clock-o"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#605ca8;">

                <h2>

                    {{ number_format(
                        $average_recovery_score,
                        2
                    ) }}

                </h2>

                <p>
                    Average Recovery Score
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Escalation Ranking -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-line-chart"></i>

            Autonomous Escalation Priority Ranking

        </div>

        <div class="table-responsive">

            <table class="table modern-table table-striped">

                <thead>

                    <tr>

                        <th>Loan No</th>
                        <th>Customer</th>
                        <th>Priority</th>
                        <th>Recovery Score</th>
                        <th>Urgency Level</th>
                        <th>Escalation Age</th>
                        <th>Assigned Officer</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse(
                        $priority_ranked_escalations
                        as $escalation
                    )

                        <tr>

                            <td>

                                {{ optional(
                                    $escalation->loan
                                )->loan_no }}

                            </td>

                            <td>

                                {{ optional(
                                    $escalation->customer
                                )->name }}

                            </td>

                            <td>

                                <span class="label label-{{ $escalation->urgency_color }}">

                                    {{ ucfirst(
                                        $escalation->priority_level
                                    ) }}

                                </span>

                            </td>

                            <td>

                                <span class="label label-primary">

                                    {{ number_format(
                                        $escalation->recovery_score
                                    ) }}

                                </span>

                            </td>

                            <td>

                                <span class="label label-{{ $escalation->urgency_color }}">

                                    {{ ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $escalation->urgency_level
                                        )
                                    ) }}

                                </span>

                            </td>

                            <td>

                                {{ number_format(
                                    $escalation->escalation_age
                                ) }} Days

                            </td>

                            <td>

                                {{ optional(
                                    $escalation->assignedOfficer
                                )->username }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7"
                                class="text-center">

                                No escalation rankings found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Governance Alerts -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ number_format(
                        $critical_risk_alert_count
                    ) }}

                </h2>

                <p>
                    Critical Risk Alerts
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-bell"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ number_format(
                        $aged_risk_alert_count
                    ) }}

                </h2>

                <p>
                    Aged Escalation Alerts
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-clock-o"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#222d32;">

                <h2>

                    {{ number_format(
                        $unresolved_alert_count
                    ) }}

                </h2>

                <p>
                    Unresolved Alerts
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#605ca8;">

                <h2>

                    {{ number_format(
                        $governance_alert_count
                    ) }}

                </h2>

                <p>
                    Governance Alert Cases
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-gavel"></i>
                </div>

            </div>

        </div>

    </div>

</section>

@endsection