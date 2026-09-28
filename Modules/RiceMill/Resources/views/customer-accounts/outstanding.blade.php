@extends('RiceMill::layout')
@section('rcm-title','Customer Outstanding List')
@section('rcm-subtitle','Approved Rice Mill Sales Invoices that remain unpaid or partly paid')
@section('rcm-actions')
@can('rice_mill.customer_payments.create')<a class="rcm-btn primary" href="{{ route('rice-mill.customer-payments.create',request()->filled('customer_id')?['customer_id'=>request('customer_id')]:[]) }}"><i class="fa fa-money"></i> Add Customer Payment</a>@endcan
@endsection
@section('rcm-content')
@include('RiceMill::customer-accounts._customer-filter')
<div class="rcm-kpi-grid">
    @include('RiceMill::partials.kpi-card',['tone'=>'blue','icon'=>'fa fa-file-text-o','label'=>'Outstanding Invoices','value'=>number_format((int)($summary->invoice_count ?? 0),0)])
    @include('RiceMill::partials.kpi-card',['tone'=>'green','icon'=>'fa fa-money','label'=>'Invoice Value','value'=>number_format((float)($summary->invoice_total ?? 0),$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'orange','icon'=>'fa fa-check-circle','label'=>'Allocated Payments','value'=>number_format((float)($summary->paid_amount ?? 0),$rcmCurrencyPrecision)])
    @include('RiceMill::partials.kpi-card',['tone'=>'purple','icon'=>'fa fa-exclamation-circle','label'=>'Outstanding','value'=>number_format((float)($summary->outstanding_amount ?? 0),$rcmCurrencyPrecision)])
</div>
@if($unappliedAdvance > 0)
<div class="rcm-alert rcm-alert-info"><i class="fa fa-info-circle"></i><div>Unapplied customer advance available for the selected customer scope: <strong>{{ number_format($unappliedAdvance,$rcmCurrencyPrecision) }}</strong>. Advances are kept separate until allocated against specific invoices.</div></div>
@endif
<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-outstanding-table','exportName'=>'rice-mill-customer-outstanding','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'outstanding invoices'])
<div class="rcm-table-wrap"><table id="rcm-outstanding-table" class="rcm-table rcm-managed-table"><thead><tr><th>Invoice No.</th><th>Invoice Date</th><th>Customer</th><th>Due Date</th><th class="rcm-num">Invoice Amount</th><th class="rcm-num">Paid</th><th class="rcm-num">Outstanding</th><th>Age</th><th data-rcm-no-export>Action</th></tr></thead><tbody>
@forelse($rows as $r)
@php $due=\Illuminate\Support\Carbon::parse($r->due_date); $days=$due->isPast()&&!$due->isToday() ? $due->diffInDays(today()) : 0; @endphp
<tr><td>{{ $r->dispatch_no }}</td><td>{{ \Illuminate\Support\Carbon::parse($r->dispatch_date)->format('Y-m-d') }}</td><td>{{ $r->customer_name }}</td><td>{{ $due->format('Y-m-d') }}</td><td class="rcm-num">{{ number_format($r->net_total,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->paid_amount,$rcmCurrencyPrecision) }}</td><td class="rcm-num"><strong>{{ number_format($r->outstanding_amount,$rcmCurrencyPrecision) }}</strong></td><td>{{ $days>0 ? $days.' days overdue' : 'Current' }}</td><td><a class="rcm-btn secondary" href="{{ route('rice-mill.dispatch.show',$r->id) }}">Invoice</a> @can('rice_mill.customer_payments.create')<a class="rcm-btn primary" href="{{ route('rice-mill.customer-payments.create',['customer_id'=>$r->customer_id,'invoice_id'=>$r->id]) }}">Pay</a>@endcan</td></tr>
@empty<tr data-rcm-empty-row><td colspan="9" class="rcm-muted">No outstanding Sales Invoices match the selected filters.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}
</div>
@endsection
