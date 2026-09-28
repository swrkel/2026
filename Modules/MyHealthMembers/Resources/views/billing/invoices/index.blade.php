@extends('layouts.app')
@section('title', 'MyHealth Invoices')
@section('content')
<section class="content-header"><h1>Invoices <a href="{{ route('myhealth.billing.invoices.create') }}" class="btn btn-primary pull-right">Add Invoice</a></h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<form method="get" class="form-inline"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search invoice/member"> <button class="btn btn-default">Search</button></form><br>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Action</th><th>Invoice No</th><th>Date</th><th>Member</th><th class="text-right">Gross</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Status</th></tr></thead><tbody>
@forelse($invoices as $invoice)<tr><td><a class="btn btn-xs btn-info" href="{{ route('myhealth.billing.invoices.show', $invoice) }}">View</a></td><td>{{ $invoice->invoice_no }}</td><td>{{ $invoice->invoice_date }}</td><td>{{ optional($invoice->member)->name }}</td><td class="text-right">{{ number_format($invoice->gross_amount, 4) }}</td><td class="text-right">{{ number_format($invoice->paid_amount, 4) }}</td><td class="text-right">{{ number_format($invoice->balance_amount, 4) }}</td><td>{{ ucwords(str_replace('_',' ', $invoice->status)) }}</td></tr>@empty<tr><td colspan="8">No records found.</td></tr>@endforelse
</tbody></table></div>{{ $invoices->links() }}
</section>
@endsection
