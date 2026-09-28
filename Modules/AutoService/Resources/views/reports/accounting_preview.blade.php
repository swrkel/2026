@extends('autoservice::layouts.master')
@section('title','Accounting Posting Preview')
@section('autoservice_content')
<div class="box"><div class="box-header with-border"><h3 class="box-title">Invoice Accounting Preview</h3></div><div class="box-body">
<p><strong>Invoice:</strong> {{ $summary['invoice']->invoice_no ?? '' }} | <strong>Total:</strong> {{ number_format($summary['grand_total'], 2) }} | <strong>Balance:</strong> {{ number_format($summary['balance_due'], 2) }}</p>
<table class="table table-bordered"><tr><th>Account ID</th><th>Type</th><th class="text-right">Amount</th><th>Description</th></tr>
@foreach($rows as $row)<tr><td>{{ $row['account'] ?: 'Not mapped' }}</td><td>{{ ucfirst($row['type']) }}</td><td class="text-right">{{ number_format($row['amount'], 2) }}</td><td>{{ $row['description'] }}</td></tr>@endforeach
</table>
<p class="text-muted">This page previews the standalone Auto Service accounting split. Actual posting remains controlled by the business accounting setting and account mappings.</p>
</div></div>
@endsection
