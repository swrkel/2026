@extends('customers::layouts.action', ['title' => 'Advance Payment'])

@php
    /*
     |--------------------------------------------------------------------------
     | S637-5: Payment Method and Payment Account on the Advance Payment popup
     |--------------------------------------------------------------------------
     |
     | THREE FAULTS, ALL FROM THIS FORM BEING BUILT SEPARATELY FROM PAY DUE:
     |
     | 1. THE ACCOUNT LIST COULD NEVER LOAD.
     |    formData() deliberately returns 'accountOptions' => [] - the account
     |    list is meant to be fetched after a payment method is chosen. Pay Due
     |    has the script that does that fetch; this form had NO script at all, so
     |    the Payment Account dropdown only ever held "Please Select". Since
     |    acknowledge() rejects an empty account_id with "Please select Payment
     |    Account.", an advance payment could not be saved at all.
     |
     | 2. THE METHOD DEFAULTED TO CASH WITH NO "PLEASE SELECT".
     |    It also never fired a change event, so even once the script exists
     |    nothing would populate the accounts until the user re-picked the
     |    method. Pay Due starts blank and forces a real choice.
     |
     | 3. THE CHEQUE / BANK FIELDS WERE ABSENT.
     |    acknowledge() requires Cheque No, Bank and Cheque Date for cheque,
     |    bank, bank_transfer, direct_bank_deposit and bank_deposit. This form
     |    offered nowhere to enter them, so those methods were rejected on save.
     |    On this tenant that is FOUR of the six enabled methods - Advance
     |    Payment only ever worked for Cash and Card.
     |
     | The fields below now mirror Pay Due exactly, and the shared script is
     | included at the foot, so the two popups cannot drift apart again.
     */
    $paymentMethods = ['' => 'Please Select'] + ($paymentMethods ?? []);

    $paymentDateValue = old('payment_date', $todayIso ?? date('Y-m-d'));
    $chequeDateValue = old('cheque_date', '');

    try {
        $paymentDateValue = \Carbon\Carbon::parse($paymentDateValue)->format('Y-m-d');
    } catch (\Throwable $e) {
        $paymentDateValue = $todayIso ?? date('Y-m-d');
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
<form method="POST" action="{{ route('customers.payments.advance.store', $customer->id) }}">
    {{ csrf_field() }}
    <input type="hidden" name="payment_mode" value="advance_payment">
    <style>
.customer-payment-section-card{background:#f8fbff;border:1px solid #dce9f7;border-radius:14px;padding:16px;margin-bottom:15px;}
.customer-payment-section-card label{font-weight:700;color:#1f2d3d;}
.customer-payment-section-card .input-group-addon{cursor:pointer;background:#eef6ff;}
</style>
    <div class="row customer-payment-section-card">
        <div class="col-md-4"><div class="form-group"><label>Customer</label><input class="form-control" value="{{ $customer->name }}" readonly></div></div>
        <div class="col-md-4"><div class="form-group"><label>Current Balance Due</label><input class="form-control text-right" value="{{ number_format((float)($totalDue ?? 0), 2) }}" readonly></div></div>
        <div class="col-md-4"><div class="form-group"><label>Advance Amount</label><input name="amount" class="form-control input_number text-right" required></div></div>

        <div class="col-md-4"><div class="form-group"><label for="customers_advance_payment_date">Transaction Date</label><div class="input-group"><span class="input-group-addon customers-open-datepicker" role="button" tabindex="0" aria-label="Open transaction date picker"><i class="fa fa-calendar"></i></span><input type="date" id="customers_advance_payment_date" name="payment_date" class="form-control customers-native-date" value="{{ $paymentDateValue }}" autocomplete="off" required></div></div></div>

        <div class="col-md-4"><div class="form-group"><label>Payment Method</label>{!! Form::select('method', $paymentMethods, old('method', ''), ['class' => 'form-control customers-payment-method', 'required']) !!}</div></div>

        <div class="col-md-4"><div class="form-group"><label>Payment Account</label>{!! Form::select('account_id', ['' => 'Please Select'] + ($accountOptions ?? []), old('account_id', ''), ['class' => 'form-control customers-payment-account', 'required']) !!}<span class="help-block text-muted customers-account-help">Account list changes according to the selected payment method.</span></div></div>

        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;"><div class="form-group"><label>Cheque No <span class="customers-bank-required text-danger">*</span></label><input name="cheque_number" class="form-control customers-bank-field" value="{{ old('cheque_number') }}" autocomplete="off"></div></div>
        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;"><div class="form-group"><label>Bank <span class="customers-bank-required text-danger">*</span></label><input name="bank_name" class="form-control customers-bank-field" value="{{ old('bank_name') }}" autocomplete="off"></div></div>
        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;"><div class="form-group"><label for="customers_advance_cheque_date">Cheque Date <span class="customers-bank-required text-danger">*</span></label><div class="input-group"><span class="input-group-addon customers-open-datepicker" role="button" tabindex="0" aria-label="Open cheque date picker"><i class="fa fa-calendar"></i></span><input type="date" id="customers_advance_cheque_date" name="cheque_date" class="form-control customers-native-date customers-bank-field" value="{{ $chequeDateValue }}" autocomplete="off"></div></div></div>

        <div class="col-md-12"><div class="form-group"><label>Note</label><textarea name="note" class="form-control"></textarea></div></div>
    </div>
    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Save Advance</button>
</form>

{{-- Same behaviour as Pay Due: loads the accounts for the chosen method and
     shows the cheque/bank fields when that method needs them. --}}
@include('customers::payments.partials.method_account_script')
@endsection
