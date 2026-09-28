@extends('RiceMill::layout')
@section('rcm-title','View Customer Payment')
@section('rcm-subtitle','Payment receipt, selected outstanding bills and allocation details')
@section('rcm-actions')
<a class="rcm-btn secondary" href="{{ route('rice-mill.customer-payments.index') }}"><i class="fa fa-list"></i> Payment History</a>
@if($payment->status==='posted')@can('rice_mill.customer_payments.create')<a class="rcm-btn primary" href="{{ route('rice-mill.customer-payments.create',['customer_id'=>$payment->customer_id]) }}"><i class="fa fa-plus"></i> New Payment</a>@endcan@endif
@endsection
@section('rcm-content')
<div class="rcm-kpi-grid">
@include('RiceMill::partials.kpi-card',['tone'=>'blue','icon'=>'fa fa-money','label'=>'Payment Amount','value'=>number_format($payment->amount,$rcmCurrencyPrecision)])
@include('RiceMill::partials.kpi-card',['tone'=>'green','icon'=>'fa fa-check-circle','label'=>'Allocated','value'=>number_format($payment->allocated_amount,$rcmCurrencyPrecision)])
@include('RiceMill::partials.kpi-card',['tone'=>'orange','icon'=>'fa fa-university','label'=>'Advance','value'=>number_format($payment->advance_amount,$rcmCurrencyPrecision)])
@include('RiceMill::partials.kpi-card',['tone'=>'purple','icon'=>'fa fa-info-circle','label'=>'Status','value'=>ucfirst($payment->status)])
</div>
<div class="rcm-card"><div class="rcm-panel-head"><h3>{{ $payment->payment_no }}</h3><span class="rcm-panel-hint">{{ optional($customer)->name ?: 'Customer #'.$payment->customer_id }}</span></div>
<div class="rcm-table-wrap"><table class="rcm-table"><tbody>
<tr><th>Date & Time</th><td>{{ \Illuminate\Support\Carbon::parse($payment->payment_date)->format('Y-m-d H:i') }}</td><th>Method</th><td>{{ app(\Modules\RiceMill\Services\CustomerAccountsService::class)->methodLabel($payment->method) }}</td></tr>
<tr><th>Reference</th><td>{{ $payment->reference_no ?: '-' }}</td><th>Cheque / Bank</th><td>{{ trim(($payment->cheque_number?:'').' '.($payment->bank_name?:'')) ?: '-' }}</td></tr>
<tr><th>Note</th><td colspan="3">{{ $payment->note ?: '-' }}</td></tr>
@if($payment->status==='reversed')<tr><th>Reversed At</th><td>{{ optional($payment->reversed_at)->format('Y-m-d H:i') }}</td><th>Reason</th><td>{{ $payment->reversal_note ?: '-' }}</td></tr>@endif
</tbody></table></div></div>
<div class="rcm-card"><div class="rcm-panel-head"><h3>Bill Allocations</h3><span class="rcm-panel-hint">Only posted payments reduce outstanding invoices.</span></div>
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-payment-allocation-table','exportName'=>'rice-mill-payment-'.$payment->payment_no,'serverPaged'=>false,'dateEnabled'=>false,'rowsLabel'=>'allocations'])
<div class="rcm-table-wrap"><table id="rcm-payment-allocation-table" class="rcm-table rcm-managed-table"><thead><tr><th>Invoice No.</th><th>Invoice Date</th><th class="rcm-num">Invoice Amount</th><th class="rcm-num">Allocated Amount</th><th data-rcm-no-export>Action</th></tr></thead><tbody>
@forelse($allocations as $a)<tr><td>{{ $a->dispatch_no }}</td><td>{{ $a->dispatch_date }}</td><td class="rcm-num">{{ number_format($a->net_total,$rcmCurrencyPrecision) }}</td><td class="rcm-num"><strong>{{ number_format($a->amount,$rcmCurrencyPrecision) }}</strong></td><td><a class="rcm-btn secondary" href="{{ route('rice-mill.dispatch.show',$a->dispatch_id) }}">View Invoice</a></td></tr>@empty<tr data-rcm-empty-row><td colspan="5" class="rcm-muted">No bill allocations. The payment is held as Customer Advance.</td></tr>@endforelse
</tbody></table></div></div>
@if($payment->status==='posted')@can('rice_mill.customer_payments.reverse')
<div class="rcm-card"><div class="rcm-panel-head"><h3>Reverse Payment</h3><span class="rcm-panel-hint">Reversal restores the allocated invoices to outstanding status. The original payment is retained for audit.</span></div><form method="post" action="{{ route('rice-mill.customer-payments.reverse',$payment->id) }}" onsubmit="return confirm('Reverse this customer payment? This will restore its bill allocations.');">@csrf<div class="rcm-form-grid"><div class="rcm-field" style="grid-column:span 3"><label>Reversal Reason</label><input name="reversal_note" maxlength="500" placeholder="Reason for reversal"></div><div class="rcm-field" style="align-self:end"><button class="rcm-btn danger" type="submit"><i class="fa fa-undo"></i> Reverse Payment</button></div></div></form></div>
@endcan@endif
@endsection
