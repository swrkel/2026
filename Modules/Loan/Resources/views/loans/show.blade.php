@extends('layouts.app')

@section('title', 'Loan Account Profile')

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
        font-size: 26px;
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

    .profile-label {
        font-weight: 700;
        color: #34495e;
        width: 40%;
    }

</style>

<section class="content-header">

    <h1>

        Enterprise Loan Servicing Workspace

        <small>
            Recovery Intelligence • Servicing Governance • Collections Operations
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- Executive KPI Cards -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00c0ef;">

                <h2 style="font-size:18px;">

                    {{ $loan->loan_no }}

                </h2>

                <p>
                    Loan Number
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-bank"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#00a65a;">

                <h2>

                    {{ number_format($total_paid, 2) }}

                </h2>

                <p>
                    Total Paid
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-money"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#dd4b39;">

                <h2>

                    {{ number_format($total_outstanding, 2) }}

                </h2>

                <p>
                    Outstanding Balance
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="dashboard-card"
                 style="border-top-color:#605ca8;">

                <h2>

                    {{ number_format($collection_efficiency, 2) }}%

                </h2>

                <p>
                    Collection Efficiency
                </p>

                <div class="dashboard-icon">
                    <i class="fa fa-line-chart"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Profile -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-box">

                <div class="governance-title">

                    <i class="fa fa-user"></i>

                    Borrower & Product Profile

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>
                            <td class="profile-label">Customer</td>
                            <td>{{ optional($loan->customer)->name }}</td>
                        </tr>

                        <tr>
                            <td class="profile-label">Loan Product</td>
                            <td>{{ optional($loan->loanProduct)->name }}</td>
                        </tr>

                        <tr>
                            <td class="profile-label">Principal Amount</td>
                            <td>{{ number_format($loan->principal_amount, 2) }}</td>
                        </tr>

                        <tr>
                            <td class="profile-label">Interest Rate</td>
                            <td>{{ $loan->interest_rate }} %</td>
                        </tr>

                        <tr>
                            <td class="profile-label">Risk Level</td>
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
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- ================================================= -->
        <!-- Loan Governance -->
        <!-- ================================================= -->

        <div class="col-md-6">

            <div class="governance-box">

                <div class="row">

                    <div class="col-md-8">

                        <div class="governance-title">

                            <i class="fa fa-shield"></i>

                            Loan Governance

                        </div>

                    </div>

                    <div class="col-md-4 text-right">

                        @if($loan->status != 'written_off')

                        <form method="POST"
                              action="/loan/loans/{{ $loan->id }}/write-off">

                            @csrf

                            <button type="submit"
                                    class="btn btn-danger">

                                <i class="fa fa-ban"></i>

                                Write-Off

                            </button>

                        </form>

                        @endif

                    </div>

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>
                            <td class="profile-label">Tenure</td>
                            <td>
                                {{ $loan->tenure }}
                                {{ ucfirst($loan->tenure_type) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="profile-label">Installment Frequency</td>
                            <td>
                                {{ ucfirst($loan->installment_frequency) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="profile-label">Disbursement Date</td>
                            <td>
                                {{ $loan->disbursement_date }}
                            </td>
                        </tr>

                        <tr>
                            <td class="profile-label">Outstanding Balance</td>
                            <td>
                                {{ number_format($total_outstanding, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td class="profile-label">Status</td>
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

                                @elseif($loan->status == 'closed')

                                    <span class="label label-primary">
                                        Closed
                                    </span>

                                @else

                                    <span class="label label-default">
                                        {{ ucfirst($loan->status) }}
                                    </span>

                                @endif

                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Repayment Schedule -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-calendar"></i>

            Repayment Schedule & Collection Operations

        </div>

        <div class="table-responsive">

            <table class="table modern-table table-striped">

                <thead>

                    <tr>

                        <th>#</th>
                        <th>Due Date</th>
                        <th>Installment</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th width="260">Collection Workflow</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($loan->repaymentSchedules as $schedule)

                    <tr>

                        <td>{{ $schedule->installment_no }}</td>

                        <td>{{ $schedule->due_date }}</td>

                        <td>

                            {{ number_format($schedule->installment_amount, 2) }}

                        </td>

                        <td>

                            {{ number_format($schedule->paid_amount, 2) }}

                        </td>

                        <td>

                            {{ number_format($schedule->balance_amount, 2) }}

                        </td>

                        <td>

                            @if($schedule->status == 'paid')

                                <span class="label label-success">
                                    Paid
                                </span>

                            @elseif($schedule->status == 'partial')

                                <span class="label label-warning">
                                    Partial
                                </span>

                            @elseif($schedule->status == 'overdue')

                                <span class="label label-danger">
                                    Overdue
                                </span>

                            @else

                                <span class="label label-default">
                                    Pending
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($schedule->status != 'paid')

                            <form method="POST"
                                  action="/loan/loan-repayments/store">

                                @csrf

                                <input type="hidden"
                                       name="schedule_id"
                                       value="{{ $schedule->id }}">

                                <div class="row">

                                    <div class="col-md-5">

                                        <input type="date"
                                               name="payment_date"
                                               class="form-control"
                                               required>

                                    </div>

                                    <div class="col-md-5">

                                        <input type="number"
                                               step="0.01"
                                               name="amount"
                                               class="form-control"
                                               placeholder="Amount"
                                               required>

                                    </div>

                                    <div class="col-md-2">

                                        <button type="submit"
                                                class="btn btn-primary btn-sm">

                                            <i class="fa fa-money"></i>

                                        </button>

                                    </div>

                                </div>

                            </form>

                            @endif

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Collections Timeline -->
    <!-- ===================================================== -->

    <div class="governance-box">

        <div class="governance-title">

            <i class="fa fa-history"></i>

            Collections & Recovery Timeline

        </div>

        <div class="table-responsive">

            <table class="table modern-table table-striped">

                <thead>

                    <tr>

                        <th>Date</th>
                        <th>Officer</th>
                        <th>Action</th>
                        <th>Collection Note</th>
                        <th>Promise To Pay</th>
                        <th>Follow Up</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse(
                        $loan->collectionNotes
                        ->sortByDesc('created_at')
                        as $note
                    )

                    <tr>

                        <td>{{ $note->created_at }}</td>

                        <td>

                            {{ optional($note->createdBy)->username }}

                        </td>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $note->collection_type
                                        )
                                    )
                                }}

                            </span>

                        </td>

                        <td>{{ $note->note }}</td>

                        <td>{{ $note->promise_to_pay_date }}</td>

                        <td>{{ $note->follow_up_date }}</td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="6"
                            class="text-center">

                            No collection history found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection