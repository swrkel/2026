@extends('autoservice::layouts.master')
@section('title','Auto Service Invoices')
@section('autoservice_content')
<div class="box"><div class="box-header with-border"><h3 class="box-title">Auto Service Invoices</h3><a href="{{ route('autoservice.invoices.create') }}" class="btn btn-primary btn-sm pull-right">Add Invoice</a></div>
<div class="box-body table-responsive">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<table class="table table-bordered table-striped">
<thead><tr><th>Date</th><th>Invoice No</th><th>Job</th><th>Customer</th><th>Status</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Action</th></tr></thead>
<tbody>
@forelse($invoices as $invoice)
<tr>
<td>{{ $invoice->invoice_date }}</td><td>{{ $invoice->invoice_no }}</td><td>{{ $invoice->job_id }}</td><td>{{ $invoice->contact_id }}</td>
<td><span class="label label-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partial' ? 'warning' : 'default') }}">{{ ucfirst($invoice->status) }}</span></td>
<td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td><td class="text-right">{{ number_format($invoice->paid_amount, 2) }}</td><td class="text-right">{{ number_format($invoice->balance_amount, 2) }}</td>
<td><a class="btn btn-xs btn-info" href="{{ route('autoservice.invoices.show',$invoice->id) }}">View</a> <a class="btn btn-xs btn-primary" href="{{ route('autoservice.invoices.edit',$invoice->id) }}">Edit</a> <a class="btn btn-xs btn-default" target="_blank" href="{{ route('autoservice.invoices.print',$invoice->id) }}">Print</a></td>
</tr>
@empty<tr><td colspan="9" class="text-center">No invoices found.</td></tr>@endforelse
</tbody></table>{{ $invoices->links() }}
</div></div>
@endsection
