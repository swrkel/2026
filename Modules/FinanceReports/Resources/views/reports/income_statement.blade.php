@extends('layouts.app')
@section('title', 'Income Statement - New')
@section('content')
<section class="content-header"><h1>Income Statement - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.statement_tabs', ['active_report_tab' => $active_report_tab])
@include('financereports::layouts.filter', ['action' => route('finance-reports.income-statement-new')])
@include('financereports::layouts.toolbar')

<div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">Financial Performance Summary</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-condensed">
            <tbody>
                <tr><th>Sales / Operating Revenue</th><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['revenue'] ?? 0, 4, '.', '') }}</td></tr>
                <tr><th>Cost of Goods Sold (COGS)</th><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['cogs'] ?? 0, 4, '.', '') }}</td></tr>
                <tr class="bg-gray"><th>Gross Profit / (Loss)</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['gross_profit'] ?? 0, 4, '.', '') }}</th></tr>
                <tr><th>Other Income</th><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['other_income'] ?? 0, 4, '.', '') }}</td></tr>
                <tr><th>Operating / Other Expenses</th><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['operating_expenses'] ?? 0, 4, '.', '') }}</td></tr>
                <tr class="bg-gray"><th>Net Profit / (Loss)</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['net_profit'] ?? 0, 4, '.', '') }}</th></tr>
            </tbody>
        </table>
    </div>
</div>
<div class="row">
    <div class="col-md-6"><div class="box box-solid"><div class="box-header"><h3 class="box-title">Income</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tbody>
        @foreach($report['income'] as $row)<tr><td>{{ $row->name }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->amount, 4, '.', '') }}</td></tr>@endforeach
        </tbody><tfoot><tr class="bg-gray"><th>Total Income</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['income'], 4, '.', '') }}</th></tr></tfoot></table></div></div></div>
    <div class="col-md-6"><div class="box box-solid"><div class="box-header"><h3 class="box-title">Expenses</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tbody>
        @foreach($report['expenses'] as $row)<tr><td>{{ $row->name }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->amount, 4, '.', '') }}</td></tr>@endforeach
        </tbody><tfoot><tr class="bg-gray"><th>Total Expenses</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['totals']['expenses'], 4, '.', '') }}</th></tr></tfoot></table></div></div></div>
</div>
<div class="box box-solid"><div class="box-body"><h4>Net Profit / Loss: <span class="display_currency" data-currency_symbol="true">{{ number_format($report['totals']['net_profit'], 4, '.', '') }}</span></h4></div></div>
</section>
@stop
