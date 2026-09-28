@extends('autoservice::layouts.master')
@section('title','Auto Service Invoices')
@section('autoservice_content')

@if(!$invoice->stock_posted_at)
<form method="post" action="{{ route('autoservice.invoices.post',$invoice->id) }}" style="display:inline" onsubmit="return confirm('Post invoice and deduct stock items? This cannot be repeated.');">@csrf
<button class="btn btn-warning">Post Invoice & Deduct Stock</button></form>
@else
<span class="label label-success">Stock Posted {{ $invoice->stock_posted_at }}</span>
@endif

<div class="box"><div class="box-header with-border"><h3 class="box-title">Invoice {{ $invoice->invoice_no }}</h3><div class="pull-right"><a class="btn btn-success btn-sm" href="{{ route('autoservice.payments.create',['invoice_id'=>$invoice->id]) }}">Add Payment</a> <a class="btn btn-default btn-sm" target="_blank" href="{{ route('autoservice.invoices.print',$invoice->id) }}">Print</a></div></div><div class="box-body">
<p><b>Date:</b> {{ $invoice->invoice_date }} | <b>Status:</b> {{ ucfirst($invoice->status) }} | <b>Job:</b> {{ optional($invoice->job)->job_no }}</p>
<table class="table table-bordered"><thead><tr><th>Type</th><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr></thead><tbody>@foreach($invoice->lines as $line)<tr><td>{{ ucfirst($line->line_type) }}</td><td>{{ $line->description }}</td><td class="text-right">{{ number_format($line->quantity,2) }}</td><td class="text-right">{{ number_format($line->unit_price,2) }}</td><td class="text-right">{{ number_format($line->line_total,2) }}</td></tr>@endforeach</tbody></table>
<div class="text-right"><p>Subtotal: {{ number_format($invoice->subtotal,2) }}</p><p>Discount: {{ number_format($invoice->discount_amount,2) }}</p><p>Tax: {{ number_format($invoice->tax_amount,2) }}</p><h4>Total: {{ number_format($invoice->total_amount,2) }}</h4><p>Paid: {{ number_format($invoice->paid_amount,2) }}</p><h4>Balance: {{ number_format($invoice->balance_amount,2) }}</h4></div>
<hr><h4>Payments</h4><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="text-right">Amount</th><th>Status</th></tr></thead><tbody>@forelse($invoice->payments as $p)<tr><td>{{ $p->payment_date }}</td><td>{{ ucfirst($p->payment_method) }}</td><td>{{ $p->reference_no }}</td><td class="text-right">{{ number_format($p->amount,2) }}</td><td>{{ ucfirst($p->status) }}</td></tr>@empty<tr><td colspan="5" class="text-center">No payments found</td></tr>@endforelse</tbody></table>
</div></div>
@endsection
