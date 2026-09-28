@extends('autoservice::layouts.master')
@section('title','Auto Service Payments')
@section('autoservice_content')
<div class="box"><div class="box-header with-border"><h3 class="box-title">Payments</h3><a href="{{ route('autoservice.payments.create') }}" class="btn btn-primary btn-sm pull-right">Add Payment</a></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="text-right">Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($payments as $p)<tr><td>{{ $p->payment_date }}</td><td>{{ $p->invoice_id }}</td><td>{{ ucfirst($p->payment_method) }}</td><td>{{ $p->reference_no }}</td><td class="text-right">{{ number_format($p->amount,2) }}</td><td>{{ ucfirst($p->status) }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('autoservice.payments.edit',$p->id) }}">Edit</a></td></tr>@endforeach
</tbody></table>{{ $payments->links() }}</div></div>
@endsection
