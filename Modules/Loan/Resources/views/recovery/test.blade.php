@extends('layouts.app')

@section('title', 'Enterprise Loan Recovery Dashboard')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.loan-dashboard-style')

<style>

.recovery-metric-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border-left: 6px solid #3498db;
}

.recovery-metric-card h2 {
    margin: 10px 0 0;
    font-size: 34px;
    font-weight: 700;
}

.recovery-metric-card .title {
    font-size: 14px;
    color: #777;
    font-weight: 600;
    text-transform: uppercase;
}

.metric-warning {
    border-left-color: #f39c12;
}

.metric-danger {
    border-left-color: #e74c3c;
}

.metric-success {
    border-left-color: #27ae60;
}

.metric-neutral {
    border-left-color: #34495e;
}

.recovery-form textarea,
.recovery-form input[type="date"],
.recovery-form input[type="number"] {

    width: 100%;
    padding: 10px;
    border: 1px solid #dcdde1;
    border-radius: 8px;
    margin-bottom: 10px;
}

.recovery-form textarea {
    min-height: 80px;
}

.recovery-btn {

    background: #2c3e50;
    color: #fff;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
}

.recovery-btn:hover {
    opacity: 0.9;
}

.customer-box {
    line-height: 1.7;
}

.customer-name {
    font-weight: 700;
}

</style>

<section class="content-header">

    <h1>
        Enterprise Loan Recovery Dashboard
    </h1>

    <div class="page-subtitle">
        Recovery operations, overdue management, collection governance and follow-up intelligence
    </div>

</section>

<section class="content">

    @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif

    @if(session('error'))

        <div class="alert alert-danger">
            {{ session('error') }}
        </div>

    @endif

    {{-- KPI ROW --}}
    <div class="row">

        <div class="col-md-2">

            <div class="recovery-metric-card metric-neutral">

                <div class="title">
                    Total Loans
                </div>

                <h2>
                    {{ number_format($totalLoans ?? 0) }}
                </h2>

            </div>

        </div>

        <div class="col-md-2">

            <div class="recovery-metric-card">

                <div class="title">
                    Recovery Remarks
                </div>

                <h2>
                    {{ number_format($recoveryRemarks ?? 0) }}
                </h2>

            </div>

        </div>

        <div class="col-md-2">

            <div class="recovery-metric-card metric-warning">

                <div class="title">
                    Overdue Loans
                </div>

                <h2>
                    {{ number_format($overdueLoans ?? 0) }}
                </h2>

            </div>

        </div>

        <div class="col-md-2">

            <div class="recovery-metric-card metric-warning">

                <div class="title">
                    30+ DPD
                </div>

                <h2>
                    {{ number_format($dpd30 ?? 0) }}
                </h2>

            </div>

        </div>

        <div class="col-md-2">

            <div class="recovery-metric-card metric-danger">

                <div class="title">
                    60+ DPD
                </div>

                <h2>
                    {{ number_format($dpd60 ?? 0) }}
                </h2>

            </div>

        </div>

        <div class="col-md-2">

            <div class="recovery-metric-card metric-danger">

                <div class="title">
                    90+ DPD
                </div>

                <h2>
                    {{ number_format($dpd90 ?? 0) }}
                </h2>

            </div>

        </div>

    </div>

    {{-- OPERATIONS SUMMARY --}}
    <div class="analytics-box">

        <div class="analytics-title">
            Recovery Operations Summary
        </div>

        <div class="row">

            <div class="col-md-3">

                <div class="portfolio-card">

                    <div class="title">
                        Total Recovery Actions
                    </div>

                    <div class="value">
                        {{ number_format($totalRecoveryActions ?? 0) }}
                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="portfolio-card">

                    <div class="title">
                        Promise To Pay Cases
                    </div>

                    <div class="value">
                        {{ number_format($promiseToPayCases ?? 0) }}
                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="portfolio-card">

                    <div class="title">
                        Follow-Ups Today
                    </div>

                    <div class="value">
                        {{ number_format($followUpsToday ?? 0) }}
                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="portfolio-card">

                    <div class="title">
                        Active Overdue Cases
                    </div>

                    <div class="value">
                        {{ number_format($activeOverdueCases ?? 0) }}
                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- RECOVERY WORK QUEUE --}}
    <div class="analytics-box">

        <div class="analytics-title">
            Recovery Work Queue
        </div>

        <div class="table-responsive">

            <table class="table executive-table">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Customer</th>
                        <th>Application</th>
                        <th>DPD</th>
                        <th>Status</th>
                        <th width="420">Recovery Action</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($overdueLoanList as $loan)

                        <tr>

                            <td>
                                {{ $loan->id }}
                            </td>

                            <td>

                                <div class="customer-box">

                                    <div class="customer-name">
                                        {{ $loan->customer_name ?? 'N/A' }}
                                    </div>

                                    <div>
                                        {{ $loan->customer_mobile ?? '-' }}
                                    </div>

                                </div>

                            </td>

                            <td>
                                {{ $loan->application_no }}
                            </td>

                            <td>

                                @if($loan->dpd >= 90)

                                    <span class="status-badge status-warning">
                                        LEGAL — {{ $loan->dpd }} Days
                                    </span>

                                @elseif($loan->dpd >= 60)

                                    <span class="status-badge status-warning">
                                        CRITICAL — {{ $loan->dpd }} Days
                                    </span>

                                @elseif($loan->dpd >= 30)

                                    <span class="status-badge status-warning">
                                        WARNING — {{ $loan->dpd }} Days
                                    </span>

                                @else

                                    <span class="status-badge status-active">
                                        NORMAL — {{ $loan->dpd }} Days
                                    </span>

                                @endif

                            </td>

                            <td>

                                <span class="status-badge status-active">
                                    {{ strtoupper($loan->status) }}
                                </span>

                            </td>

                            <td>

                                <form method="POST"
                                      action="{{ route('loan.recovery.remark.store') }}"
                                      class="recovery-form">

                                    @csrf

                                    <input type="hidden"
                                           name="loan_application_id"
                                           value="{{ $loan->id }}">

                                    <textarea
                                        name="remark"
                                        placeholder="Enter recovery remark..."
                                        required></textarea>

                                    <input type="date"
                                           name="next_followup_date">

                                    <div style="margin-bottom:10px;">

                                        <label>

                                            <input type="checkbox"
                                                   name="promise_to_pay"
                                                   value="1">

                                            Promise To Pay

                                        </label>

                                    </div>

                                    <input type="number"
                                           step="0.01"
                                           name="promised_amount"
                                           placeholder="Promised Amount">

                                    <input type="date"
                                           name="promised_payment_date">

                                    <button type="submit"
                                            class="recovery-btn">

                                        Save Recovery Remark

                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6"
                                class="text-center text-muted">

                                No overdue loans found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px;">
            {{ $overdueLoanList->links() }}
        </div>

    </div>

    {{-- RECOVERY HISTORY --}}
    <div class="analytics-box">

        <div class="analytics-title">
            Recovery History
        </div>

        <div class="table-responsive">

            <table class="table executive-table">

                <thead>

                    <tr>

                        <th>Loan ID</th>
                        <th>Customer</th>
                        <th>Remark</th>
                        <th>Promise To Pay</th>
                        <th>Promised Amount</th>
                        <th>Follow-Up Date</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($recoveryHistory as $history)

                        <tr>

                            <td>
                                {{ $history->loan_application_id }}
                            </td>

                            <td>

                                <div class="customer-box">

                                    <div class="customer-name">
                                        {{ $history->customer_name ?? 'N/A' }}
                                    </div>

                                    <div>
                                        {{ $history->customer_mobile ?? '-' }}
                                    </div>

                                </div>

                            </td>

                            <td>
                                {{ $history->remark }}
                            </td>

                            <td>

                                @if($history->promise_to_pay)

                                    <span class="status-badge status-active">
                                        PROMISE TO PAY
                                    </span>

                                @else

                                    <span class="status-badge status-warning">
                                        NO PROMISE
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ number_format($history->promised_amount, 2) }}
                            </td>

                            <td>
                                {{ $history->next_followup_date }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6"
                                class="text-center text-muted">

                                No recovery history found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px;">
            {{ $recoveryHistory->links() }}
        </div>

    </div>

</section>

@endsection