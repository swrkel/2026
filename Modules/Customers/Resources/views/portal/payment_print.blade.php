@extends('customers::portal.layout')
@section('title', 'Payment Receipt')
@section('body')
<div class="dd-wrap">
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Payment Receipt</h3></div>
        <div class="dd-card-body">
            <p><strong>Customer:</strong> {{ $customer->name }} ({{ $customer->contact_id }})</p>
            <p><strong>Reference No:</strong> {{ $payment->payment_ref_no ?: 'PAY-'.$payment->id }}</p>
            <p><strong>Date:</strong> {{ !empty($payment->paid_on) ? date('Y-m-d', strtotime($payment->paid_on)) : '' }}</p>
            <p><strong>Invoice No:</strong> {{ $payment->invoice_no ?: $payment->ref_no }}</p>
            <p><strong>Payment Method:</strong> {{ ucwords(str_replace('_',' ', $payment->method)) }}</p>
            <p><strong>Remarks:</strong> {{ $payment->note }}</p>
            <hr>
            <h3 class="text-right">Amount: {{ number_format((float)$payment->amount, 2) }}</h3>
            <div class="dd-no-print" style="margin-top:20px;"><button onclick="window.print()" class="dd-btn dd-btn-primary">Print</button></div>
        </div>
    </div>
</div>
@endsection
