@extends('layouts.app')

@section('title', 'Enterprise Portfolio Intelligence Center')

@section('content')

<style>

    .dashboard-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 22px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        border-top: 4px solid #3c8dbc;
        min-height: 140px;
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

    .risk-card {
        border-radius: 14px;
        padding: 20px;
        color: #fff;
        margin-bottom: 20px;
    }

    .risk-card h3 {
        margin: 0;
        font-size: 30px;
        font-weight: 700;
    }

    .risk-card p {
        margin-top: 10px;
        font-size: 14px;
        font-weight: 600;
    }

</style>

<section class="content-header">

    <h1>

        Enterprise Portfolio Intelligence Center

        <small>
            Executive Portfolio Analytics • Risk Intelligence • Recovery Forecasting
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- KPI DASHBOARD -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2>

                    {{ number_format($total_portfolio, 2) }}

                </h2>

                <p>
                    Total Portfolio Exposure
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-briefcase"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ number_format($outstanding_portfolio, 2) }}

                </h2>

                <p>
                    Outstanding Exposure
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-money"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ $npl_ratio }}%

                </h2>

                <p>
                    NPL Ratio
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ $forecasted_recovery_rate }}%

                </h2>

                <p>
                    Forecast Recovery Rate
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- GOVERNANCE -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-shield"></i>

                    Portfolio Governance Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Portfolio Monitoring
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
                        Recovery Forecasting
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
                        NPL Governance
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

        <!-- ================================================ -->
        <!-- METRICS -->
        <!-- ================================================ -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-line-chart"></i>

                    Executive Portfolio Metrics

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Total Loans
                            </th>

                            <td>

                                {{ $total_loans }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Active Loans
                            </th>

                            <td>

                                {{ $active_loans }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Defaulted Loans
                            </th>

                            <td>

                                <span class="label label-danger">

                                    {{ $defaulted_loans }}

                                </span>

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Delinquent Loans
                            </th>

                            <td>

                                <span class="label label-warning">

                                    {{ $delinquent_loans }}

                                </span>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- PAR ANALYTICS -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-4">

            <div class="risk-card"
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

            <div class="risk-card"
                 style="background:#ff851b;">

                <h3>

                    {{ number_format($par_60, 2) }}

                </h3>

                <p>
                    PAR 60 Exposure
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="risk-card"
                 style="background:#dd4b39;">

                <h3>

                    {{ number_format($par_90, 2) }}

                </h3>

                <p>
                    PAR 90 Exposure
                </p>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- RECOVERY & LOSS -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-line-chart"></i>

                    Recovery Performance Intelligence

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
                                Forecast Recovery Rate
                            </th>

                            <td>

                                <span class="label label-success">

                                    {{
                                        $forecasted_recovery_rate
                                    }}%

                                </span>

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Recovery Governance
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Forecast Intelligence
                            </th>

                            <td>
                                Running
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- ================================================ -->
        <!-- LOSS -->
        <!-- ================================================ -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-warning"></i>

                    Financial Loss Governance

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Write-Off Losses
                            </th>

                            <td>

                                {{
                                    number_format(
                                        $write_off_losses,
                                        2
                                    )
                                }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                NPL Ratio
                            </th>

                            <td>

                                <span class="label label-danger">

                                    {{ $npl_ratio }}%

                                </span>

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Portfolio Governance
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Audit Intelligence
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- RISK SEGMENTATION -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-pie-chart"></i>

            Portfolio Risk Segmentation

        </div>

        <div class="row">

            <div class="col-md-4">

                <div class="risk-card"
                     style="background:#dd4b39;">

                    <h3>

                        {{ $high_risk }}

                    </h3>

                    <p>
                        High Risk Accounts
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="risk-card"
                     style="background:#f39c12;">

                    <h3>

                        {{ $medium_risk }}

                    </h3>

                    <p>
                        Medium Risk Accounts
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="risk-card"
                     style="background:#00a65a;">

                    <h3>

                        {{ $low_risk }}

                    </h3>

                    <p>
                        Low Risk Accounts
                    </p>

                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- EXECUTIVE GOVERNANCE -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-3">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2>
                    Portfolio
                </h2>

                <p>
                    Governed
                </p>

            </div>

        </div>

        <div class="col-md-3">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>
                    Risk
                </h2>

                <p>
                    Monitored
                </p>

            </div>

        </div>

        <div class="col-md-3">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>
                    Delinquency
                </h2>

                <p>
                    Tracked
                </p>

            </div>

        </div>

        <div class="col-md-3">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>
                    Recovery
                </h2>

                <p>
                    Forecasted
                </p>

            </div>

        </div>

    </div>

</section>

@endsection