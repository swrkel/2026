@extends('layouts.app')

@section('title', 'Promise To Pay Details')

@section('content')

@php
    $currency_precision = session('business.currency_precision', 2);

    $customer_name =
        optional($record->customer)->name
        ??
        optional(optional($record->loan)->customer)->name;

    $ptp_type_label = ucwords(str_replace('_', ' ', $record->ptp_type));
    $source_label = ucwords(str_replace('_', ' ', $record->source));
    $status_label = ucwords(str_replace('_', ' ', $record->status));
    $risk_label = ucwords(str_replace('_', ' ', $record->risk_level));
@endphp

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.loan-operational-dashboard-style')

<style>
.ptp-detail-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 22px;
    margin-bottom: 25px;
}

.ptp-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    min-height: 135px;
}

.ptp-card-title {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #52616b;
}

.ptp-value {
    font-size: 24px;
    font-weight: 800;
    color: #1f2d3d;
}

.ptp-section {
    background: #ffffff;
    border-radius: 18px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}

.ptp-section-title {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 20px;
    color: #1f2d3d;
}

.ptp-section-title i {
    margin-right: 7px;
}

.ptp-info-table td {
    padding: 11px 10px;
    border-bottom: 1px solid #f1f3f5;
    vertical-align: middle;
}

.ptp-label-cell {
    width: 40%;
    font-weight: 700;
    color: #52616b;
}

.ptp-note-box {
    background: #f8fafc;
    border-left: 5px solid #3498db;
    border-radius: 14px;
    padding: 18px 20px;
    color: #52616b;
    line-height: 1.7;
}

.ptp-empty-text {
    color: #999;
    font-style: italic;
}

@media(max-width: 991px) {
    .ptp-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="content-header">

    <h1>
        Promise To Pay Details

        <small>
            Recovery Commitment Governance
        </small>
    </h1>

</section>

<section class="content">

    <div class="loan-op-header-panel">

        <div class="row">

            <div class="col-md-8">

                <div class="loan-op-title">

                    <i class="fa fa-handshake-o"></i>

                    {{ $record->ptp_no }}

                </div>

                <div class="loan-op-subtitle">

                    Enterprise Promise-To-Pay Monitoring, Follow-Up Governance & Audit Trail

                </div>

            </div>

            <div class="col-md-4 text-right">

                <a href="{{ route('loan.promise.to.pay.index') }}"
                   class="loan-op-action-btn">

                    <i class="fa fa-arrow-left"></i>

                    Back To PTP List

                </a>

                @can('loan.promise_to_pay.update')
                    <a href="{{ route('loan.promise.to.pay.edit', $record->id) }}"
                       class="loan-op-action-btn"
                       style="margin-left:6px;">

                        <i class="fa fa-pencil"></i>

                        Edit

                    </a>
                @endcan

            </div>

        </div>

    </div>

    <div class="ptp-detail-grid">

        <div class="ptp-card">

            <div class="ptp-card-title">
                Promise Amount
            </div>

            <div class="ptp-value text-right">
                {{ number_format($record->promised_amount, $currency_precision) }}
            </div>

        </div>

        <div class="ptp-card">

            <div class="ptp-card-title">
                Promise Date
            </div>

            <div class="ptp-value">
                {{ $record->promised_payment_date }}
            </div>

        </div>

        <div class="ptp-card">

            <div class="ptp-card-title">
                Status
            </div>

            <div class="ptp-value">
                {{ $status_label }}
            </div>

        </div>

        <div class="ptp-card">

            <div class="ptp-card-title">
                Risk Level
            </div>

            <div class="ptp-value">
                {{ $risk_label }}
            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-md-6">

            <div class="ptp-section">

                <div class="ptp-section-title">

                    <i class="fa fa-user"></i>

                    Customer Information

                </div>

                <table class="table ptp-info-table">

                    <tr>
                        <td class="ptp-label-cell">
                            Customer
                        </td>
                        <td>
                            @if(!empty($customer_name))
                                {{ $customer_name }}
                            @else
                                <span class="text-danger">
                                    Customer Not Linked
                                </span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="ptp-label-cell">
                            Loan
                        </td>
                        <td>
                            Loan #{{ optional($record->loan)->id ?? $record->loan_id }}
                        </td>
                    </tr>

                    <tr>
                        <td class="ptp-label-cell">
                            Branch
                        </td>
                        <td>
                            {{ optional($record->location)->name ?? 'Not Available' }}
                        </td>
                    </tr>

                    <tr>
                        <td class="ptp-label-cell">
                            Recovery Officer
                        </td>
                        <td>
                            {{ optional($record->recoveryOfficer)->first_name }}
                            {{ optional($record->recoveryOfficer)->last_name }}
                        </td>
                    </tr>

                </table>

            </div>

        </div>

        <div class="col-md-6">

            <div class="ptp-section">

                <div class="ptp-section-title">

                    <i class="fa fa-info-circle"></i>

                    Promise Information

                </div>

                <table class="table ptp-info-table">

                    <tr>
                        <td class="ptp-label-cell">
                            PTP Type
                        </td>
                        <td>
                            {{ $ptp_type_label }}
                        </td>
                    </tr>

                    <tr>
                        <td class="ptp-label-cell">
                            Contact Source
                        </td>
                        <td>
                            {{ $source_label }}
                        </td>
                    </tr>

                    <tr>
                        <td class="ptp-label-cell">
                            Escalated
                        </td>
                        <td>
                            @if($record->is_escalated)
                                <span class="label label-danger">
                                    Escalated
                                </span>
                            @else
                                <span class="label label-primary">
                                    Normal
                                </span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="ptp-label-cell">
                            Created Date
                        </td>
                        <td>
                            {{ $record->created_at }}
                        </td>
                    </tr>

                </table>

            </div>

        </div>

    </div>

    <div class="ptp-section">

        <div class="ptp-section-title">

            <i class="fa fa-sticky-note"></i>

            Notes & Recovery Remarks

        </div>

        <div class="ptp-note-box">

            @if(!empty($record->remarks))

                {{ $record->remarks }}

            @else

                <span class="ptp-empty-text">
                    No notes or recovery remarks available.
                </span>

            @endif

        </div>

    </div>

    <div class="ptp-section">

        <div class="ptp-section-title">

            <i class="fa fa-calendar-check-o"></i>

            Follow-Up Timeline

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Officer</th>
                        <th>Note</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($record->followups as $followup)

                        <tr>

                            <td>
                                {{ $followup->followup_date }}
                            </td>

                            <td>
                                {{ ucwords(str_replace('_', ' ', $followup->followup_status)) }}
                            </td>

                            <td>
                                {{ optional($followup->assignedOfficer)->first_name }}
                                {{ optional($followup->assignedOfficer)->last_name }}
                            </td>

                            <td>
                                {{ $followup->followup_note }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No follow-ups available.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <div class="ptp-section">

        <div class="ptp-section-title">

            <i class="fa fa-history"></i>

            Audit Trail

        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-hover enterprise-table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Status Change</th>
                        <th>Note</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($record->logs as $log)

                        <tr>

                            <td>
                                {{ $log->created_at }}
                            </td>

                            <td>
                                {{ ucwords(str_replace('_', ' ', $log->action_type)) }}
                            </td>

                            <td>
                                {{ ucwords(str_replace('_', ' ', $log->old_status)) }}

                                →

                                {{ ucwords(str_replace('_', ' ', $log->new_status)) }}
                            </td>

                            <td>
                                {{ $log->note }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No audit records available.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection