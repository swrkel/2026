@extends('customers::layouts.action', ['title' => 'Security Deposit'])

@php
    /*
     * IS2307
     * - The old Date field was a plain text input, so there was no reliable
     *   calendar/date selection in the AJAX popup.
     * - The save service requires Payment Method + Payment Account, but the old
     *   Security Deposit form did not submit either field.  That caused Save to
     *   return a validation error every time.
     *
     * Keep this form on the same payment controls used by the working Customer
     * payment actions so account mappings and cheque requirements remain one
     * source of truth.
     */
    $paymentMethods = ['' => 'Please Select'] + ($paymentMethods ?? []);

    $depositDateValue = old('date', $todayIso ?? date('Y-m-d'));
    $chequeDateValue = old('cheque_date', '');

    try {
        $depositDateValue = \Carbon\Carbon::parse($depositDateValue)->format('Y-m-d');
    } catch (\Throwable $e) {
        $depositDateValue = $todayIso ?? date('Y-m-d');
    }

    if (!empty($chequeDateValue)) {
        try {
            $chequeDateValue = \Carbon\Carbon::parse($chequeDateValue)->format('Y-m-d');
        } catch (\Throwable $e) {
            $chequeDateValue = '';
        }
    }
@endphp

@section('customer_action_body')
<style>
    .customers-security-deposit-card{
        background:#f8fbff;
        border:1px solid #dce9f7;
        border-radius:14px;
        padding:16px;
        margin-bottom:15px;
    }
    .customers-security-deposit-card label{font-weight:700;color:#1f2d3d;}
    .customers-security-deposit-card .input-group-addon{cursor:pointer;background:#eef6ff;}
    .customers-security-deposit-summary{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin:4px 0 14px;
        padding:12px 14px;
        border:1px solid #e5e7eb;
        border-radius:10px;
        background:#f8fafc;
    }
    .customers-security-deposit-summary strong{font-size:16px;color:#1f2937;}
    .customers-security-deposit-table-wrap{
        width:100%;
        overflow-x:auto;
        border:1px solid #e5e7eb;
        border-radius:10px;
        margin-top:10px;
    }
    .customers-security-deposit-table{
        width:100%;
        min-width:980px;
        margin:0;
        table-layout:auto;
    }
    .customers-security-deposit-table th{
        white-space:nowrap;
        background:#f8fafc;
        font-weight:700;
        vertical-align:middle !important;
    }
    .customers-security-deposit-table td{vertical-align:middle !important;}
    .customers-security-deposit-table .amount-cell{
        text-align:right;
        white-space:nowrap;
        font-variant-numeric:tabular-nums;
    }
    .customers-security-deposit-table .empty-row{
        text-align:center;
        padding:24px 12px;
        color:#6b7280;
    }
    .customers-security-deposit-section-title{
        margin:22px 0 8px;
        font-weight:700;
        color:#1f2937;
    }
</style>

<form method="POST" action="{{ route('customers.deposits.security.store', $customer->id) }}" id="customers_security_deposit_form">
    {{ csrf_field() }}
    <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

    <div class="row customers-security-deposit-card">
        <div class="col-md-4">
            <div class="form-group">
                <label>Customer</label>
                <input class="form-control" value="{{ $customer->name }}" readonly>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label>Deposit Amount</label>
                <input name="amount" class="form-control input_number text-right" value="{{ old('amount') }}" required>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="customers_security_deposit_date">Date</label>
                <div class="input-group">
                    <span class="input-group-addon customers-open-datepicker" role="button" tabindex="-1" aria-label="Open security deposit date picker">
                        <i class="fa fa-calendar"></i>
                    </span>
                    <input type="date"
                           id="customers_security_deposit_date"
                           name="date"
                           class="form-control customers-native-date"
                           value="{{ $depositDateValue }}"
                           autocomplete="off"
                           required>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label>Payment Method</label>
                {!! Form::select('method', $paymentMethods, old('method', ''), ['class' => 'form-control customers-payment-method', 'required']) !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label>Payment Account</label>
                {!! Form::select('account_id', ['' => 'Please Select'] + ($accountOptions ?? []), old('account_id', ''), ['class' => 'form-control customers-payment-account', 'required']) !!}
                <span class="help-block text-muted customers-account-help">Account list changes according to the selected payment method.</span>
            </div>
        </div>

        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;">
            <div class="form-group">
                <label>Cheque No <span class="customers-bank-required text-danger">*</span></label>
                <input name="cheque_number" class="form-control customers-bank-field" value="{{ old('cheque_number') }}" autocomplete="off">
            </div>
        </div>

        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;">
            <div class="form-group">
                <label>Bank <span class="customers-bank-required text-danger">*</span></label>
                <input name="bank_name" class="form-control customers-bank-field" value="{{ old('bank_name') }}" autocomplete="off">
            </div>
        </div>

        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;">
            <div class="form-group">
                <label for="customers_security_deposit_cheque_date">Cheque Date <span class="customers-bank-required text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-addon customers-open-datepicker" role="button" tabindex="-1" aria-label="Open cheque date picker">
                        <i class="fa fa-calendar"></i>
                    </span>
                    <input type="date"
                           id="customers_security_deposit_cheque_date"
                           name="cheque_date"
                           class="form-control customers-native-date customers-bank-field"
                           value="{{ $chequeDateValue }}"
                           autocomplete="off">
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label>Note</label>
                <textarea name="note" class="form-control">{{ old('note') }}</textarea>
            </div>
        </div>
    </div>

    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Save</button>
</form>

<h4 class="customers-security-deposit-section-title">Security Deposit History</h4>
<div class="customers-security-deposit-summary">
    <span>Total Security Deposits</span>
    <strong>{{ number_format((float) ($depositTotal ?? 0), 2) }}</strong>
</div>

<div class="customers-security-deposit-table-wrap">
    <table class="table table-bordered table-hover customers-security-deposit-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference No</th>
                <th>Payment Method</th>
                <th>Cheque No</th>
                <th>Cheque Date</th>
                <th>Bank</th>
                <th>Status</th>
                <th>Note</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($deposits ?? collect()) as $deposit)
                <tr>
                    <td>{{ !empty($deposit->deposit_date) ? \Carbon\Carbon::parse($deposit->deposit_date)->format('d/m/Y') : '' }}</td>
                    <td>{{ $deposit->payment_ref_no ?? '' }}</td>
                    <td>{{ !empty($deposit->method) ? ucwords(str_replace('_', ' ', $deposit->method)) : '-' }}</td>
                    <td>{{ $deposit->cheque_number ?? '' }}</td>
                    <td>{{ !empty($deposit->cheque_date) ? \Carbon\Carbon::parse($deposit->cheque_date)->format('d/m/Y') : '' }}</td>
                    <td>{{ $deposit->bank_name ?? '' }}</td>
                    <td>{{ !empty($deposit->status) ? ucwords(str_replace('_', ' ', $deposit->status)) : '-' }}</td>
                    <td>{{ $deposit->note ?? '' }}</td>
                    <td class="amount-cell">{{ number_format((float) ($deposit->amount ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-row">No security deposits found for this customer.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Reuse the working Customer payment behaviour for linked accounts and
     conditional cheque/bank details. --}}
@include('customers::payments.partials.method_account_script')
@endsection
