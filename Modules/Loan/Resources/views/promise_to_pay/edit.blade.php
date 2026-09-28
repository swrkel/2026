@extends('layouts.app')

@section('title', 'Edit Promise To Pay')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.loan-operational-dashboard-style')

@php
    $currency_precision = session('business.currency_precision', 2);

    $promised_amount = number_format(
        $record->promised_amount,
        $currency_precision
    );
@endphp

<style>
.ptp-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr 0.8fr;
    gap: 22px;
    margin-bottom: 22px;
}

.ptp-grid-secondary {
    display: grid;
    grid-template-columns: 1fr 1fr 1.4fr;
    gap: 22px;
    margin-bottom: 22px;
}

.ptp-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    min-height: 210px;
}

.ptp-card-small {
    min-height: 160px;
}

.ptp-card-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2d3d;
    margin-bottom: 18px;
}

.ptp-card-title i {
    margin-right: 8px;
}

.ptp-help-box {
    background: #f8fafc;
    border-left: 5px solid #f39c12;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 22px;
    color: #52616b;
    line-height: 1.7;
}

.ptp-field-label {
    font-weight: 600;
    color: #34495e;
    margin-bottom: 6px;
}

.ptp-card .form-control {
    border-radius: 10px;
    min-height: 42px;
    border: 1px solid #dfe6e9;
    box-shadow: none;
}

.ptp-card textarea.form-control {
    min-height: 130px;
}

.erp-amount-input {
    text-align: right;
}

.ptp-submit-row {
    background: #f8fafc;
    border-radius: 16px;
    padding: 18px;
    margin-top: 10px;
    text-align: right;
}

.ptp-secondary-btn {
    display: inline-block;
    background: #34495e;
    color: #ffffff !important;
    padding: 13px 22px;
    border-radius: 12px;
    font-weight: 700;
    text-decoration: none !important;
    margin-right: 8px;
}

@media(max-width: 991px) {
    .ptp-grid,
    .ptp-grid-secondary {
        grid-template-columns: 1fr;
    }

    .ptp-card,
    .ptp-card-small {
        min-height: auto;
    }

    .ptp-submit-row {
        text-align: left;
    }
}
</style>

<section class="content-header">
    <h1>
        Edit Promise To Pay
        <small>Update Customer Payment Commitment</small>
    </h1>
</section>

<section class="content">

    <div class="loan-op-header-panel">
        <div class="row">
            <div class="col-md-8">
                <div class="loan-op-title">
                    <i class="fa fa-pencil-square-o"></i>
                    Edit Promise To Pay - {{ $record->ptp_no }}
                </div>

                <div class="loan-op-subtitle">
                    Update customer repayment commitment, officer responsibility,
                    notes, risk level and recovery governance details.
                </div>
            </div>

            <div class="col-md-4 text-right">
                <a href="{{ route('loan.promise.to.pay.index') }}"
                   class="loan-op-action-btn">
                    <i class="fa fa-arrow-left"></i>
                    Back To PTP List
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="ptp-help-box">
        <strong>Promise-To-Pay Edit Note:</strong>
        Update only when customer commitment details have changed.
        If no field is changed, the system will show
        <strong>Nothing is changed to save.</strong>
    </div>

    <form method="POST"
          action="{{ route('loan.promise.to.pay.update', $record->id) }}"
          id="ptp-edit-form">

        @csrf

        <div class="ptp-grid">

            <div class="ptp-card">
                <div class="ptp-card-title">
                    <i class="fa fa-bank"></i>
                    Loan & Customer Reference
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">PTP Number</label>

                    <input type="text"
                           class="form-control"
                           value="{{ $record->ptp_no }}"
                           readonly>
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Loan</label>

                    <input type="text"
                           class="form-control"
                           value="Loan #{{ $record->loan_id }}"
                           readonly>
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Recovery Officer</label>

                    <select name="recovery_officer_id"
                            class="form-control select2"
                            required>

                        @foreach($officers as $officer)
                            <option value="{{ $officer->id }}"
                                {{ $record->recovery_officer_id == $officer->id ? 'selected' : '' }}>
                                {{ $officer->first_name }}
                                {{ $officer->last_name }}
                            </option>
                        @endforeach

                    </select>
                </div>
            </div>

            <div class="ptp-card">
                <div class="ptp-card-title">
                    <i class="fa fa-money"></i>
                    Promise Commitment
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Promised Amount</label>

                    <input type="text"
                           name="promised_amount"
                           class="form-control erp-amount-input"
                           data-precision="{{ $currency_precision }}"
                           value="{{ $promised_amount }}"
                           autocomplete="off"
                           required>
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Promised Payment Date</label>

                    <input type="date"
                           name="promised_payment_date"
                           class="form-control"
                           value="{{ $record->promised_payment_date }}"
                           required>
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Current Status</label>

                    <input type="text"
                           class="form-control"
                           value="{{ ucwords(str_replace('_', ' ', $record->status)) }}"
                           readonly>
                </div>
            </div>

            <div class="ptp-card ptp-card-small">
                <div class="ptp-card-title">
                    <i class="fa fa-list"></i>
                    PTP Type
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Payment Commitment Type</label>

                    <select name="ptp_type"
                            class="form-control select2">
                        <option value="full_payment"
                            {{ $record->ptp_type == 'full_payment' ? 'selected' : '' }}>
                            Full Payment
                        </option>

                        <option value="partial_payment"
                            {{ $record->ptp_type == 'partial_payment' ? 'selected' : '' }}>
                            Partial Payment
                        </option>

                        <option value="installment"
                            {{ $record->ptp_type == 'installment' ? 'selected' : '' }}>
                            Installment
                        </option>
                    </select>
                </div>
            </div>

        </div>

        <div class="ptp-grid-secondary">

            <div class="ptp-card ptp-card-small">
                <div class="ptp-card-title">
                    <i class="fa fa-phone"></i>
                    Contact Source
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Source</label>

                    <select name="source"
                            class="form-control select2">
                        <option value="phone_call"
                            {{ $record->source == 'phone_call' ? 'selected' : '' }}>
                            Phone Call
                        </option>

                        <option value="field_visit"
                            {{ $record->source == 'field_visit' ? 'selected' : '' }}>
                            Field Visit
                        </option>

                        <option value="branch_visit"
                            {{ $record->source == 'branch_visit' ? 'selected' : '' }}>
                            Branch Visit
                        </option>

                        <option value="email"
                            {{ $record->source == 'email' ? 'selected' : '' }}>
                            Email
                        </option>
                    </select>
                </div>
            </div>

            <div class="ptp-card ptp-card-small">
                <div class="ptp-card-title">
                    <i class="fa fa-warning"></i>
                    Risk Level
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Commitment Risk Level</label>

                    <select name="risk_level"
                            class="form-control select2">
                        <option value="low"
                            {{ $record->risk_level == 'low' ? 'selected' : '' }}>
                            Low
                        </option>

                        <option value="medium"
                            {{ $record->risk_level == 'medium' ? 'selected' : '' }}>
                            Medium
                        </option>

                        <option value="high"
                            {{ $record->risk_level == 'high' ? 'selected' : '' }}>
                            High
                        </option>

                        <option value="critical"
                            {{ $record->risk_level == 'critical' ? 'selected' : '' }}>
                            Critical
                        </option>
                    </select>
                </div>
            </div>

            <div class="ptp-card">
                <div class="ptp-card-title">
                    <i class="fa fa-sticky-note"></i>
                    Notes & Recovery Remarks
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Notes / Remarks</label>

                    <textarea name="remarks"
                              class="form-control"
                              placeholder="Customer commitment notes, negotiation remarks, recovery observations, risk comments...">{{ $record->remarks }}</textarea>
                </div>
            </div>

        </div>

        <div class="ptp-submit-row">

            <a href="{{ route('loan.promise.to.pay.index') }}"
               class="ptp-secondary-btn">
                Cancel
            </a>

            <button type="submit"
                    class="loan-op-action-btn"
                    style="border:none;">
                <i class="fa fa-save"></i>
                Update Promise To Pay
            </button>

        </div>

    </form>

</section>

<script>
$(document).ready(function () {

    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%'
        });
    }

    let originalFormData = $('#ptp-edit-form').serialize();

    $('#ptp-edit-form').on('submit', function (e) {
        let currentFormData = $(this).serialize();

        if (originalFormData === currentFormData) {
            e.preventDefault();
            alert('Nothing is changed to save.');
            return false;
        }

        $('.erp-amount-input').each(function () {
            $(this).val($(this).val().replace(/,/g, ''));
        });
    });

    $('.erp-amount-input').on('input', function () {
        let value = $(this).val().replace(/,/g, '');
        let precision = parseInt($(this).data('precision')) || 2;

        if (value === '' || isNaN(value)) {
            return;
        }

        let parts = value.split('.');

        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        if (parts.length > 1) {
            parts[1] = parts[1].substring(0, precision);
            $(this).val(parts[0] + '.' + parts[1]);
        } else {
            $(this).val(parts[0]);
        }
    });

    $('.erp-amount-input').on('blur', function () {
        let value = $(this).val().replace(/,/g, '');
        let precision = parseInt($(this).data('precision')) || 2;

        if (value !== '' && !isNaN(value)) {
            $(this).val(
                parseFloat(value).toLocaleString(undefined, {
                    minimumFractionDigits: precision,
                    maximumFractionDigits: precision
                })
            );
        }
    });

});
</script>

@endsection