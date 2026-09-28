@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }} <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => $title === 'Revenue Analysis - New' ? route('finance-reports.revenue-analysis-new') : route('finance-reports.expense-analysis-new')])
@include('financereports::layouts.toolbar')
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Account</th><th>Group</th><th class="text-right">Amount</th><th class="text-right">%</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row->name }} @if($row->account_number)<small>({{ $row->account_number }})</small>@endif</td><td>{{ $row->account_group_name ?: $row->account_type_name }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->amount, 4, '.', '') }}</td><td class="text-right">{{ number_format($row->percentage, 2) }}%</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="2">Total / Records: {{ $report['totals']['records'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['amount'], 4, '.', '') }}</th><th></th></tr></tfoot></table></div></div>
</section>
@stop
