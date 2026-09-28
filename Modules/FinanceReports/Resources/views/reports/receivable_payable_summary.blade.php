@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }} <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url(), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-warning">{{ $report['message'] }}</div>@endif
<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Summary</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Contact</th><th>Mobile</th><th class="text-right">Invoices</th><th class="text-right">Invoice Total</th><th class="text-right">Paid</th><th class="text-right">Returns / Credits</th><th class="text-right">Outstanding</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row->contact_name }}</td><td>{{ $row->mobile }}</td><td class="text-right">{{ $row->invoice_count }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->final_total, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->paid_amount, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->return_amount ?? 0, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->balance, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="3">Contacts: {{ $report['totals']['contacts'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['invoiced'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['paid'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['returns'] ?? 0, 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['outstanding'], 4, '.', '') }}</th></tr></tfoot></table>
</div></div>
<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Aging Total</h3></div><div class="box-body"><div class="row"><div class="col-md-3"><strong>0-30:</strong> {{ number_format($report['aging_totals']['bucket_0_30'] ?? 0, 4) }}</div><div class="col-md-3"><strong>31-60:</strong> {{ number_format($report['aging_totals']['bucket_31_60'] ?? 0, 4) }}</div><div class="col-md-3"><strong>61-90:</strong> {{ number_format($report['aging_totals']['bucket_61_90'] ?? 0, 4) }}</div><div class="col-md-3"><strong>90+:</strong> {{ number_format($report['aging_totals']['bucket_over_90'] ?? 0, 4) }}</div></div></div></div>
</section>
@stop
