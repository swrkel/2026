@extends('layouts.app')

@section('title', 'Active Loan Portfolio')

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
        font-size: 42px;
        opacity: 0.10;
        margin-top: -50px;
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

        Enterprise Active Loan Portfolio

        <small>
            Portfolio Governance • Delinquency Intelligence • Recovery Oversight
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- Executive KPI Cards -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-2 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2>

                    {{ number_format($total_loans) }}

                </h2>

                <p>
                    Total Loans
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-bank"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-2 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ number_format($active_loans) }}

                </h2>

                <p>
                    Active Loans
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-check"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-2 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ number_format($written_off_loans) }}

                </h2>

                <p>
                    Written Off
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#f39c12;">

                <h2 style="font-size:22px;">

                    {{ number_format($total_portfolio, 2) }}

                </h2>

                <p>
                    Total Portfolio Exposure
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#605ca8;">

                <h2 style="font-size:22px;">

                    {{ number_format($total_collections, 2) }}

                </h2>

                <p>
                    Recovery Collections
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-money"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Governance Intelligence -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-warning"></i>

                    PAR & Delinquency Monitoring

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Overdue Amount
                            </th>

                            <td>

                                {{ number_format($overdue_amount, 2) }}

                            </td>

                        </tr>

                        <tr>

                            <th>
                                Portfolio At Risk
                            </th>

                            <td>

                                <span class="label label-danger">

                                    {{ number_format($par_percentage, 2) }}%

                                </span>

                            </td>

                        </tr>

                    </tbody>

                </table>

                <div class="progress-group">

                    <span>
                        Delinquency Pressure
                    </span>

                    <span class="pull-right">

                        {{ number_format($par_percentage, 2) }}%

                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-danger"
                             style="width: {{ $par_percentage }}%">
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ================================================= -->
        <!-- Recovery Intelligence -->
        <!-- ================================================= -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-line-chart"></i>

                    Workforce Recovery Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Collections Efficiency
                    </span>

                    <span class="pull-right">

                        {{ number_format($total_collections, 2) }}

                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-success"
                             style="width:
                             {{
                                $total_portfolio > 0
                                ? ($total_collections / $total_portfolio) * 100
                                : 0
                             }}%">
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

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Portfolio Table -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-database"></i>

            Enterprise Active Loan Portfolio

        </div>

        <!-- ============================================= -->
        <!-- Search & Filter -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:15px;">

            <div class="col-md-4">

                <input type="text"
                       id="loanSearch"
                       class="form-control"
                       placeholder="Search Loans">

            </div>

            <div class="col-md-3">

                <select id="statusFilter"
                        class="form-control">

                    <option value="">
                        All Statuses
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="delinquent">
                        Delinquent
                    </option>

                    <option value="written_off">
                        Written Off
                    </option>

                </select>

            </div>

        </div>

        <!-- ============================================= -->
        <!-- Table -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table modern-table table-striped"
                   id="loanTable">

                <thead>

                    <tr>

                        <th>Loan No</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Disbursement</th>
                        <th>Principal</th>
                        <th>Outstanding</th>
                        <th>Risk</th>
                        <th>Status</th>
                        <th width="220">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($loans as $loan)

                        <tr
                            data-status="{{ $loan->status }}">

                            <td>

                                <strong>

                                    {{ $loan->loan_no }}

                                </strong>

                            </td>

                            <td>

                                {{ optional($loan->customer)->name }}

                            </td>

                            <td>

                                {{ optional($loan->loanProduct)->name }}

                            </td>

                            <td>

                                {{ $loan->disbursement_date }}

                            </td>

                            <td>

                                {{ number_format($loan->principal_amount, 2) }}

                            </td>

                            <td>

                                {{
                                    number_format(
                                        ($loan->principal_outstanding ?? 0)
                                        +
                                        ($loan->interest_outstanding ?? 0)
                                        +
                                        ($loan->penalty_outstanding ?? 0),
                                        2
                                    )
                                }}

                            </td>

                            <!-- =============================== -->
                            <!-- Risk -->
                            <!-- =============================== -->

                            <td>

                                @if(($loan->risk_level ?? 'low') == 'critical')

                                    <span class="label label-danger">
                                        Critical
                                    </span>

                                @elseif(($loan->risk_level ?? 'low') == 'high')

                                    <span class="label label-warning">
                                        High
                                    </span>

                                @elseif(($loan->risk_level ?? 'low') == 'medium')

                                    <span class="label label-primary">
                                        Medium
                                    </span>

                                @else

                                    <span class="label label-success">
                                        Low
                                    </span>

                                @endif

                            </td>

                            <!-- =============================== -->
                            <!-- Status -->
                            <!-- =============================== -->

                            <td>

                                @if($loan->status == 'active')

                                    <span class="label label-success">
                                        Active
                                    </span>

                                @elseif($loan->status == 'delinquent')

                                    <span class="label label-warning">
                                        Delinquent
                                    </span>

                                @elseif($loan->status == 'written_off')

                                    <span class="label label-danger">
                                        Written Off
                                    </span>

                                @else

                                    <span class="label label-default">

                                        {{ ucfirst($loan->status) }}

                                    </span>

                                @endif

                            </td>

                            <!-- =============================== -->
                            <!-- Actions -->
                            <!-- =============================== -->

                            <td>

                                <a href="/loan/loans/{{ $loan->id }}/show"
                                   class="btn btn-xs btn-primary">

                                    <i class="fa fa-eye"></i>

                                    View

                                </a>

                                @if($loan->status != 'written_off')

                                    <form method="POST"
                                          action="/loan/loans/{{ $loan->id }}/write-off"
                                          style="display:inline;">

                                        @csrf

                                        <button type="submit"
                                                class="btn btn-xs btn-danger">

                                            <i class="fa fa-ban"></i>

                                            Write-Off

                                        </button>

                                    </form>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="9"
                                class="text-center">

                                No loans found

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $loans->links() }}

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

    $('#loanSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#loanTable tbody tr').filter(function() {

            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) > -1
            );

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Status Filter
    |--------------------------------------------------------------------------
    */

    $('#statusFilter').on('change', function() {

        var status = $(this).val();

        $('#loanTable tbody tr').each(function() {

            if (
                status == '' ||
                $(this).data('status') == status
            ) {

                $(this).show();

            } else {

                $(this).hide();
            }

        });

    });

});

</script>

@endsection