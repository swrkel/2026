@extends('autoservice::layouts.master')
@section('title','Invoice Aging / Outstanding')
@section('autoservice_content')
<div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped">
<tr><th>Invoice No</th><th>Date</th><th>Job No</th><th>Vehicle</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Accounting</th></tr>
@foreach($rows as $row)
<tr>
<td><a href="{{ route('autoservice.invoices.show', $row->id) }}">{{ $row->invoice_no }}</a></td>
<td>{{ $row->invoice_date ?? optional($row->created_at)->format('Y-m-d') }}</td>
<td>{{ $row->job_no }}</td><td>{{ $row->registration_no }}</td>
<td class="text-right">{{ number_format($row->grand_total, 2) }}</td><td class="text-right">{{ number_format($row->paid_total, 2) }}</td><td class="text-right">{{ number_format($row->balance_due, 2) }}</td>
<td>{{ ucwords(str_replace('_',' ', $row->accounting_status ?? 'not_posted')) }} @if(($row->accounting_status ?? '') !== 'posted') <a class="btn btn-xs btn-default" href="{{ route('autoservice.reports.accounting_preview', $row->id) }}">Preview</a> @endif</td>
</tr>
@endforeach
</table>{{ $rows->appends(request()->query())->links() }}</div></div>
@endsection
