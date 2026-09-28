@extends('layouts.app')

@section('title', 'Loan Application Details')

@section('content')

<section class="content-header">

    <h1>
        Loan Application Details
        <small>
            Enterprise Underwriting & Recovery Intelligence
        </small>
    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- KPI OVERVIEW -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box bg-aqua">

                <span class="info-box-icon">

                    <i class="fa fa-file-text"></i>

                </span>

                <div class="info-box-content">

                    <span class="info-box-text">

                        Application No

                    </span>

                    <span class="info-box-number"
                          style="font-size:14px;">

                        {{ $application->application_no }}

                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box bg-green">

                <span class="info-box-icon">

                    <i class="fa fa-money"></i>

                </span>

                <div class="info-box-content">

                    <span class="info-box-text">

                        Loan Amount

                    </span>

                    <span class="info-box-number">

                        {{ number_format($application->principal_amount, 2) }}

                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box bg-yellow">

                <span class="info-box-icon">

                    <i class="fa fa-warning"></i>

                </span>

                <div class="info-box-content">

                    <span class="info-box-text">

                        Risk Level

                    </span>

                    <span class="info-box-number">

                        {{ ucfirst($application->risk_level ?? 'low') }}

                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">

            <div class="info-box bg-red">

                <span class="info-box-icon">

                    <i class="fa fa-shield"></i>

                </span>

                <div class="info-box-content">

                    <span class="info-box-text">

                        Workflow

                    </span>

                    <span class="info-box-number"
                          style="font-size:14px;">

                        {{ ucwords(str_replace('_', ' ', $application->workflow_stage ?? 'draft')) }}

                    </span>

                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- MAIN PROFILE -->
    <!-- ===================================================== -->

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">

                Application Profile

            </h3>

            <!-- ============================================= -->
            <!-- ACTIONS -->
            <!-- ============================================= -->

            <div class="pull-right">

                @if($application->status == 'draft')

                    <form method="POST"
                          action="/loan/loan-applications/{{ $application->id }}/approve"
                          style="display:inline;">

                        @csrf

                        <button type="submit"
                                class="btn btn-success">

                            <i class="fa fa-check"></i>

                            Approve

                        </button>

                    </form>

                    <form method="POST"
                          action="/loan/loan-applications/{{ $application->id }}/reject"
                          style="display:inline;">

                        @csrf

                        <button type="submit"
                                class="btn btn-danger">

                            <i class="fa fa-times"></i>

                            Reject

                        </button>

                    </form>

                @elseif($application->status == 'approved')

                    <form method="POST"
                          action="/loan/loan-applications/{{ $application->id }}/disburse"
                          style="display:inline;">

                        @csrf

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="fa fa-money"></i>

                            Disburse Loan

                        </button>

                    </form>

                @endif

            </div>

        </div>

        <div class="box-body">

            <div class="row">

                <!-- ========================================= -->
                <!-- CUSTOMER & PRODUCT -->
                <!-- ========================================= -->

                <div class="col-md-6">

                    <table class="table table-bordered">

                        <tr>

                            <th width="40%">
                                Customer
                            </th>

                            <td>

                                {{ optional($application->customer)->name }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Loan Product
                            </th>

                            <td>

                                {{ optional($application->loanProduct)->name }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Principal Amount
                            </th>

                            <td>

                                {{ number_format($application->principal_amount, 2) }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Interest Rate
                            </th>

                            <td>

                                {{ $application->interest_rate }} %

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Interest Type
                            </th>

                            <td>

                                {{ ucfirst($application->interest_type) }}

                            </td>

                        </tr>

                    </table>

                </div>

                <!-- ========================================= -->
                <!-- APPLICATION DETAILS -->
                <!-- ========================================= -->

                <div class="col-md-6">

                    <table class="table table-bordered">

                        <tr>

                            <th width="40%">
                                Tenure
                            </th>

                            <td>

                                {{ $application->tenure }}

                                {{ ucfirst($application->tenure_type) }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Installment Frequency
                            </th>

                            <td>

                                {{ ucfirst($application->installment_frequency) }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Application Date
                            </th>

                            <td>

                                {{ $application->application_date }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Status
                            </th>

                            <td>

                                @if($application->status == 'draft')

                                    <span class="label label-warning">
                                        Draft
                                    </span>

                                @elseif($application->status == 'approved')

                                    <span class="label label-success">
                                        Approved
                                    </span>

                                @elseif($application->status == 'rejected')

                                    <span class="label label-danger">
                                        Rejected
                                    </span>

                                @elseif($application->status == 'disbursed')

                                    <span class="label label-primary">
                                        Disbursed
                                    </span>

                                @else

                                    <span class="label label-default">

                                        {{ ucfirst($application->status) }}

                                    </span>

                                @endif

                            </td>

                        </tr>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- UNDERWRITING -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="box box-warning">

                <div class="box-header with-border">

                    <h3 class="box-title">

                        Underwriting Summary

                    </h3>

                </div>

                <div class="box-body">

                    <table class="table table-bordered">

                        <tr>

                            <th width="40%">
                                Risk Level
                            </th>

                            <td>

                                @if(($application->risk_level ?? 'low') == 'critical')

                                    <span class="label label-danger">
                                        Critical
                                    </span>

                                @elseif(($application->risk_level ?? 'low') == 'high')

                                    <span class="label label-warning">
                                        High
                                    </span>

                                @elseif(($application->risk_level ?? 'low') == 'medium')

                                    <span class="label label-primary">
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
                                Compliance Status
                            </th>

                            <td>

                                {{ ucfirst(str_replace('_', ' ', $application->compliance_status ?? 'pending_review')) }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Collection Priority
                            </th>

                            <td>

                                {{ ucfirst($application->collection_priority ?? 'normal') }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Workflow Stage
                            </th>

                            <td>

                                {{ ucwords(str_replace('_', ' ', $application->workflow_stage ?? 'application_submitted')) }}

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

                        Underwriting Notes

                    </h3>

                </div>

                <div class="box-body">

                    <div class="well"
                         style="min-height:180px;">

                        {{ $application->notes ?? 'No notes available.' }}

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- COLLATERAL & GUARANTOR -->
    <!-- ===================================================== -->

    <div class="row">

        <!-- ================================================ -->
        <!-- COLLATERAL -->
        <!-- ================================================ -->

        <div class="col-md-6">

            <div class="box box-danger">

                <div class="box-header with-border">

                    <h3 class="box-title">

                        Collateral Information

                    </h3>

                </div>

                <div class="box-body">

                    <table class="table table-bordered">

                        <tr>

                            <th width="40%">
                                Asset Name
                            </th>

                            <td>

                                {{ $application->asset_name ?? '-' }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Estimated Value
                            </th>

                            <td>

                                @if(!empty($application->estimated_value))

                                    {{ number_format($application->estimated_value, 2) }}

                                @else

                                    -

                                @endif

                            </td>

                        </tr>

                    </table>

                </div>

            </div>

        </div>

        <!-- ================================================ -->
        <!-- GUARANTOR -->
        <!-- ================================================ -->

        <div class="col-md-6">

            <div class="box box-success">

                <div class="box-header with-border">

                    <h3 class="box-title">

                        Guarantor Information

                    </h3>

                </div>

                <div class="box-body">

                    <table class="table table-bordered">

                        <tr>

                            <th width="40%">
                                Guarantor Name
                            </th>

                            <td>

                                {{ $application->guarantor_name ?? '-' }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Guarantor Phone
                            </th>

                            <td>

                                {{ $application->guarantor_phone ?? '-' }}

                            </td>

                        </tr>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- ENTERPRISE RECOVERY INSIGHTS -->
    <!-- ===================================================== -->

    <div class="box box-success">

        <div class="box-header with-border">

            <h3 class="box-title">

                Recovery Intelligence & Governance

            </h3>

        </div>

        <div class="box-body">

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
                                Risk Monitoring
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
                                Compliance
                            </h4>

                            <p>
                                Monitored
                            </p>

                        </div>

                        <div class="icon">

                            <i class="fa fa-check-circle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection