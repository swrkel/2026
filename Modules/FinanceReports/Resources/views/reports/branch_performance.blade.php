@extends('layouts.app')
@section('title', 'Branch Performance - New')
@section('content')
<section class="content-header"><h1>Branch Performance - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.branch-performance-new')])
@include('financereports::layouts.toolbar')
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Branch / Location</th><th class="text-right">Income</th><th class="text-right">Expenses</th><th class="text-right">Net Profit/Loss</th><th class="text-right">Assets</th><th class="text-right">Liabilities</th><th class="text-right">Net Margin</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row->location_name }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->income, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->expenses, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->net_profit, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->assets, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->liabilities, 4, '.', '') }}</td><td class="text-right">{{ number_format($row->net_margin, 2) }}%</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th>Branches: {{ $report['totals']['branches'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['income'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['expenses'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['net_profit'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['assets'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['liabilities'], 4, '.', '') }}</th><th></th></tr></tfoot></table></div></div>
</section>
@stop
