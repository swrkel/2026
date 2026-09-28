@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }} <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url(), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-warning">{{ $report['message'] }}</div>@endif
<div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Contact</th><th>Reference</th><th class="text-right">Invoice Total</th><th class="text-right">Paid</th><th class="text-right">Returns / Credits</th><th class="text-right">Outstanding</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ @format_date($row->transaction_date) }}</td><td>{{ $row->contact_name }}</td><td>{{ $row->reference_no }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->final_total, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->paid_amount, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->return_amount ?? 0, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->balance, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="3">Records: {{ $report['totals']['records'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['invoiced'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['paid'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['returns'] ?? 0, 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['outstanding'], 4, '.', '') }}</th></tr></tfoot></table>
</div></div>
</section>
@stop
