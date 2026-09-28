@extends('layouts.app')

@section('title', 'Enterprise Executive Intelligence Center')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')

<style>

    .enterprise-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 22px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        border-top: 4px solid #3c8dbc;
        min-height: 145px;
    }

    .enterprise-card h2 {
        font-size: 30px;
        font-weight: 700;
        margin: 0;
        color: #2c3e50;
    }

    .enterprise-card p {
        margin-top: 10px;
        color: #7f8c8d;
        font-size: 14px;
        font-weight: 600;
    }

    .enterprise-icon {
        float: right;
        font-size: 44px;
        opacity: 0.10;
        margin-top: -45px;
    }

    .governance-box {
        background: #ffffff;
        border-radius: 16px;
        padding: 22px;
        margin-bottom: 22px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }

    .governance-title {
        font-size: 22px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 22px;
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

    .progress {
        height: 10px !important;
        border-radius: 20px;
        background: #ecf0f1;
    }

    .timeline-modern {
        list-style: none;
        padding: 0;
    }

    .timeline-modern li {
        position: relative;
        padding-left: 30px;
        margin-bottom: 25px;
        border-left: 2px solid #d2d6de;
    }

    .timeline-modern li:before {
        content: '';
        width: 12px;
        height: 12px;
        background: #00c0ef;
        border-radius: 50%;
        position: absolute;
        left: -7px;
        top: 5px;
    }

    .timeline-card {
        background: #f9fafc;
        padding: 16px;
        border-radius: 10px;
    }

    .executive-card {
        border-radius: 14px;
        padding: 20px;
        color: #fff;
        margin-bottom: 20px;
    }

    .executive-card h3 {
        margin: 0;
        font-size: 30px;
        font-weight: 700;
    }

    .executive-card p {
        margin-top: 10px;
        font-size: 14px;
        font-weight: 600;
    }

</style>

<section class="content-header">

    <h1>

        Enterprise Executive Intelligence Center

        <small>
            Institutional Governance • Recovery Intelligence • Executive MIS Analytics
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- EXECUTIVE KPI DASHBOARD -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-3 col-md-6">

            <div class="enterprise-card"
                 style="border-top-color:#00c0ef;">

                <h2>

                    {{ number_format($total_portfolio, 2) }}

                </h2>

                <p>
                    Total Portfolio Exposure
                </p>

                <div class="enterprise-icon">
                    <i class="fa fa-briefcase"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="enterprise-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ number_format($outstanding_portfolio, 2) }}

                </h2>

                <p>
                    Outstanding Exposure
                </p>

                <div class="enterprise-icon">
                    <i class="fa fa-money"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="enterprise-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ $npl_ratio }}%

                </h2>

                <p>
                    NPL Ratio
                </p>

                <div class="enterprise-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="enterprise-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ $recovery_rate }}%

                </h2>

                <p>
                    Recovery Rate
                </p>

                <div class="enterprise-icon">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- EXECUTIVE GOVERNANCE -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-shield"></i>

                    Executive Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Portfolio Governance
                    </span>

                    <span class="pull-right">
                        Active
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-info"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Recovery Governance
                    </span>

                    <span class="pull-right">
                        Operational
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-success"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Regulatory Governance
                    </span>

                    <span class="pull-right">
                        Enabled
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-danger"
                             style="width:100%">
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-line-chart"></i>

                    Executive Recovery Intelligence

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Total Recoveries
                            </th>

                            <td>

                                {{
                                    number_format(
                                        $total_recoveries,
                                        2
                                    )
                                }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Write-Off Losses
                            </th>

                            <td>

                                <span class="label label-danger">

                                    {{
                                        number_format(
                                            $write_off_losses,
                                            2
                                        )
                                    }}

                                </span>

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Open Escalations
                            </th>

                            <td>

                                <span class="label label-warning">

                                    {{ $open_escalations }}

                                </span>

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Governance Status
                            </th>

                            <td>
                                Executive Active
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- EXECUTIVE PERFORMANCE -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-3">

            <div class="executive-card"
                 style="background:#00c0ef;">

                <h3>

                    {{ $total_loans }}

                </h3>

                <p>
                    Total Loans
                </p>

            </div>

        </div>

        <div class="col-md-3">

            <div class="executive-card"
                 style="background:#00a65a;">

                <h3>

                    {{ $active_loans }}

                </h3>

                <p>
                    Active Loans
                </p>

            </div>

        </div>

        <div class="col-md-3">

            <div class="executive-card"
                 style="background:#dd4b39;">

                <h3>

                    {{ $defaulted_loans }}

                </h3>

                <p>
                    Defaulted Loans
                </p>

            </div>

        </div>

        <div class="col-md-3">

            <div class="executive-card"
                 style="background:#f39c12;">

                <h3>

                    {{ $collections_count }}

                </h3>

                <p>
                    Collection Activities
                </p>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- REGULATORY ANALYTICS -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-4">

            <div class="executive-card"
                 style="background:#f39c12;">

                <h3>

                    {{ number_format($par_30, 2) }}

                </h3>

                <p>
                    PAR 30 Exposure
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="executive-card"
                 style="background:#dd4b39;">

                <h3>

                    {{ number_format($par_90, 2) }}

                </h3>

                <p>
                    PAR 90 Exposure
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="executive-card"
                 style="background:#00a65a;">

                <h3>

                    {{ $repayment_count }}

                </h3>

                <p>
                    Total Repayments
                </p>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- EXECUTIVE CONTROL CENTER -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-dashboard"></i>

            Executive Governance & Institutional Intelligence

        </div>

        <div class="row">

            <div class="col-md-3">

                <div class="enterprise-card"
                     style="border-top-color:#00c0ef;">

                    <h2>
                        MIS
                    </h2>

                    <p>
                        Operational
                    </p>

                </div>

            </div>

            <div class="col-md-3">

                <div class="enterprise-card"
                     style="border-top-color:#00a65a;">

                    <h2>
                        Recovery
                    </h2>

                    <p>
                        Optimized
                    </p>

                </div>

            </div>

            <div class="col-md-3">

                <div class="enterprise-card"
                     style="border-top-color:#f39c12;">

                    <h2>
                        Compliance
                    </h2>

                    <p>
                        Governed
                    </p>

                </div>

            </div>

            <div class="col-md-3">

                <div class="enterprise-card"
                     style="border-top-color:#dd4b39;">

                    <h2>
                        Risk
                    </h2>

                    <p>
                        Monitored
                    </p>

                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- EXECUTIVE TIMELINE -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Executive Governance Timeline

        </div>

        <ul class="timeline-modern">

            <li>

                <div class="timeline-card">

                    <strong>
                        Executive MIS Generated
                    </strong>

                    <br><br>

                    Institutional MIS analytics and executive governance dashboard generated successfully.

                    <hr>

                    <small class="text-muted">

                        {{ now() }}

                    </small>

                </div>

            </li>

            <li>

                <div class="timeline-card">

                    <strong>
                        Recovery Intelligence Updated
                    </strong>

                    <br><br>

                    Enterprise recovery governance analytics refreshed successfully.

                    <hr>

                    <small class="text-muted">

                        {{ now() }}

                    </small>

                </div>

            </li>

            <li>

                <div class="timeline-card">

                    <strong>
                        Portfolio Governance Validated
                    </strong>

                    <br><br>

                    Executive portfolio governance and regulatory intelligence validated.

                    <hr>

                    <small class="text-muted">

                        {{ now() }}

                    </small>

                </div>

            </li>

        </ul>

    </div>

</section>

@endsection