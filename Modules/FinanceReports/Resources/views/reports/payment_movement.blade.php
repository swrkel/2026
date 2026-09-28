@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }} <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url()])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-warning">{{ $report['message'] }}</div>@endif
<div class="row">
<div class="col-md-4"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Method Summary</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Method</th><th class="text-right">Amount</th><th class="text-right">Records</th></tr></thead><tbody>@foreach($report['by_method'] ?? [] as $m)<tr><td>{{ ucfirst($m->method) }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($m->amount, 4, '.', '') }}</td><td class="text-right">{{ $m->records }}</td></tr>@endforeach</tbody></table></div></div></div>
<div class="col-md-8"><div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Contact</th><th>Invoice/Ref</th><th>Method</th><th class="text-right">Amount</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ @format_date($row->paid_on) }}</td><td>{{ $row->contact_name }}</td><td>{{ $row->invoice_no ?: $row->ref_no }}</td><td>{{ ucfirst($row->method) }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->amount, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="4">Records: {{ $report['totals']['records'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['amount'], 4, '.', '') }}</th></tr></tfoot></table></div></div></div>
</div>
</section>
@stop
