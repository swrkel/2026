@extends('layouts.app')

@section('title', 'Repayment Operations Center')

@section('content')

<style>

    .dashboard-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        border-top: 4px solid #3c8dbc;
        min-height: 135px;
    }

    .dashboard-card h2 {
        font-size: 28px;
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
        font-size: 40px;
        opacity: 0.10;
        margin-top: -45px;
    }

    .governance-box {
        background: #ffffff;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    .governance-title {
        font-size: 22px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 20px;
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

</style>

<section class="content-header">

    <h1>

        Enterprise Repayment Operations Center

        <small>
            Treasury Intelligence • Recovery Monitoring • Collections Governance
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- KPI DASHBOARD -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-4 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ number_format($total_collections, 2) }}

                </h2>

                <p>
                    Total Recovery Collections
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-money"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-4 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2>

                    {{ number_format($today_collections, 2) }}

                </h2>

                <p>
                    Today's Collections
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-calendar"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-4 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2>

                    {{ $repayment_count }}

                </h2>

                <p>
                    Total Transactions
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Recovery Intelligence -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-line-chart"></i>

                    Recovery Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Collections Performance
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
                        Recovery Monitoring
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
                        Delinquency Cure Workflow
                    </span>

                    <span class="pull-right">
                        Enabled
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-warning"
                             style="width:100%">
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ================================================= -->
        <!-- Workforce -->
        <!-- ================================================= -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-users"></i>

                    Workforce Optimization

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Collections Engine
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Recovery Escalation
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Governance Monitoring
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Autonomous Intelligence
                            </th>

                            <td>
                                Running
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Repayment Transactions -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Repayment Transactions

        </div>

        <!-- ============================================= -->
        <!-- Search -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="repaymentSearch"
                       class="form-control"
                       placeholder="Search Transactions">

            </div>

        </div>

        <!-- ============================================= -->
        <!-- Table -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table modern-table table-striped"
                   id="repaymentTable">

                <thead>

                    <tr>

                        <th>Payment Date</th>
                        <th>Loan No</th>
                        <th>Installment</th>
                        <th>Amount</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>Penalty</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th width="120">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($repayments as $repayment)

                    <tr>

                        <td>

                            {{ $repayment->payment_date }}

                        </td>

                        <td>

                            {{ optional($repayment->loan)->loan_no }}

                        </td>

                        <td>

                            {{
                                optional($repayment->schedule)
                                    ->installment_no
                            }}

                        </td>

                        <td>

                            <strong>

                                {{ number_format($repayment->amount, 2) }}

                            </strong>

                        </td>

                        <td>

                            {{
                                number_format(
                                    $repayment->principal_paid ?? 0,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $repayment->interest_paid ?? 0,
                                    2
                                )
                            }}

                        </td>

                        <td>

                            {{
                                number_format(
                                    $repayment->penalty_paid ?? 0,
                                    2
                                )
                            }}

                        </td>

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

                        <td>

                            <span class="label label-success">

                                Processed

                            </span>

                        </td>

                        <td>

                            <a href="/loan/loan-repayments/{{ $repayment->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="10"
                            class="text-center">

                            No repayment transactions found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- ============================================= -->
        <!-- Pagination -->
        <!-- ============================================= -->

        <div class="text-right">

            {{ $repayments->links() }}

        </div>

    </div>

</section>

@endsection

@section('javascript')

<script>

$(document).ready(function() {

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    $('#repaymentSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#repaymentTable tbody tr').filter(function() {

            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) > -1
            );

        });

    });

});

</script>

@endsection