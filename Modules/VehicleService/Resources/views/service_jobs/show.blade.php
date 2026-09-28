@extends('layouts.app')
@section('title', 'Vehicle Service Bill')
@section('content')
<section class="content-header"><h1>Vehicle Service Bill <small>{{ $job->job_no }}</small></h1></section>
<section class="content">
<div class="box box-solid"><div class="box-body">
    <div class="row">
        <div class="col-md-3"><strong>Date:</strong> {{ $job->transaction_date }}</div>
        <div class="col-md-3"><strong>Vehicle:</strong> {{ $job->vehicle_no }}</div>
        <div class="col-md-3"><strong>Customer:</strong> {{ $job->customer_name }}</div>
        <div class="col-md-3"><strong>Status:</strong> {{ ucfirst($job->status) }}</div>
    </div><hr>
    <table class="table table-bordered table-striped">
        <thead><tr><th>Item</th><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right">Total</th></tr></thead>
        <tbody>@foreach($job->lines as $line)<tr><td>{{ $line->item_name }}</td><td>{{ $line->description }}</td><td class="text-right">{{ number_format($line->quantity, 4) }}</td><td class="text-right">{{ number_format($line->unit_price, 2) }}</td><td class="text-right">{{ number_format($line->discount_amount, 2) }}</td><td class="text-right">{{ number_format($line->tax_amount, 2) }}</td><td class="text-right">{{ number_format($line->line_total, 2) }}</td></tr>@endforeach</tbody>
        <tfoot><tr><th colspan="6" class="text-right">Grand Total</th><th class="text-right">{{ number_format($job->total_amount, 2) }}</th></tr><tr><th colspan="6" class="text-right">Paid</th><th class="text-right">{{ number_format($job->paid_amount, 2) }}</th></tr><tr><th colspan="6" class="text-right">Balance</th><th class="text-right">{{ number_format($job->balance_amount, 2) }}</th></tr></tfoot>
    </table>
    <a href="{{ route('vehicleservice.jobs.index') }}" class="btn btn-default">Back</a>
</div></div>
</section>
@endsection
