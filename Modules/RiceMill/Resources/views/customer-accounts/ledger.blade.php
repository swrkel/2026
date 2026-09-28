@extends('RiceMill::layout')
@section('rcm-title','Customer Ledgers')
@section('rcm-subtitle','Rice Mill approved Sales Invoices and Customer Payment movements with running balance')
@section('rcm-actions')
<a class="rcm-btn secondary" href="{{ route('rice-mill.customer-accounts.outstanding') }}"><i class="fa fa-list-alt"></i> Outstanding</a>
<a class="rcm-btn secondary" href="{{ route('rice-mill.customer-payments.index') }}"><i class="fa fa-money"></i> Payments</a>
@endsection
@section('rcm-content')
@include('RiceMill::customer-accounts._customer-filter',['customerRequired'=>true])

@if($customerId)
<div class="rcm-kpi-grid">
    @include('RiceMill::partials.kpi-card',['tone'=>'blue','icon'=>'fa fa-balance-scale','label'=>'Opening Balance','value'=>number_format($ledger['opening_balance'],$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'green','icon'=>'fa fa-arrow-circle-up','label'=>'Total Debit','value'=>number_format($ledger['total_debit'],$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'orange','icon'=>'fa fa-arrow-circle-down','label'=>'Total Credit','value'=>number_format($ledger['total_credit'],$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'purple','icon'=>'fa fa-calculator','label'=>'Closing Balance','value'=>number_format($ledger['closing_balance'],$rcmCurrencyPrecision)])
</div>
<div class="rcm-card">
    <div class="rcm-panel-head"><h3>{{ $customerName }} — Ledger</h3><span class="rcm-panel-hint">Draft Sales Invoices are excluded.</span></div>
    @include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-customer-ledger-table','exportName'=>'rice-mill-customer-ledger','serverPaged'=>false,'rowsLabel'=>'movements'])
    <div class="rcm-table-wrap">
        <table id="rcm-customer-ledger-table" class="rcm-table rcm-managed-table">
            <thead><tr><th>Date & Time</th><th>Type</th><th>Reference</th><th>Method</th><th>Note</th><th class="rcm-num">Debit</th><th class="rcm-num">Credit</th><th class="rcm-num">Balance</th><th data-rcm-no-export>Action</th></tr></thead>
            <tbody>
            @forelse($ledger['entries'] as $row)
                <tr>
                    <td>{{ $row->date->format('Y-m-d H:i') }}</td><td>{{ $row->type }}</td><td>{{ $row->reference }}</td><td>{{ $row->method ?: '-' }}</td><td>{{ $row->note ?: '-' }}</td>
                    <td class="rcm-num">{{ $row->debit ? number_format($row->debit,$rcmCurrencyPrecision) : '-' }}</td>
                    <td class="rcm-num">{{ $row->credit ? number_format($row->credit,$rcmCurrencyPrecision) : '-' }}</td>
                    <td class="rcm-num"><strong>{{ number_format($row->balance,$rcmCurrencyPrecision) }}</strong></td>
                    <td>
                        @if($row->dispatch_id)<a class="rcm-btn secondary" href="{{ route('rice-mill.dispatch.show',$row->dispatch_id) }}">Invoice</a>@endif
                        @if($row->payment_id)<a class="rcm-btn secondary" href="{{ route('rice-mill.customer-payments.show',$row->payment_id) }}">Payment</a>@endif
                    </td>
                </tr>
            @empty<tr data-rcm-empty-row><td colspan="9" class="rcm-muted">No movements exist for the selected date range.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<div class="rcm-alert rcm-alert-info"><i class="fa fa-info-circle"></i><div>Select a customer to open the Rice Mill customer ledger.</div></div>
@endif
@endsection
