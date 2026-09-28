@extends('layouts.app')

@section('title', 'Write-Off Governance Profile')

@section('content')

<section class="content-header">

    <h1>
        Write-Off Governance Profile
        <small>
            Enterprise Recovery Closure & Financial Loss Intelligence
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

                <i class="fa fa-times-circle"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Write-Off Amount

                </span>

                <span class="info-box-number"
                      style="font-size:18px;">

                    {{
                        number_format(
                            $write_off->write_off_amount,
                            2
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

                    Provisioning Score

                </span>

                <span class="info-box-number">

                    {{ $write_off->provisioning_score }}%

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-aqua">

            <span class="info-box-icon">

                <i class="fa fa-money"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Loss Amount

                </span>

                <span class="info-box-number"
                      style="font-size:18px;">

                    {{
                        number_format(
                            $write_off->loss_amount,
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
                            $write_off->approval_status
                        )
                    }}

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- WRITE-OFF PROFILE -->
<!-- ===================================================== -->

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">

            Enterprise Write-Off Governance Record

        </h3>

        <div class="pull-right">

            @if($write_off->approval_status != 'approved')

            <form method="POST"
                  action="/loan/loan-write-offs/{{ $write_off->id }}/approve">

                @csrf

                <button type="submit"
                        class="btn btn-success">

                    <i class="fa fa-check"></i>

                    Approve Write-Off

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
                            Write-Off Number
                        </th>

                        <td>

                            {{ $write_off->write_off_no }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Loan Number
                        </th>

                        <td>

                            {{
                                optional(
                                    $write_off->loan
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
                                    $write_off->customer
                                )->name
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Write-Off Date
                        </th>

                        <td>

                            {{ $write_off->write_off_date }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Write-Off Reason
                        </th>

                        <td>

                            <span class="label label-danger">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $write_off->write_off_reason
                                        )
                                    )
                                }}

                            </span>

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

                            @if($write_off->approval_status == 'approved')

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
                            Board Approval
                        </th>

                        <td>

                            @if($write_off->requires_board_approval)

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

                            @if($write_off->legal_clearance_required)

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
                            Recovery Closure
                        </th>

                        <td>

                            @if($write_off->recovery_closure_status == 'closed')

                                <span class="label label-success">
                                    Closed
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
<!-- FINANCIAL LOSS -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-danger">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Financial Loss Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Write-Off Amount
                        </th>

                        <td>

                            {{
                                number_format(
                                    $write_off->write_off_amount,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Recovered Amount
                        </th>

                        <td>

                            {{
                                number_format(
                                    $write_off->recovered_amount,
                                    2
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Net Loss Amount
                        </th>

                        <td>

                            <span class="label label-danger">

                                {{
                                    number_format(
                                        $write_off->loss_amount,
                                        2
                                    )
                                }}

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Provisioning Score
                        </th>

                        <td>

                            <span class="label label-warning">

                                {{
                                    $write_off->provisioning_score
                                }}%

                            </span>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

    <!-- ================================================ -->
    <!-- GOVERNANCE -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-warning">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Recovery Closure Governance

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Recovery Closure Status
                        </th>

                        <td>

                            {{
                                ucfirst(
                                    $write_off->recovery_closure_status
                                )
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Board Governance
                        </th>

                        <td>

                            Enabled

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Audit Visibility
                        </th>

                        <td>

                            Active

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Autonomous Monitoring
                        </th>

                        <td>

                            Running

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

            Write-Off Governance Notes

        </h3>

    </div>

    <div class="box-body">

        <div class="well"
             style="min-height:180px;
                    font-size:15px;">

            {{ $write_off->notes }}

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- TIMELINE -->
<!-- ===================================================== -->

<div class="box box-success">

    <div class="box-header with-border">

        <h3 class="box-title">

            Recovery Closure & Approval Timeline

        </h3>

    </div>

    <div class="box-body">

        <ul class="timeline">

            <li>

                <i class="fa fa-times-circle bg-red"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $write_off->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Write-Off Governance Initiated

                    </h3>

                    <div class="timeline-body">

                        Enterprise write-off workflow created.

                    </div>

                </div>

            </li>

            @if($write_off->requires_board_approval)

            <li>

                <i class="fa fa-users bg-yellow"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $write_off->created_at }}

                    </span>

                    <h3 class="timeline-header">

                        Board Approval Escalated

                    </h3>

                    <div class="timeline-body">

                        Write-off escalated to board governance workflow.

                    </div>

                </div>

            </li>

            @endif

            @if($write_off->approval_status == 'approved')

            <li>

                <i class="fa fa-check bg-green"></i>

                <div class="timeline-item">

                    <span class="time">

                        <i class="fa fa-clock-o"></i>

                        {{ $write_off->approved_at }}

                    </span>

                    <h3 class="timeline-header">

                        Write-Off Approved

                    </h3>

                    <div class="timeline-body">

                        Write-off approved and recovery closure finalized.

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

                    Write-Off

                </h4>

                <p>

                    Governed

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-times-circle"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-yellow">

            <div class="inner">

                <h4>

                    Provisioning

                </h4>

                <p>

                    Monitored

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-line-chart"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-aqua">

            <div class="inner">

                <h4>

                    Audit

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

        <div class="small-box bg-green">

            <div class="inner">

                <h4>

                    Closure

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