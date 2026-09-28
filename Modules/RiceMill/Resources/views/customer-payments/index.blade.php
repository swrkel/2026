@extends('RiceMill::layout')
@section('rcm-title','Customer Payments')
@section('rcm-subtitle','Rice Mill customer receipts, bill allocations, advances and payment history')
@section('rcm-actions')
@can('rice_mill.customer_payments.create')<a class="rcm-btn primary" href="{{ route('rice-mill.customer-payments.create') }}"><i class="fa fa-plus"></i> Add Customer Payment</a>@endcan
@endsection
@section('rcm-content')
@include('RiceMill::customer-accounts._customer-filter')
<div class="rcm-card">
@include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-customer-payments-table','exportName'=>'rice-mill-customer-payments','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'payments'])
<div class="rcm-table-wrap"><table id="rcm-customer-payments-table" class="rcm-table rcm-managed-table"><thead><tr><th>Payment No.</th><th>Date & Time</th><th>Customer</th><th>Method</th><th>Reference</th><th class="rcm-num">Amount</th><th class="rcm-num">Allocated</th><th class="rcm-num">Advance</th><th>Status</th><th data-rcm-no-export>Action</th></tr></thead><tbody>
@forelse($rows as $r)
<tr><td>{{ $r->payment_no }}</td><td>{{ \Illuminate\Support\Carbon::parse($r->payment_date)->format('Y-m-d H:i') }}</td><td>{{ $r->customer_name }}</td><td>{{ app(\Modules\RiceMill\Services\CustomerAccountsService::class)->methodLabel($r->method) }}</td><td>{{ $r->reference_no ?: '-' }}</td><td class="rcm-num">{{ number_format($r->amount,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->allocated_amount,$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($r->advance_amount,$rcmCurrencyPrecision) }}</td><td><span class="rcm-status rcm-status-{{ $r->status==='posted'?'approved':'cancelled' }}">{{ ucfirst($r->status) }}</span></td><td><a class="rcm-btn secondary" href="{{ route('rice-mill.customer-payments.show',$r->id) }}"><i class="fa fa-eye"></i> View</a></td></tr>
@empty<tr data-rcm-empty-row><td colspan="10" class="rcm-muted">No customer payments match the selected filters.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}
</div>
@endsection
