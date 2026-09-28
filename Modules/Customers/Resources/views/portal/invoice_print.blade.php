@extends('customers::portal.layout')
@section('title', 'Invoice Print')
@section('body')
<div class="dd-wrap">
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Invoice</h3></div>
        <div class="dd-card-body">
            <p><strong>Customer:</strong> {{ $customer->name }} ({{ $customer->contact_id }})</p>
            <p><strong>Invoice No:</strong> {{ $invoice->invoice_no ?: $invoice->ref_no }}</p>
            <p><strong>Date:</strong> {{ !empty($invoice->transaction_date) ? date('Y-m-d', strtotime($invoice->transaction_date)) : '' }}</p>
            <p><strong>Status:</strong> {{ ucwords(str_replace('_',' ', $invoice->payment_status ?: 'Due')) }}</p>
            <hr>
            <table class="dd-table" style="min-width:0;">
                <thead><tr><th>Description</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                    <tr><td>{{ ucwords(str_replace('_',' ', $invoice->type)) }}</td><td class="text-right">{{ number_format((float)$invoice->final_total, 2) }}</td></tr>
                    <tr><td>Paid</td><td class="text-right">{{ number_format((float)$invoice->paid_amount, 2) }}</td></tr>
                    <tr><td><strong>Balance</strong></td><td class="text-right"><strong>{{ number_format((float)$invoice->balance, 2) }}</strong></td></tr>
                </tbody>
            </table>
            <div class="dd-no-print" style="margin-top:20px;"><button onclick="window.print()" class="dd-btn dd-btn-primary">Print</button></div>
        </div>
    </div>
</div>
@endsection
