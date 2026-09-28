@extends('RiceMill::layout')
@section('rcm-title','Add Customer Payment')
@section('rcm-subtitle','Receive a payment and allocate it against one or more outstanding Rice Mill Sales Invoices')
@section('rcm-actions')<a class="rcm-btn secondary" href="{{ route('rice-mill.customer-payments.index') }}"><i class="fa fa-list"></i> Payment History</a>@endsection
@section('rcm-content')
<div class="rcm-card">
    <div class="rcm-panel-head"><h3>Payment Details</h3><span class="rcm-panel-hint">Next Payment No.: <strong>{{ $preview['preview'] }}</strong></span></div>
    <form method="post" action="{{ route('rice-mill.customer-payments.store') }}" id="rcm-customer-payment-form"
          data-outstanding-url="{{ route('rice-mill.customer-payments.outstanding',['customer'=>'__CUSTOMER__']) }}"
          data-preselect-invoice="{{ $invoiceId }}" data-currency-precision="{{ $rcmCurrencyPrecision }}">
        @csrf
        <div class="rcm-form-grid">
            <div class="rcm-field" style="grid-column:span 2;"><label>Customer *</label><select class="rcm-searchable" name="customer_id" id="rcm-payment-customer" required><option value="">Select customer</option>@foreach($customers as $c)<option value="{{ $c['id'] }}" {{ (int)old('customer_id',$customerId)===(int)$c['id']?'selected':'' }}>{{ $c['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Payment Date & Time *</label><input type="datetime-local" name="payment_date" value="{{ old('payment_date',now()->format('Y-m-d\TH:i')) }}" required></div>
            <div class="rcm-field"><label>Payment Amount *</label><input type="number" name="amount" id="rcm-payment-amount" step="{{ $rcmCurrencyStep }}" min="{{ $rcmCurrencyStep }}" value="{{ old('amount') }}" required></div>
            <div class="rcm-field"><label>Payment Method *</label><select name="method" id="rcm-payment-method" required><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="card">Card</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
            <div class="rcm-field"><label>Reference No.</label><input name="reference_no" value="{{ old('reference_no') }}"></div>
            <div class="rcm-field"><label>Cheque No.</label><input name="cheque_number" value="{{ old('cheque_number') }}"></div>
            <div class="rcm-field"><label>Bank Name</label><input name="bank_name" value="{{ old('bank_name') }}"></div>
            <div class="rcm-field" style="grid-column:span 2;"><label>Note</label><textarea name="note" rows="2">{{ old('note') }}</textarea></div>
        </div>

        <div class="rcm-panel-head" style="margin-top:18px;"><h3>Select Outstanding Bills</h3><span class="rcm-panel-hint">You may allocate this payment to one or several invoices. Any unallocated balance will be kept as Customer Advance.</span></div>
        <div class="rcm-table-wrap">
            <table class="rcm-table" id="rcm-payment-outstanding-table"><thead><tr><th>Select</th><th>Invoice No.</th><th>Invoice Date</th><th>Due Date</th><th class="rcm-num">Invoice Amount</th><th class="rcm-num">Already Paid</th><th class="rcm-num">Outstanding</th><th class="rcm-num">Amount to Allocate</th></tr></thead><tbody><tr data-empty><td colspan="8" class="rcm-muted">Select a customer to load outstanding Sales Invoices.</td></tr></tbody></table>
        </div>
        <div class="rcm-kpi-grid" style="margin-top:14px;">
            <div class="rcm-kpi-card rcm-kpi-rice"><div class="rcm-kpi-icon"><i class="fa fa-money"></i></div><div class="rcm-kpi-content"><div class="rcm-kpi-label">Payment Amount</div><div class="rcm-kpi-value" id="rcm-payment-total-card">0</div></div></div>
            <div class="rcm-kpi-card rcm-kpi-production"><div class="rcm-kpi-icon"><i class="fa fa-check-square-o"></i></div><div class="rcm-kpi-content"><div class="rcm-kpi-label">Allocated to Bills</div><div class="rcm-kpi-value" id="rcm-payment-allocated-card">0</div></div></div>
            <div class="rcm-kpi-card rcm-kpi-sales"><div class="rcm-kpi-icon"><i class="fa fa-university"></i></div><div class="rcm-kpi-content"><div class="rcm-kpi-label">Customer Advance</div><div class="rcm-kpi-value" id="rcm-payment-advance-card">0</div></div></div>
            <div class="rcm-kpi-card rcm-kpi-yield"><div class="rcm-kpi-icon"><i class="fa fa-file-text-o"></i></div><div class="rcm-kpi-content"><div class="rcm-kpi-label">Selected Bills</div><div class="rcm-kpi-value" id="rcm-payment-bills-card">0</div></div></div>
        </div>
        <div class="rcm-alert rcm-alert-danger" id="rcm-payment-allocation-error" style="display:none;"><i class="fa fa-exclamation-circle"></i><div></div></div>
        <div style="margin-top:16px;text-align:right;"><button class="rcm-btn primary" id="rcm-payment-save" type="submit"><i class="fa fa-save"></i> Save Customer Payment</button></div>
    </form>
</div>
@php $paymentJs=public_path('modules/ricemill/js/customer-payments.js'); @endphp
@if(is_file($paymentJs))<script src="{{ asset('modules/ricemill/js/customer-payments.js') }}?v={{ filemtime($paymentJs) }}"></script>@endif
@endsection
