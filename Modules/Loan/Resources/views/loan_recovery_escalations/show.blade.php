@extends('layouts.app')

@section('title', 'Recovery Escalation Profile')

@section('content')

<section class="content-header">

    <h1>
        Recovery Escalation Profile
        <small>
            Enterprise Recovery Governance & Escalation Intelligence
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

                <i class="fa fa-warning"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Escalation Priority

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    {{ strtoupper($escalation->priority_level) }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-yellow">

            <span class="info-box-icon">

                <i class="fa fa-clock-o"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    SLA Due Date

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{ $escalation->sla_due_date }}

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

                    Assigned Recovery

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{ $escalation->assigned_to ?? 'Unassigned' }}

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

                    Escalation Status

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{ strtoupper($escalation->status) }}

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- ESCALATION PROFILE -->
<!-- ===================================================== -->

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Recovery Escalation

        </h3>

        <div class="pull-right">

            @if($escalation->status == 'open')

            <form method="POST"
                  action="/loan/loan-recovery-escalations/{{ $escalation->id }}/close">

                @csrf

                <button type="submit"
                        class="btn btn-success">

                    <i class="fa fa-check"></i>

                    Close Escalation

                </button>

            </form>

            @endif

        </div>

    </div>

    <div class="box-body">

        <div class="row">

            <!-- ============================================= -->
            <!-- LOAN -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Loan Number
                        </th>

                        <td>

                            {{
                                optional(
                                    $escalation->loan
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
                                    $escalation->customer
                                )->name
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Escalation Type
                        </th>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $escalation->escalation_type
                                        )
                                    )
                                }}

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Escalation Date
                        </th>

                        <td>

                            {{ $escalation->escalation_date }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Workforce Priority Score
                        </th>

                        <td>

                            {{ $escalation->workforce_priority_score ?? 50 }}

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
                            Priority Level
                        </th>

                        <td>

                            @if($escalation->priority_level == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif($escalation->priority_level == 'high')

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
                            Legal Action
                        </th>

                        <td>

                            @if($escalation->legal_action_required)

                                <span class="label label-danger">
                                    Required
                                </span>

                            @else

                                <span class="label label-success">
                                    Standard
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Field Recovery
                        </th>

                        <td>

                            @if($escalation->field_visit_required)

                                <span class="label label-warning">
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
                            SLA Monitoring
                        </th>

                        <td>

                            Active

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
<!-- ESCALATION NOTES -->
<!-- ===================================================== -->

<div class="box box-danger">

    <div class="box-header with-border">

        <h3 class="box-title">

            Escalation Recovery Notes

        </h3>

    </div>

    <div class="box-body">

        <div class="well"
             style="min-height:180px;
                    font-size:15px;">

            {{ $escalation->notes }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- SLA GOVERNANCE -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    SLA & Escalation Governance

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            SLA Due Date
                        </th>

                        <td>

                            {{ $escalation->sla_due_date }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Escalation Status
                        </th>

                        <td>

                            @if($escalation->status == 'open')

                                <span class="label label-warning">
                                    Open
                                </span>

                            @else

                                <span class="label label-success">
                                    Closed
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Closed Date
                        </th>

                        <td>

                            {{ $escalation->closed_at ?? 'Pending' }}

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

    <!-- ================================================ -->
    <!-- RECOVERY -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-info">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Recovery Assignment Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Assigned Recovery Officer
                        </th>

                        <td>

                            {{ $escalation->assigned_to ?? 'Unassigned' }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Workforce Routing
                        </th>

                        <td>

                            Operational

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Recovery Prioritization
                        </th>

                        <td>

                            Enabled

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Institutional Governance
                        </th>

                        <td>

                            Verified

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- ACTION HISTORY -->
<!-- ===================================================== -->

<div class="box box-success">

    <div class="box-header with-border">

        <h3 class="box-title">

            Escalation Action Timeline

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            <li>

                <i class="fa fa-warning bg-red"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $escalation->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Escalation Created

                    </h3>

                    <div class="timeline-body">

                        Enterprise recovery escalation initiated.

                    </div>

                </div>

            </li>

            @if($escalation->legal_action_required)

            <li>

                <i class="fa fa-balance-scale bg-yellow"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $escalation->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Legal Escalation Triggered

                    </h3>

                    <div class="timeline-body">

                        Legal recovery workflow activated.

                    </div>

                </div>

            </li>

            @endif

            @if($escalation->field_visit_required)

            <li>

                <i class="fa fa-map-marker bg-aqua"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $escalation->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Field Recovery Required

                    </h3>

                    <div class="timeline-body">

                        Field recovery workflow assigned.

                    </div>

                </div>

            </li>

            @endif

            @if($escalation->status == 'closed')

            <li>

                <i class="fa fa-check bg-green"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $escalation->closed_at }}

                    </span>

                    <h3 class="timeline-header">

                        Escalation Closed

                    </h3>

                    <div class="timeline-body">

                        Recovery escalation successfully resolved.

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

        <div class="small-box bg-red">

            <div class="inner">

                <h4>

                    Escalation

                </h4>

                <p>

                    Active

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

                    SLA

                </h4>

                <p>

                    Monitored

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-clock-o"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-aqua">

            <div class="inner">

                <h4>

                    Workforce

                </h4>

                <p>

                    Optimized

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-users"></i>

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