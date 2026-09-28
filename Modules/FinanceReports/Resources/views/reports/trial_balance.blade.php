@extends('layouts.app')
@section('title', 'Trial Balance - New')
@section('content')
<section class="content-header"><h1>Trial Balance - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.statement_tabs', ['active_report_tab' => $active_report_tab])
@include('financereports::layouts.filter', ['action' => route('finance-reports.trial-balance-new'), 'asAtMode' => true, 'as_at' => $as_at])
@include('financereports::layouts.toolbar')
<div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr class="bg-gray"><th>Account No</th><th>Account Name</th><th>Type</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row->account_number }}</td><td>{{ $row->name }}</td><td>{{ $row->account_type_name ?: $row->account_group_name }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->debit, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->credit, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="3" class="text-right">Total</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['debit'], 4, '.', '') }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['credit'], 4, '.', '') }}</th></tr><tr><th colspan="3" class="text-right">Difference</th><th colspan="2" class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['difference'], 4, '.', '') }}</th></tr></tfoot></table>
</div></div>
</section>
@stop
