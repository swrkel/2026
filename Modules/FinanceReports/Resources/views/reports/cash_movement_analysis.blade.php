@extends('layouts.app')
@section('title', 'Cash Movement Analysis - New')
@section('content')
<section class="content-header"><h1>Cash Movement Analysis - New</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.cash-movement-analysis-new')])
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th class="text-right">Cash/Bank Inflow</th><th class="text-right">Cash/Bank Outflow</th><th class="text-right">Net Movement</th><th class="text-right">Records</th></tr></thead><tbody>
@foreach($report['rows'] ?? [] as $row)<tr><td>{{ $row->date }}</td><td class="text-right">{{ number_format($row->inflow, 4) }}</td><td class="text-right">{{ number_format($row->outflow, 4) }}</td><td class="text-right">{{ number_format($row->net_movement, 4) }}</td><td class="text-right">{{ $row->records }}</td></tr>@endforeach
</tbody><tfoot><tr><th>Total</th><th class="text-right">{{ number_format($report['totals']['inflow'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['outflow'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['net_movement'] ?? 0, 4) }}</th><th class="text-right">{{ $report['totals']['records'] ?? 0 }}</th></tr></tfoot></table>
</div></div></section>
@endsection
