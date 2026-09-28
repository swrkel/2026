@extends('RiceMill::layout')
@section('rcm-title','Customer Statement')
@section('rcm-subtitle','Opening balance, approved Rice Mill Sales Invoices, payments and closing balance')
@section('rcm-content')
@include('RiceMill::customer-accounts._customer-filter',['customerRequired'=>true])
@if($customerId)
<div class="rcm-kpi-grid">
    @include('RiceMill::partials.kpi-card',['tone'=>'blue','icon'=>'fa fa-balance-scale','label'=>'Opening Balance','value'=>number_format($ledger['opening_balance'],$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'green','icon'=>'fa fa-list-alt','label'=>'Current Outstanding','value'=>number_format($summary->outstanding,$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'orange','icon'=>'fa fa-money','label'=>'Unapplied Advance','value'=>number_format($summary->unapplied_advance,$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'purple','icon'=>'fa fa-calculator','label'=>'Closing Balance','value'=>number_format($ledger['closing_balance'],$rcmCurrencyPrecision)])
</div>
<div class="rcm-card"><div class="rcm-panel-head"><h3>{{ $customerName }} — Statement</h3><span class="rcm-panel-hint">Draft Sales Invoices are excluded.</span></div>
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-statement-table','exportName'=>'rice-mill-customer-statement','serverPaged'=>false,'rowsLabel'=>'statement rows'])
<div class="rcm-table-wrap"><table id="rcm-statement-table" class="rcm-table rcm-managed-table"><thead><tr><th>Date & Time</th><th>Description</th><th>Reference</th><th>Method</th><th class="rcm-num">Debit</th><th class="rcm-num">Credit</th><th class="rcm-num">Balance</th></tr></thead><tbody>
<tr><td>-</td><td>Opening Balance</td><td>-</td><td>-</td><td class="rcm-num">{{ $ledger['opening_balance']>0?number_format($ledger['opening_balance'],$rcmCurrencyPrecision):'-' }}</td><td class="rcm-num">{{ $ledger['opening_balance']<0?number_format(abs($ledger['opening_balance']),$rcmCurrencyPrecision):'-' }}</td><td class="rcm-num"><strong>{{ number_format($ledger['opening_balance'],$rcmCurrencyPrecision) }}</strong></td></tr>
@foreach($ledger['entries'] as $r)<tr><td>{{ $r->date->format('Y-m-d H:i') }}</td><td>{{ $r->type }}{{ $r->note?' - '.$r->note:'' }}</td><td>{{ $r->reference }}</td><td>{{ $r->method ?: '-' }}</td><td class="rcm-num">{{ $r->debit?number_format($r->debit,$rcmCurrencyPrecision):'-' }}</td><td class="rcm-num">{{ $r->credit?number_format($r->credit,$rcmCurrencyPrecision):'-' }}</td><td class="rcm-num"><strong>{{ number_format($r->balance,$rcmCurrencyPrecision) }}</strong></td></tr>@endforeach
</tbody></table></div></div>
@else<div class="rcm-alert rcm-alert-info"><i class="fa fa-info-circle"></i><div>Select a customer to generate the statement.</div></div>@endif
@endsection
