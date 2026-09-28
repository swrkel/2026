@extends('layouts.app')
@section('title', 'Profit & Loss - New')
@section('content')
<section class="content-header"><h1>Profit & Loss - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.statement_tabs', ['active_report_tab' => $active_report_tab])
@include('financereports::layouts.filter', ['action' => route('finance-reports.profit-loss-new'), 'autoSubmitDate' => true])
@include('financereports::layouts.toolbar', ['table' => 'fr_profit_breakdown_table', 'printRoot' => 'fr_profit_loss_report'])

<div id="fr_profit_loss_report">
<div class="fr-profit-print-header">
    <h2>Profit &amp; Loss - New</h2>
    <div>
        Period: {{ $start }} to {{ $end }}
        &nbsp;&middot;&nbsp;
        Location: {{ !empty($location_id) ? ($locations->get($location_id) ?? 'Selected Location') : 'All Locations' }}
    </div>
</div>
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
{{-- IS2059: the nine profit breakdowns, tab-wise. The income/expenses
     summary above is unchanged - this is added beneath it. --}}
@include('financereports::reports.partials.profit_breakdown')

<div class="box box-solid"><div class="box-body"><h4>Net Profit / Loss: <span class="display_currency" data-currency_symbol="true">{{ number_format($report['totals']['net_profit'], 4, '.', '') }}</span></h4></div></div>
</div>
</section>

<style>
    .fr-profit-print-header { display: none; }

    /*
     * IS2220 print layout.
     *
     * The screen report stays unchanged. During Print the toolbar clones
     * #fr_profit_loss_report into a direct child of <body>. These rules only
     * target that print shell, so they cannot disturb the normal report UI.
     */
    @media print {
        @page { size: A4 landscape; margin: 9mm; }

        body.fr-printing .fr-print-shell {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 10px !important;
            color: #111 !important;
        }

        body.fr-printing .fr-print-shell .no-print,
        body.fr-printing .fr-print-shell .fr-profit-tabs,
        body.fr-printing .fr-print-shell .fr-profit-split {
            display: none !important;
        }

        body.fr-printing .fr-print-shell .fr-profit-print-header {
            display: block !important;
            margin: 0 0 10px !important;
        }

        body.fr-printing .fr-print-shell .fr-profit-print-header h2 {
            margin: 0 0 3px !important;
            font-size: 18px !important;
        }

        body.fr-printing .fr-print-shell .fr-profit-print-header div {
            color: #64748b !important;
            font-size: 9px !important;
        }

        body.fr-printing .fr-print-shell .fr-profit-print-title {
            display: block !important;
        }

        body.fr-printing .fr-print-shell .box {
            border: 1px solid #d8dee6 !important;
            box-shadow: none !important;
            margin: 0 0 10px !important;
            background: #fff !important;
        }

        body.fr-printing .fr-print-shell .box-header {
            padding: 7px 9px !important;
            border-bottom: 1px solid #d8dee6 !important;
        }

        body.fr-printing .fr-print-shell .box-title {
            font-size: 13px !important;
            font-weight: 700 !important;
        }

        body.fr-printing .fr-print-shell .box-body {
            padding: 7px 9px !important;
        }

        body.fr-printing .fr-print-shell .table-responsive,
        body.fr-printing .fr-print-shell .fr-profit-table-wrap {
            overflow: visible !important;
            width: 100% !important;
        }

        body.fr-printing .fr-print-shell table,
        body.fr-printing .fr-print-shell .fr-profit-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: auto !important;
            border-collapse: collapse !important;
            font-size: 9px !important;
        }

        body.fr-printing .fr-print-shell th,
        body.fr-printing .fr-print-shell td {
            padding: 4px 5px !important;
            border: 1px solid #cbd5e1 !important;
            white-space: normal !important;
        }

        body.fr-printing .fr-print-shell thead {
            display: table-header-group !important;
        }

        body.fr-printing .fr-print-shell tfoot {
            display: table-footer-group !important;
        }

        body.fr-printing .fr-print-shell tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        body.fr-printing .fr-print-shell .row {
            margin-left: -4px !important;
            margin-right: -4px !important;
        }

        body.fr-printing .fr-print-shell .col-md-6 {
            float: left !important;
            width: 50% !important;
            padding-left: 4px !important;
            padding-right: 4px !important;
        }

        body.fr-printing .fr-print-shell .row:after {
            content: '';
            display: table;
            clear: both;
        }

        body.fr-printing .fr-print-shell .fr-profit-negative {
            color: #b91c1c !important;
        }
    }
</style>

@stop
