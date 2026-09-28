@extends('layouts.app')

@section('title', 'Settlement & Restructuring Profile')

@section('content')

<section class="content-header">

    <h1>
        Settlement & Restructuring Profile
        <small>
            Enterprise Settlement Governance & Recovery Intelligence
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

                <i class="fa fa-handshake-o"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Settlement Type

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    {{
                        strtoupper(
                            $settlement->settlement_type
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-yellow">

            <span class="info-box-icon">

                <i class="fa fa-line-chart"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Recovery Score

                </span>

                <span class="info-box-number">

                    {{ $settlement->recovery_score }}%

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
                            $settlement->settlement_risk_level
                            ?? 'MEDIUM'
                        )
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

                    Approval Status

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{
                        strtoupper(
                            $settlement->approval_status
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- SETTLEMENT PROFILE -->
<!-- ===================================================== -->

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Settlement Proposal

        </h3>

        <div class="pull-right">

            @if($settlement->approval_status != 'approved')

            <form method="POST"
                  action="/loan/loan-settlements/{{ $settlement->id }}/approve">

                @csrf

                <button type="submit"
                        class="btn btn-success">

                    <i class="fa fa-check"></i>

                    Approve Settlement

                </button>

            </form>

            @endif

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
                            Settlement Number
                        </th>

                        <td>

                            {{ $settlement->settlement_no }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Loan Number
                        </th>

                        <td>

                            {{
                                optional(
                                    $settlement->loan
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
                                    $settlement->customer
                                )->name
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Settlement Type
                        </th>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $settlement->settlement_type
                                        )
                                    )
                                }}

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Proposal Date
                        </th>

                        <td>

                            {{ $settlement->proposal_date }}

                        </td>

                    </tr>

                </table>

            </div>

            <!-- ============================================= -->
            <!-- GOVERNANCE -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Approval Status
                        </th>

                        <td>

                            @if($settlement->approval_status == 'approved')

                                <span class="label label-success">
                                    Approved
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
                            Committee Approval
                        </th>

                        <td>

                            @if($settlement->requires_committee_approval)

                                <span class="label label-danger">
                                    Required
                                </span>

                            @else

                                <span class="label label-success">
                                    Not Required
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Legal Clearance
                        </th>

                        <td>

                            @if($settlement->legal_clearance_required)

                                <span class="label label-danger">
                                    Required
                                </span>

                            @else

                                <span class="label label-success">
                                    Cleared
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Settlement Risk
                        </th>

                        <td>

                            @if(($settlement->settlement_risk_level ?? 'medium') == 'high')

                                <span class="label label-danger">
                                    High
                                </span>

                            @elseif(($settlement->settlement_risk_level ?? 'medium') == 'medium')

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
<!-- FINANCIAL ANALYTICS -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-success">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Settlement Financial Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Proposed Amount
                        </th>

                        <td>

                            {{
                                number_format(
                                    $settlement->proposed_amount,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Approved Amount
                        </th>

                        <td>

                            {{
                                number_format(
                                    $settlement->approved_amount ?? 0,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Settlement Due Date
                        </th>

                        <td>

                            {{
                                $settlement->settlement_due_date
                                ?? 'Pending'
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Recovery Score
                        </th>

                        <td>

                            <span class="label label-success">

                                {{ $settlement->recovery_score }}%

                            </span>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

    <!-- ================================================ -->
    <!-- RESTRUCTURE -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Restructuring Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Restructure Installments
                        </th>

                        <td>

                            {{
                                $settlement->restructure_installments
                                ?? 'N/A'
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Restructure Interest Rate
                        </th>

                        <td>

                            {{
                                $settlement->restructure_interest_rate
                                ?? 'N/A'
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Settlement Governance
                        </th>

                        <td>

                            Enabled

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

<div class="box box-danger">

    <div class="box-header with-border">

        <h3 class="box-title">

            Settlement Negotiation Notes

        </h3>

    </div>

    <div class="box-body">

        <div class="well"
             style="min-height:180px;
                    font-size:15px;">

            {{ $settlement->notes }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- APPROVAL TIMELINE -->
<!-- ===================================================== -->

<div class="box box-info">

    <div class="box-header with-border">

        <h3 class="box-title">

            Settlement Approval & Governance Timeline

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            <li>

                <i class="fa fa-handshake-o bg-aqua"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $settlement->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Settlement Proposal Created

                    </h3>

                    <div class="timeline-body">

                        Enterprise settlement negotiation initiated.

                    </div>

                </div>

            </li>

            @if($settlement->requires_committee_approval)

            <li>

                <i class="fa fa-users bg-yellow"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $settlement->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Committee Approval Required

                    </h3>

                    <div class="timeline-body">

                        Settlement routed through governance approval workflow.

                    </div>

                </div>

            </li>

            @endif

            @if($settlement->approval_status == 'approved')

            <li>

                <i class="fa fa-check bg-green"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $settlement->approved_at }}

                    </span>

                    <h3 class="timeline-header">

                        Settlement Approved

                    </h3>

                    <div class="timeline-body">

                        Settlement approved under enterprise governance framework.

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

                    Settlement

                </h4>

                <p>

                    Active

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-handshake-o"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-yellow">

            <div class="inner">

                <h4>

                    Governance

                </h4>

                <p>

                    Controlled

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-shield"></i>

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

        <div class="small-box bg-green">

            <div class="inner">

                <h4>

                    Recovery

                </h4>

                <p>

                    Optimized

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-line-chart"></i>

            </div>

        </div>

    </div>

</div>

</section>

@endsection