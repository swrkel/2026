@extends('layouts.app')
@section('title', 'Invoice Details')
@section('content')
<section class="content-header"><h1>Invoice {{ $invoice->invoice_no }} <a class="btn btn-success pull-right" href="{{ route('myhealth.billing.payments.create', $invoice) }}">Add Payment</a></h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<div class="box"><div class="box-body"><div class="row"><div class="col-md-4"><b>Member:</b> {{ optional($invoice->member)->name }}</div><div class="col-md-2"><b>Date:</b> {{ $invoice->invoice_date }}</div><div class="col-md-2"><b>Status:</b> {{ ucwords(str_replace('_',' ', $invoice->status)) }}</div><div class="col-md-4 text-right"><b>Balance:</b> {{ number_format($invoice->balance_amount, 4) }}</div></div></div></div>
<h4>Items</h4><table class="table table-bordered table-striped"><thead><tr><th>Description</th><th>Type</th><th class="text-right">Qty</th><th class="text-right">Unit</th><th class="text-right">Discount</th><th class="text-right">Total</th></tr></thead><tbody>@foreach($invoice->items as $item)<tr><td>{{ $item->description }}</td><td>{{ $item->item_type }}</td><td class="text-right">{{ number_format($item->qty, 4) }}</td><td class="text-right">{{ number_format($item->unit_price, 4) }}</td><td class="text-right">{{ number_format($item->discount_amount, 4) }}</td><td class="text-right">{{ number_format($item->line_total, 4) }}</td></tr>@endforeach</tbody></table>
<h4>Payments</h4><table class="table table-bordered"><thead><tr><th>No</th><th>Date</th><th>Method</th><th>Reference</th><th class="text-right">Amount</th></tr></thead><tbody>@forelse($invoice->payments as $payment)<tr><td>{{ $payment->payment_no }}</td><td>{{ $payment->payment_date }}</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->reference_no }}</td><td class="text-right">{{ number_format($payment->amount, 4) }}</td></tr>@empty<tr><td colspan="5">No payments yet.</td></tr>@endforelse</tbody></table>
</section>
@endsection
