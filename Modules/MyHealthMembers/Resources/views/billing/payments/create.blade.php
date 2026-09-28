@extends('layouts.app')
@section('title', 'Add Payment')
@section('content')
<section class="content-header"><h1>Add Payment - {{ $invoice->invoice_no }}</h1></section>
<section class="content"><form method="post" action="{{ route('myhealth.billing.payments.store', $invoice) }}">@csrf
<div class="row"><div class="col-md-3"><label>Payment Date</label><input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="form-control"></div><div class="col-md-3"><label>Payment Method</label><select name="payment_method" class="form-control"><option value="cash">Cash</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option><option value="insurance">Insurance</option></select></div><div class="col-md-3"><label>Amount</label><input name="amount" value="{{ number_format($invoice->balance_amount, 4, '.', '') }}" class="form-control text-right" required></div><div class="col-md-3"><label>Reference No</label><input name="reference_no" class="form-control"></div></div><br><label>Remarks</label><textarea name="remarks" class="form-control"></textarea><br><button class="btn btn-primary">Save Payment</button> <a href="{{ route('myhealth.billing.invoices.show', $invoice) }}" class="btn btn-default">Cancel</a>
</form></section>
@endsection
