@extends('layouts.app')
@section('title', 'Account Ledger - New')
@section('content')
<section class="content-header"><h1>Account Ledger - New <small>Finance Reports</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.account-ledger-new'), 'accounts' => $accounts])
@include('financereports::layouts.toolbar')
@if($report)
<div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr class="bg-gray"><th>Date</th><th>Reference</th><th>Note</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Running Balance</th></tr></thead><tbody>
<tr><td colspan="5" class="text-right"><strong>Opening Balance</strong></td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['opening_balance'], 4, '.', '') }}</td></tr>
@foreach($report['rows'] as $row)<tr><td>{{ \Carbon\Carbon::parse($row->operation_date)->format('Y-m-d') }}</td><td>{{ $row->ref_no ?? $row->id }}</td><td>{{ $row->note }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->debit, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->credit, 4, '.', '') }}</td><td class="text-right display_currency" data-currency_symbol="true">{{ number_format($row->running_balance, 4, '.', '') }}</td></tr>@endforeach
</tbody><tfoot><tr class="bg-gray"><th colspan="5" class="text-right">Closing Balance</th><th class="text-right display_currency" data-currency_symbol="true">{{ number_format($report['closing_balance'], 4, '.', '') }}</th></tr></tfoot></table>
</div></div>
@else
<div class="callout callout-info">Please select an account and generate the report.</div>
@endif
</section>
@stop
