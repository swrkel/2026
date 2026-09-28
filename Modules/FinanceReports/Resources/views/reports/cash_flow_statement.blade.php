@extends('layouts.app')
@section('title', 'Cash Flow Statement - New')
@section('content')
<section class="content-header"><h1>Cash Flow Statement - New</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.cash-flow-statement-new')])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-info">{{ $report['message'] }}</div>@endif
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Section</th><th>Line</th><th class="text-right">Amount</th></tr></thead><tbody>
<tr><th colspan="2">Opening Cash & Bank</th><th class="text-right">{{ number_format($report['opening_cash'] ?? 0, 4) }}</th></tr>
@foreach(['operating'=>'Operating Activities','investing'=>'Investing Activities','financing'=>'Financing Activities'] as $key=>$label)
<tr class="bg-gray"><th colspan="3">{{ $label }}</th></tr>
@foreach($report[$key] ?? [] as $row)<tr><td>{{ $label }}</td><td>{{ $row->line }}</td><td class="text-right">{{ number_format($row->amount, 4) }}</td></tr>@endforeach
<tr><th colspan="2">Total {{ $label }}</th><th class="text-right">{{ number_format($report['totals'][$key] ?? 0, 4) }}</th></tr>
@endforeach
<tr><th colspan="2">Net Cash Movement</th><th class="text-right">{{ number_format($report['net_movement'] ?? 0, 4) }}</th></tr>
<tr><th colspan="2">Closing Cash & Bank</th><th class="text-right">{{ number_format($report['closing_cash'] ?? 0, 4) }}</th></tr>
</tbody></table></div></div></section>
@endsection
