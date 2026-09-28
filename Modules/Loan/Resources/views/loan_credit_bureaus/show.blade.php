@extends('layouts.app')

@section('title', 'Credit Bureau Profile')

@section('content')

<section class="content-header">

    <h1>
        Credit Bureau Profile
        <small>
            Enterprise External Risk & Credit Intelligence
        </small>
    </h1>

</section>

<section class="content">

<!-- ===================================================== -->
<!-- KPI DASHBOARD -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-aqua">

            <span class="info-box-icon">

                <i class="fa fa-line-chart"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Credit Score

                </span>

                <span class="info-box-number">

                    {{ $bureau_record->credit_score }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-red">

            <span class="info-box-icon">

                <i class="fa fa-warning"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Risk Level

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    {{
                        strtoupper(
                            $bureau_record->risk_level
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-yellow">

            <span class="info-box-icon">

                <i class="fa fa-money"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Total Exposure

                </span>

                <span class="info-box-number"
                      style="font-size:18px;">

                    {{
                        number_format(
                            $bureau_record->total_exposure,
                            2
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-green">

            <span class="info-box-icon">

                <i class="fa fa-refresh"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Sync Status

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    {{
                        strtoupper(
                            $bureau_record->sync_status
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- PROFILE -->
<!-- ===================================================== -->

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Credit Bureau Record

        </h3>

        <div class="pull-right">

            <form method="POST"
                  action="/loan/loan-credit-bureaus/{{ $bureau_record->id }}/resync">

                @csrf

                <button type="submit"
                        class="btn btn-success">

                    <i class="fa fa-refresh"></i>

                    Re-Synchronize

                </button>

            </form>

        </div>

    </div>

    <div class="box-body">

        <div class="row">

            <!-- ============================================= -->
            <!-- BASIC -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Reference Number
                        </th>

                        <td>

                            {{ $bureau_record->reference_no }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Customer
                        </th>

                        <td>

                            {{
                                optional(
                                    $bureau_record->customer
                                )->name
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Loan Number
                        </th>

                        <td>

                            {{
                                optional(
                                    $bureau_record->loan
                                )->loan_no
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Bureau Name
                        </th>

                        <td>

                            {{ $bureau_record->bureau_name }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Bureau Status
                        </th>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucfirst(
                                        $bureau_record->bureau_status
                                    )
                                }}

                            </span>

                        </td>

                    </tr>

                </table>

            </div>

            <!-- ============================================= -->
            <!-- RISK -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Risk Level
                        </th>

                        <td>

                            @if($bureau_record->risk_level == 'high')

                                <span class="label label-danger">
                                    High
                                </span>

                            @elseif($bureau_record->risk_level == 'medium')

                                <span class="label label-warning">
                                    Medium
                                </span>

                            @else

                                <span class="label label-success">
                                    Low
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Behavior Score
                        </th>

                        <td>

                            <span class="label label-info">

                                {{
                                    $bureau_record->behavior_score
                                }}%

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Multi-Lender Risk
                        </th>

                        <td>

                            @if($bureau_record->multi_lender_risk)

                                <span class="label label-danger">
                                    Detected
                                </span>

                            @else

                                <span class="label label-success">
                                    Normal
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            External Collections Flag
                        </th>

                        <td>

                            @if($bureau_record->external_collection_flag)

                                <span class="label label-danger">
                                    Active
                                </span>

                            @else

                                <span class="label label-success">
                                    Clear
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Governance Status
                        </th>

                        <td>

                            Operational

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- EXPOSURE -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-danger">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Exposure Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            External Exposure
                        </th>

                        <td>

                            {{
                                number_format(
                                    $bureau_record->external_exposure,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Total Exposure
                        </th>

                        <td>

                            {{
                                number_format(
                                    $bureau_record->total_exposure,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Active Loans Count
                        </th>

                        <td>

                            {{
                                $bureau_record->active_loans_count
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Defaulted Loans Count
                        </th>

                        <td>

                            <span class="label label-danger">

                                {{
                                    $bureau_record->defaulted_loans_count
                                }}

                            </span>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

    <!-- ================================================ -->
    <!-- DELINQUENCY -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Delinquency Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Delinquency Days
                        </th>

                        <td>

                            <span class="label label-danger">

                                {{
                                    $bureau_record->delinquency_days
                                }} Days

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Bureau Sync Date
                        </th>

                        <td>

                            {{
                                $bureau_record->bureau_sync_date
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Sync Status
                        </th>

                        <td>

                            @if($bureau_record->sync_status == 'synced')

                                <span class="label label-success">
                                    Synced
                                </span>

                            @else

                                <span class="label label-warning">
                                    Pending
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Autonomous Monitoring
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- NOTES -->
<!-- ===================================================== -->

<div class="box box-info">

    <div class="box-header with-border">

        <h3 class="box-title">

            Bureau Intelligence Notes

        </h3>

    </div>

    <div class="box-body">

        <div class="well"
             style="min-height:180px;
                    font-size:15px;">

            {{ $bureau_record->notes }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- TIMELINE -->
<!-- ===================================================== -->

<div class="box box-success">

    <div class="box-header with-border">

        <h3 class="box-title">

            Bureau Synchronization Timeline

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            <li>

                <i class="fa fa-database bg-aqua"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $bureau_record->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Bureau Record Created

                    </h3>

                    <div class="timeline-body">

                        External credit bureau profile synchronized.

                    </div>

                </div>

            </li>

            <li>

                <i class="fa fa-refresh bg-green"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $bureau_record->bureau_sync_date }}

                    </span>

                    <h3 class="timeline-header">

                        Bureau Synchronization Completed

                    </h3>

                    <div class="timeline-body">

                        Enterprise bureau synchronization workflow completed.

                    </div>

                </div>

            </li>

            @if($bureau_record->risk_level == 'high')

            <li>

                <i class="fa fa-warning bg-red"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $bureau_record->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        High Risk Escalation Triggered

                    </h3>

                    <div class="timeline-body">

                        High-risk external exposure detected by bureau intelligence engine.

                    </div>

                </div>

            </li>

            @endif

        </ul>

    </div>

</div>

<!-- ===================================================== -->
<!-- ENTERPRISE GOVERNANCE -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3">

        <div class="small-box bg-aqua">

            <div class="inner">

                <h4>

                    Bureau

                </h4>

                <p>

                    Synced

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-database"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-red">

            <div class="inner">

                <h4>

                    Risk

                </h4>

                <p>

                    Monitored

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-warning"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-yellow">

            <div class="inner">

                <h4>

                    Exposure

                </h4>

                <p>

                    Analyzed

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-money"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-green">

            <div class="inner">

                <h4>

                    Governance

                </h4>

                <p>

                    Active

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-shield"></i>

            </div>

        </div>

    </div>

</div>

</section>

@endsection