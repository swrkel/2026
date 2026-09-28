@extends('layouts.app')
@section('title', 'Balance Sheet - New')
@section('content')
<section class="content-header"><h1>Balance Sheet - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.statement_tabs', ['active_report_tab' => $active_report_tab])
@include('financereports::layouts.filter', ['action' => route('finance-reports.balance-sheet-new'), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
<div class="row">
@foreach(['Assets' => 'assets', 'Liabilities' => 'liabilities', 'Equity' => 'equity'] as $label => $key)
<div class="col-md-4"><div class="box box-solid"><div class="box-header"><h3 class="box-title">{{ $label }}</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tbody>
@foreach($report[$key] as $row)<tr><td>{{ $row->name }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->balance, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th>Total {{ $label }}</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals'][strtolower($label)], 4, '.', '') }}</th></tr></tfoot></table></div></div></div>
@endforeach
</div>
<div class="box box-solid"><div class="box-body"><strong>Difference:</strong> <span class="display_currency" data-currency_symbol="true">{{ number_format($report['totals']['difference'], 4, '.', '') }}</span></div></div>
</section>
@stop
