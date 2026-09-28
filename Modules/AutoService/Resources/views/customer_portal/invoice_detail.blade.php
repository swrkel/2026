@extends('autoservice::layouts.master')
@section('title','Customer Invoice Detail')
@section('autoservice_content')
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Invoice Detail - {{ $invoice->invoice_no }}</h3>
        <div class="box-tools pull-right">
            <button onclick="window.print()" class="btn btn-sm btn-default"><i class="fa fa-print"></i> Print</button>
            @if(!empty($portalSettings['allow_customer_invoice_pdf']))
                <a href="{{ route('autoservice.invoices.print', $invoice->id) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-file-pdf-o"></i> PDF / Print View</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered table-condensed">
                    <tr><th style="width:180px">Invoice No</th><td>{{ $invoice->invoice_no }}</td></tr>
                    <tr><th>Invoice Date</th><td>{{ $invoice->invoice_date }}</td></tr>
                    <tr><th>Job No</th><td>{{ optional($invoice->job)->job_no }}</td></tr>
                    <tr><th>Status</th><td>{{ ucwords(str_replace('_',' ', $invoice->status)) }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-condensed">
                    <tr><th style="width:180px">Subtotal</th><td class="text-right">{{ number_format((float)$invoice->subtotal, 2) }}</td></tr>
                    <tr><th>Discount</th><td class="text-right">{{ number_format((float)$invoice->discount_amount, 2) }}</td></tr>
                    <tr><th>Tax</th><td class="text-right">{{ number_format((float)$invoice->tax_amount, 2) }}</td></tr>
                    <tr><th>Total</th><td class="text-right"><strong>{{ number_format((float)$invoice->total_amount, 2) }}</strong></td></tr>
                    <tr><th>Paid</th><td class="text-right">{{ number_format((float)$invoice->paid_amount, 2) }}</td></tr>
                    <tr><th>Balance</th><td class="text-right"><strong>{{ number_format((float)$invoice->balance_amount, 2) }}</strong></td></tr>
                </table>
            </div>
        </div>
        <h4>Invoice Lines</h4>
        <table class="table table-bordered table-striped">
            <thead><tr><th>Type</th><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right">Line Total</th></tr></thead>
            <tbody>
                @foreach($invoice->lines as $line)
                    <tr>
                        <td>{{ ucwords(str_replace('_',' ', $line->line_type)) }}</td>
                        <td>{{ $line->description }}</td>
                        <td class="text-right">{{ number_format((float)$line->quantity, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$line->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$line->discount_amount, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$line->tax_amount, 2) }}</td>
                        <td class="text-right">{{ number_format((float)$line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <h4>Payment History</h4>
        <table class="table table-bordered table-striped">
            <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th>Status</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
                @forelse($invoice->payments as $payment)
                    <tr><td>{{ $payment->payment_date }}</td><td>{{ ucwords(str_replace('_',' ', $payment->payment_method)) }}</td><td>{{ $payment->reference_no }}</td><td>{{ ucwords(str_replace('_',' ', $payment->status)) }}</td><td class="text-right">{{ number_format((float)$payment->amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center">No payment recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
