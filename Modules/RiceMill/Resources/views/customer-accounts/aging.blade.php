@extends('RiceMill::layout')
@section('rcm-title','Customer Aging')
@section('rcm-subtitle','Outstanding approved Rice Mill Sales Invoices grouped by due-date age')
@section('rcm-content')
@include('RiceMill::customer-accounts._customer-filter')
<div class="rcm-kpi-grid">
    @include('RiceMill::partials.kpi-card',['tone'=>'blue','icon'=>'fa fa-clock-o','label'=>'Current','value'=>number_format($totals->current,$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'green','icon'=>'fa fa-calendar','label'=>'1–30 Days','value'=>number_format($totals->d1_30,$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'orange','icon'=>'fa fa-hourglass-half','label'=>'31–90 Days','value'=>number_format($totals->d31_60+$totals->d61_90,$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'purple','icon'=>'fa fa-calculator','label'=>'Total Outstanding','value'=>number_format($totals->total,$rcmCurrencyPrecision)])
</div>
<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-aging-table','exportName'=>'rice-mill-customer-aging','serverPaged'=>false,'rowsLabel'=>'customers'])
<div class="rcm-table-wrap"><table id="rcm-aging-table" class="rcm-table rcm-managed-table"><thead><tr><th>Customer</th><th class="rcm-num">Invoices</th><th class="rcm-num">Current</th><th class="rcm-num">1–30</th><th class="rcm-num">31–60</th><th class="rcm-num">61–90</th><th class="rcm-num">91–120</th><th class="rcm-num">Over 120</th><th class="rcm-num">Total</th><th data-rcm-no-export>Action</th></tr></thead><tbody>
@forelse($rows as $r)
<tr><td>{{ $r->customer_name }}</td><td class="rcm-num">{{ $r->invoice_count }}</td><td class="rcm-num">{{ number_format($r->current,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->d1_30,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->d31_60,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->d61_90,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->d91_120,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->over_120,$rcmCurrencyPrecision) }}</td><td class="rcm-num"><strong>{{ number_format($r->total,$rcmCurrencyPrecision) }}</strong></td><td><a class="rcm-btn secondary" href="{{ route('rice-mill.customer-accounts.outstanding',['customer_id'=>$r->customer_id,'range'=>'all']) }}">Outstanding</a></td></tr>
@empty<tr data-rcm-empty-row><td colspan="10" class="rcm-muted">No outstanding balances exist for the selected scope.</td></tr>@endforelse
</tbody></table></div>
</div>
@endsection
