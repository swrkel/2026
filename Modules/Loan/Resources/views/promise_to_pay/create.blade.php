@extends('layouts.app')

@section('title', 'Create Promise To Pay')

@section('content')

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.loan-operational-dashboard-style')

@php
    $currency_precision = session('business.currency_precision', 2);
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

.ptp-help-box {
    background: #f8fafc;
    border-left: 5px solid #3498db;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 22px;
    color: #52616b;
    line-height: 1.7;
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
        Create Promise To Pay
        <small>Register Customer Payment Commitment</small>
    </h1>
</section>

<section class="content">

    <div class="loan-op-header-panel">
        <div class="row">
            <div class="col-md-8">
                <div class="loan-op-title">
                    <i class="fa fa-handshake-o"></i>
                    New Promise To Pay Registration
                </div>

                <div class="loan-op-subtitle">
                    Capture customer repayment commitments, assign recovery responsibility,
                    and activate follow-up governance for promised payments.
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

    <div class="ptp-help-box">
        <strong>Promise-To-Pay Governance Note:</strong>
        Select the customer first, then select one of that customer’s active loans.
    </div>

    <form method="POST"
          action="{{ route('loan.promise.to.pay.store') }}">

        @csrf

        <div class="ptp-grid">

            <div class="ptp-card">
                <div class="ptp-card-title">
                    <i class="fa fa-bank"></i>
                    Loan & Customer Reference
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Customer</label>

                    <select id="ptp_customer_id"
                            class="form-control select2"
                            required>
                        <option value="">Select Customer</option>

@foreach($customers as $customer)
    <option value="{{ $customer->id }}">
        {{ $customer->name }}

        @if(!empty($customer->contact_id))
            | {{ $customer->contact_id }}
        @endif

        @if(!empty($customer->mobile))
            | {{ $customer->mobile }}
        @endif

        @if(!empty($customer->nic_number))
            | {{ $customer->nic_number }}
        @endif
    </option>
@endforeach

                    </select>
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Loan</label>

                    <select name="loan_id"
                            id="ptp_loan_id"
                            class="form-control select2"
                            required>
                        <option value="">Select Loan</option>

                        @foreach($loans as $loan)
                            <option value="{{ $loan->id }}"
                                    data-customer-id="{{ $loan->contact_id }}">
                                {{ $loan->loan_number ?? 'Loan #' . $loan->id }}

                                @if(!empty($loan->outstanding_amount))
                                    - Outstanding:
                                    {{ number_format($loan->outstanding_amount, $currency_precision) }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Recovery Officer</label>

                    <select name="recovery_officer_id"
                            class="form-control select2"
                            required>
                        <option value="">Select Recovery Officer</option>

                        @foreach($officers as $officer)
                            <option value="{{ $officer->id }}">
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
                           placeholder="0.{{ str_repeat('0', $currency_precision) }}"
                           autocomplete="off"
                           required>
                </div>

                <div class="form-group">
                    <label class="ptp-field-label">Promised Payment Date</label>

                    <input type="date"
                           name="promised_payment_date"
                           class="form-control"
                           required>
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
                        <option value="full_payment">Full Payment</option>
                        <option value="partial_payment">Partial Payment</option>
                        <option value="installment">Installment</option>
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
                        <option value="phone_call">Phone Call</option>
                        <option value="field_visit">Field Visit</option>
                        <option value="branch_visit">Branch Visit</option>
                        <option value="email">Email</option>
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
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
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
                              placeholder="Customer commitment notes, negotiation remarks, recovery observations, risk comments..."></textarea>
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
                Save Promise To Pay
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

    let allLoanOptions = $('#ptp_loan_id option').clone();

    $('#ptp_customer_id').on('change', function () {
        let customerId = $(this).val();

        $('#ptp_loan_id').empty();

        $('#ptp_loan_id').append(
            allLoanOptions.filter(function () {
                return $(this).val() === '';
            })
        );

        if (customerId) {
            allLoanOptions.each(function () {
                if ($(this).data('customer-id') == customerId) {
                    $('#ptp_loan_id').append($(this).clone());
                }
            });
        }

        $('#ptp_loan_id').val('').trigger('change.select2');
    });

    $('#ptp_loan_id').on('change', function () {
        let selectedOption = $(this).find(':selected');
        let customerId = selectedOption.data('customer-id');

        if (customerId) {
            $('#ptp_customer_id').val(customerId).trigger('change.select2');
        }
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