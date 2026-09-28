@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }} <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url(), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-warning">{{ $report['message'] }}</div>@endif
<div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Contact</th><th>Reference</th><th class="text-right">Days</th><th class="text-right">0-30</th><th class="text-right">31-60</th><th class="text-right">61-90</th><th class="text-right">90+</th><th class="text-right">Total</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ @format_date($row->transaction_date) }}</td><td>{{ $row->contact_name }}</td><td>{{ $row->reference_no }}</td><td class="text-right">{{ $row->days }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->bucket_0_30, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->bucket_31_60, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->bucket_61_90, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->bucket_over_90, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->balance, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="4">Records: {{ $report['totals']['records'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['bucket_0_30'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['bucket_31_60'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['bucket_61_90'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['bucket_over_90'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['outstanding'], 4, '.', '') }}</th></tr></tfoot></table>
</div></div>
</section>
@stop
