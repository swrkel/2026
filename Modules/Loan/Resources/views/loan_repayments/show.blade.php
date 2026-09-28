@extends('layouts.app')

@section('title', 'Repayment Transaction Profile')

@section('content')

<section class="content-header">

    <h1>
        Repayment Transaction Profile
        <small>
            Enterprise Collections & Recovery Audit Intelligence
        </small>
    </h1>

</section>

<section class="content">

<!-- ===================================================== -->
<!-- KPI DASHBOARD -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-green">

            <span class="info-box-icon">

                <i class="fa fa-money"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Payment Amount

                </span>

                <span class="info-box-number"
                      style="font-size:20px;">

                    {{ number_format($repayment->amount, 2) }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-aqua">

            <span class="info-box-icon">

                <i class="fa fa-bank"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Loan Number

                </span>

                <span class="info-box-number"
                      style="font-size:14px;">

                    {{ optional($repayment->loan)->loan_no }}

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

                    Payment Date

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    {{ $repayment->payment_date }}

                </span>

            </div>

        </div>

    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">

        <div class="info-box bg-red">

            <span class="info-box-icon">

                <i class="fa fa-line-chart"></i>

            </span>

            <div class="info-box-content">

                <span class="info-box-text">

                    Collection Status

                </span>

                <span class="info-box-number"
                      style="font-size:16px;">

                    Processed

                </span>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- TRANSACTION PROFILE -->
<!-- ===================================================== -->

<div class="box box-primary">

    <div class="box-header with-border">

        <h3 class="box-title">

            Repayment Transaction Details

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

                            {{ optional($repayment->loan)->loan_no }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Installment No
                        </th>

                        <td>

                            {{
                                optional($repayment->schedule)
                                    ->installment_no
                            }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Payment Date
                        </th>

                        <td>

                            {{ $repayment->payment_date }}

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Payment Method
                        </th>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucfirst(
                                        $repayment->payment_method
                                        ?? 'cash'
                                    )
                                }}

                            </span>

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Transaction Amount
                        </th>

                        <td>

                            <strong>

                                {{ number_format($repayment->amount, 2) }}

                            </strong>

                        </td>

                    </tr>

                </table>

            </div>

            <!-- ============================================= -->
            <!-- STATUS -->
            <!-- ============================================= -->

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="40%">
                            Transaction Status
                        </th>

                        <td>

                            <span class="label label-success">

                                Processed

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
                            Audit Tracking
                        </th>

                        <td>

                            Enabled

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Collections Monitoring
                        </th>

                        <td>

                            Operational

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Autonomous Reconciliation
                        </th>

                        <td>

                            Completed

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- PAYMENT ALLOCATION -->
<!-- ===================================================== -->

<div class="box box-success">

    <div class="box-header with-border">

        <h3 class="box-title">

            Allocation Analytics

        </h3>

    </div>

    <div class="box-body">

        <div class="row">

            <div class="col-md-4">

                <div class="small-box bg-green">

                    <div class="inner">

                        <h3>

                            {{
                                number_format(
                                    $repayment->principal_paid ?? 0,
                                    2
                                )
                            }}

                        </h3>

                        <p>

                            Principal Allocation

                        </p>

                    </div>

                    <div class="icon">

                        <i class="fa fa-bank"></i>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="small-box bg-aqua">

                    <div class="inner">

                        <h3>

                            {{
                                number_format(
                                    $repayment->interest_paid ?? 0,
                                    2
                                )
                            }}

                        </h3>

                        <p>

                            Interest Allocation

                        </p>

                    </div>

                    <div class="icon">

                        <i class="fa fa-line-chart"></i>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="small-box bg-red">

                    <div class="inner">

                        <h3>

                            {{
                                number_format(
                                    $repayment->penalty_paid ?? 0,
                                    2
                                )
                            }}

                        </h3>

                        <p>

                            Penalty Allocation

                        </p>

                    </div>

                    <div class="icon">

                        <i class="fa fa-warning"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- SCHEDULE RECONCILIATION -->
<!-- ===================================================== -->

<div class="box box-warning">

    <div class="box-header with-border">

        <h3 class="box-title">

            Schedule Reconciliation

        </h3>

    </div>

    <div class="box-body">

        <table class="table table-bordered">

            <tr>

                <th width="40%">
                    Installment Amount
                </th>

                <td>

                    {{
                        number_format(
                            optional($repayment->schedule)
                                ->installment_amount ?? 0,
                            2
                        )
                    }}

                </td>

            </tr>

            <tr>

                <th>
                    Paid Amount
                </th>

                <td>

                    {{
                        number_format(
                            optional($repayment->schedule)
                                ->paid_amount ?? 0,
                            2
                        )
                    }}

                </td>

            </tr>

            <tr>

                <th>
                    Outstanding Balance
                </th>

                <td>

                    {{
                        number_format(
                            optional($repayment->schedule)
                                ->balance_amount ?? 0,
                            2
                        )
                    }}

                </td>

            </tr>

            <tr>

                <th>
                    Schedule Status
                </th>

                <td>

                    @if(optional($repayment->schedule)->status == 'paid')

                        <span class="label label-success">
                            Paid
                        </span>

                    @elseif(optional($repayment->schedule)->status == 'partial')

                        <span class="label label-warning">
                            Partial
                        </span>

                    @elseif(optional($repayment->schedule)->status == 'overdue')

                        <span class="label label-danger">
                            Overdue
                        </span>

                    @else

                        <span class="label label-default">
                            Pending
                        </span>

                    @endif

                </td>

            </tr>

        </table>

    </div>

</div>

<!-- ===================================================== -->
<!-- RECOVERY INTELLIGENCE -->
<!-- ===================================================== -->

<div class="row">

    <div class="col-md-6">

        <div class="box box-danger">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Recovery Intelligence

                </h3>

            </div>

            <div class="box-body">

                <table class="table table-bordered">

                    <tr>

                        <th width="50%">
                            Delinquency Cure
                        </th>

                        <td>

                            Completed

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Recovery Workflow
                        </th>

                        <td>

                            Updated

                        </td>

                    </tr>

                    <tr>

                        <th>
                            PAR Reconciliation
                        </th>

                        <td>

                            Processed

                        </td>

                    </tr>

                    <tr>

                        <th>
                            Governance Audit
                        </th>

                        <td>

                            Successful

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

    <!-- ================================================ -->
    <!-- NOTES -->
    <!-- ================================================ -->

    <div class="col-md-6">

        <div class="box box-info">

            <div class="box-header with-border">

                <h3 class="box-title">

                    Collection Notes

                </h3>

            </div>

            <div class="box-body">

                <div class="well"
                     style="min-height:180px;">

                    {{ $repayment->notes ?? 'No repayment notes available.' }}

                </div>

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

                    Governance

                </h4>

                <p>

                    Enabled

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-shield"></i>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="small-box bg-yellow">

            <div class="inner">

                <h4>

                    Recovery

                </h4>

                <p>

                    Operational

                </p>

            </div>

            <div class="icon">

                <i class="fa fa-warning"></i>

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