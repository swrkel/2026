@extends('layouts.app')
@section('title', 'Budget vs Actual - New')
@section('content')
<section class="content-header"><h1>Budget vs Actual - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.budget-vs-actual-new')])
@include('financereports::layouts.toolbar')
@if(empty($report['has_budget_table']))<div class="alert alert-info">Budget table was not found, so budget amounts are shown as zero. Actual figures are still calculated from account transactions.</div>@endif
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Account</th><th>Section</th><th class="text-right">Budget</th><th class="text-right">Actual</th><th class="text-right">Variance</th><th class="text-right">Variance %</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row->account_name }} @if($row->account_number)<small>({{ $row->account_number }})</small>@endif</td><td>{{ $row->section }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->budget, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->actual, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->variance, 4, '.', '') }}</td><td class="text-right">{{ is_null($row->variance_pct) ? '-' : number_format($row->variance_pct, 2) . '%' }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="2">Totals / Records: {{ $report['totals']['records'] }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['budget'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['actual'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['variance'], 4, '.', '') }}</th><th></th></tr></tfoot></table></div></div>
</section>
@stop
