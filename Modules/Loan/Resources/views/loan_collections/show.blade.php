@extends('layouts.app')

@section('title', 'Collection Action Profile')

@section('content')

<section class="content-header">

    <h1>
        Collection Action Profile
        <small>
            Enterprise Recovery Execution & Audit Intelligence
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

                <i class="fa fa-phone"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Collection Type

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    {{
                        ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $collection_note->collection_type
                            )
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-green">

            <span class="info-box-icon">

                <i class="fa fa-calendar"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Promise To Pay

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{
                        $collection_note->promise_to_pay_date
                        ?? 'N/A'
                    }}

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

                    Follow Up Date

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{
                        $collection_note->follow_up_date
                        ?? 'N/A'
                    }}

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

                    Escalation Status

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    @if($collection_note->escalation_required)

                        Escalated

                    @else

                        Normal

                    @endif

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- COLLECTION PROFILE -->
<!-- ===================================================== -->

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">

            Collection Recovery Action

        </h3>

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
                                    $collection_note->loan
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
                                    $collection_note->customer
                                )->name
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Collection Type
                        </th>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $collection_note->collection_type
                                        )
                                    )
                                }}

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Collection Officer
                        </th>

                        <td>

                            {{
                                optional(
                                    $collection_note->createdBy
                                )->username
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Created Date
                        </th>

                        <td>

                            {{
                                $collection_note->created_at
                            }}

                        </td>

                    </tr>

                </table>

            </div>

            <!-- ============================================= -->
            <!-- RECOVERY -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Priority Level
                        </th>

                        <td>

                            @if(($collection_note->priority_level ?? 'normal') == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif(($collection_note->priority_level ?? 'normal') == 'high')

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
                            Call Outcome
                        </th>

                        <td>

                            {{
                                ucfirst(
                                    $collection_note->call_outcome
                                    ?? 'not_recorded'
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Field Visit Required
                        </th>

                        <td>

                            @if($collection_note->field_visit_required)

                                <span class="label label-danger">
                                    Required
                                </span>

                            @else

                                <span class="label label-success">
                                    No
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Escalation Required
                        </th>

                        <td>

                            @if($collection_note->escalation_required)

                                <span class="label label-danger">
                                    Yes
                                </span>

                            @else

                                <span class="label label-success">
                                    No
                                </span>

                            @endif

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Recovery Governance
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
<!-- COLLECTION NOTES -->
<!-- ===================================================== -->

<div class="box box-success">

    <div class="box-header with-border">

        <h3 class="box-title">

            Recovery Communication Notes

        </h3>

    </div>

    <div class="box-body">

        <div class="well"
             style="min-height:180px;
                    font-size:15px;">

            {{ $collection_note->note }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- FOLLOW UP -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Promise To Pay Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Promise To Pay Date
                        </th>

                        <td>

                            {{
                                $collection_note->promise_to_pay_date
                                ?? 'Not Recorded'
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Follow Up Date
                        </th>

                        <td>

                            {{
                                $collection_note->follow_up_date
                                ?? 'Not Scheduled'
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Collections Workflow
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Workforce Monitoring
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

        <div class="box box-danger">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Escalation & Recovery Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Recovery Escalation
                        </th>

                        <td>

                            @if($collection_note->escalation_required)

                                <span class="label label-danger">
                                    Escalated
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
                            Field Recovery
                        </th>

                        <td>

                            @if($collection_note->field_visit_required)

                                Active

                            @else

                                Not Required

                            @endif

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

                    <tr>

                        <th>
                            Governance Audit
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
<!-- ENTERPRISE GOVERNANCE -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3">

        <div class="small-box bg-green">

            <div class="inner">

                <h4>

                    Recovery

                </h4>

                <p>

                    Operational

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-money"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-yellow">

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

        <div class="small-box bg-aqua">

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

    <div class="col-md-3">

        <div class="small-box bg-red">

            <div class="inner">

                <h4>

                    Audit

                </h4>

                <p>

                    Verified

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-check-circle"></i>

            </div>

        </div>

    </div>

</div>

</section>

@endsection