@extends('layouts.app')

@section('title', 'Legal Recovery Case Profile')

@section('content')

<section class="content-header">

    <h1>
        Legal Recovery Case Profile
        <small>
            Enterprise Litigation Governance & Legal Intelligence
        </small>
    </h1>

</section>

<section class="content">

<!-- ===================================================== -->
<!-- KPI DASHBOARD -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-red">

            <span class="info-box-icon">

                <i class="fa fa-balance-scale"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Legal Stage

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    {{
                        strtoupper(
                            $legal_case->legal_stage
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-yellow">

            <span class="info-box-icon">

                <i class="fa fa-calendar"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Court Hearing

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{
                        $legal_case->court_hearing_date
                        ?? 'Pending'
                    }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-aqua">

            <span class="info-box-icon">

                <i class="fa fa-users"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Assigned Legal Officer

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{
                        $legal_case->assigned_to
                        ?? 'Unassigned'
                    }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-green">

            <span class="info-box-icon">

                <i class="fa fa-check-circle"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Case Status

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{
                        strtoupper(
                            $legal_case->status
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- LEGAL CASE PROFILE -->
<!-- ===================================================== -->

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Legal Recovery Case

        </h3>

        <div class="pull-right">

            @if($legal_case->status == 'active')

            <form method="POST"
                  action="/loan/loan-legal-recoveries/{{ $legal_case->id }}/close">

                @csrf

                <button type="submit"
                        class="btn btn-success">

                    <i class="fa fa-check"></i>

                    Close Legal Case

                </button>

            </form>

            @endif

        </div>

    </div>

    <div class="box-body">

        <div class="row">

            <!-- ============================================= -->
            <!-- CASE -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Case Number
                        </th>

                        <td>

                            {{ $legal_case->case_no }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Case Title
                        </th>

                        <td>

                            {{ $legal_case->case_title }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Loan Number
                        </th>

                        <td>

                            {{
                                optional(
                                    $legal_case->loan
                                )->loan_no
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Customer
                        </th>

                        <td>

                            {{
                                optional(
                                    $legal_case->customer
                                )->name
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Court Name
                        </th>

                        <td>

                            {{
                                $legal_case->court_name
                                ?? 'Not Assigned'
                            }}

                        </td>

                    </tr>

                </table>

            </div>

            <!-- ============================================= -->
            <!-- LEGAL -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Legal Stage
                        </th>

                        <td>

                            <span class="label label-danger">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $legal_case->legal_stage
                                        )
                                    )
                                }}

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Priority Level
                        </th>

                        <td>

                            @if(($legal_case->priority_level ?? 'high') == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif(($legal_case->priority_level ?? 'high') == 'high')

                                <span class="label label-warning">
                                    High
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
                            Settlement Allowed
                        </th>

                        <td>

                            @if($legal_case->settlement_allowed)

                                <span class="label label-success">
                                    Allowed
                                </span>

                            @else

                                <span class="label label-danger">
                                    Restricted
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Recovery Probability
                        </th>

                        <td>

                            <span class="label label-primary">

                                {{
                                    $legal_case->recovery_probability
                                    ?? 50
                                }}%

                            </span>

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
<!-- CLAIM & SETTLEMENT -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-danger">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Legal Claim Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Claim Amount
                        </th>

                        <td>

                            {{
                                number_format(
                                    $legal_case->claim_amount,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Legal Cost Estimate
                        </th>

                        <td>

                            {{
                                number_format(
                                    $legal_case->legal_cost_estimate ?? 0,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Court Hearing Date
                        </th>

                        <td>

                            {{
                                $legal_case->court_hearing_date
                                ?? 'Pending'
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Litigation Monitoring
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

    <!-- ================================================ -->
    <!-- SETTLEMENT -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-success">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Settlement Governance

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Settlement Eligibility
                        </th>

                        <td>

                            @if($legal_case->settlement_allowed)

                                <span class="label label-success">
                                    Eligible
                                </span>

                            @else

                                <span class="label label-danger">
                                    Restricted
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Recovery Intelligence
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Legal Governance
                        </th>

                        <td>

                            Verified

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Autonomous Monitoring
                        </th>

                        <td>

                            Enabled

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- LEGAL NOTES -->
<!-- ===================================================== -->

<div class="box box-warning">

    <div class="box-header with-border">

        <h3 class="box-title">

            Litigation & Recovery Notes

        </h3>

    </div>

    <div class="box-body">

        <div class="well"
             style="min-height:180px;
                    font-size:15px;">

            {{ $legal_case->notes }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- LEGAL TIMELINE -->
<!-- ===================================================== -->

<div class="box box-info">

    <div class="box-header with-border">

        <h3 class="box-title">

            Litigation Timeline & Governance

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            <li>

                <i class="fa fa-balance-scale bg-red"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $legal_case->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Legal Recovery Case Created

                    </h3>

                    <div class="timeline-body">

                        Enterprise legal recovery workflow initiated.

                    </div>

                </div>

            </li>

            @if($legal_case->court_hearing_date)

            <li>

                <i class="fa fa-university bg-yellow"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $legal_case->court_hearing_date }}

                    </span>

                    <h3 class="timeline-header">

                        Court Hearing Scheduled

                    </h3>

                    <div class="timeline-body">

                        Court hearing entered into litigation governance workflow.

                    </div>

                </div>

            </li>

            @endif

            @if($legal_case->status == 'closed')

            <li>

                <i class="fa fa-check bg-green"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $legal_case->closed_at }}

                    </span>

                    <h3 class="timeline-header">

                        Legal Case Closed

                    </h3>

                    <div class="timeline-body">

                        Legal recovery workflow successfully completed.

                    </div>

                </div>

            </li>

            @endif

        </ul>

    </div>

</div>

<!-- ===================================================== -->
<!-- ENTERPRISE LEGAL GOVERNANCE -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3">

        <div class="small-box bg-red">

            <div class="inner">

                <h4>

                    Litigation

                </h4>

                <p>

                    Active

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-balance-scale"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-yellow">

            <div class="inner">

                <h4>

                    Court

                </h4>

                <p>

                    Monitored

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-university"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-aqua">

            <div class="inner">

                <h4>

                    Settlement

                </h4>

                <p>

                    Governed

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

                    Verified

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