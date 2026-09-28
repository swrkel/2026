@extends('customers::layouts.action', ['title' => 'Pay Due Amount', 'compact' => true])

@php
    // S410: Payment methods must come only from the enabled methods configured
    // in Super Admin > All Businesses > Manage > Payment Method. Do not add
    // hard-coded/custom inactive payment types here.
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
<form method="POST" action="{{ route('customers.payments.due.store', $customer->id) }}">
    {{ csrf_field() }}
    {{-- IS2264: unique browser submission token prevents a double-click / retry
         from creating a second Pay Due payment before the first response returns. --}}
    <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    <input type="hidden" name="payment_mode" value="pay_due">
    <div class="row">
        <div class="col-md-4"><div class="form-group"><label>Customer</label><input class="form-control" value="{{ $customer->name }}" readonly></div></div>
        <div class="col-md-4"><div class="form-group"><label>Total Due</label><input class="form-control text-right" value="{{ number_format((float)($totalDue ?? 0), 2) }}" readonly></div></div>
        <div class="col-md-4"><div class="form-group"><label>System Payment Reference</label><input class="form-control" value="{{ $paymentReferencePreview ?? '' }}" readonly><span class="help-block text-muted">Preview from Customer Settings; final reference is assigned on Save.</span></div></div>
        <div class="col-md-4"><div class="form-group"><label>Amount</label><input name="amount" class="form-control input_number text-right customers-amount-input" value="{{ number_format(max((float)($totalDue ?? 0), 0), 2) }}" required></div></div>
        <div class="col-md-4"><div class="form-group"><label for="customers_pay_due_payment_date">Transaction Date</label><div class="input-group"><span class="input-group-addon customers-open-datepicker" role="button" tabindex="-1" aria-label="Open transaction date picker"><i class="fa fa-calendar"></i></span><input type="date" id="customers_pay_due_payment_date" name="payment_date" class="form-control customers-native-date" value="{{ $paymentDateValue }}" autocomplete="off" required></div></div></div>
        <div class="col-md-4"><div class="form-group"><label>Payment Method</label>{!! Form::select('method', $paymentMethods, old('method', ''), ['class' => 'form-control customers-payment-method', 'required']) !!}</div></div>
        <div class="col-md-4"><div class="form-group"><label>Payment Account</label>{!! Form::select('account_id', ['' => 'Please Select'] + ($accountOptions ?? []), old('account_id', ''), ['class' => 'form-control customers-payment-account', 'required']) !!}<span class="help-block text-muted customers-account-help">Account list changes according to the selected payment method.</span></div></div>

        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;"><div class="form-group"><label>Cheque No <span class="customers-bank-required text-danger">*</span></label><input name="cheque_number" class="form-control customers-bank-field" value="{{ old('cheque_number') }}" autocomplete="off"></div></div>
        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;"><div class="form-group"><label>Bank <span class="customers-bank-required text-danger">*</span></label><input name="bank_name" class="form-control customers-bank-field" value="{{ old('bank_name') }}" autocomplete="off"></div></div>
        <div class="col-md-4 customers-bank-cheque-fields" style="display:none;"><div class="form-group"><label for="customers_pay_due_cheque_date">Cheque Date <span class="customers-bank-required text-danger">*</span></label><div class="input-group"><span class="input-group-addon customers-open-datepicker" role="button" tabindex="-1" aria-label="Open cheque date picker"><i class="fa fa-calendar"></i></span><input type="date" id="customers_pay_due_cheque_date" name="cheque_date" class="form-control customers-native-date customers-bank-field" value="{{ $chequeDateValue }}" autocomplete="off"></div></div></div>

        {{-- SW Shift No. Cash from a customer during a shift adds to
             Balance In Hand. Renders nothing unless SW is enabled. --}}
        <div class="col-md-8">@includeIf('sw::partials.shift_field')</div>
        <div class="col-md-12"><div class="form-group"><label>Note</label><textarea name="note" class="form-control"></textarea></div></div>
    </div>
    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Save Payment</button>
</form>
@include('customers::payments.partials.method_account_script')
@endsection
