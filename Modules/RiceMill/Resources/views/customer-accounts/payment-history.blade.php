@extends('RiceMill::layout')
@section('rcm-title','Customer Payment History')
@section('rcm-actions')@if($canViewPayments)<a class="rcm-btn" href="{{ route('rice-mill.customer-accounts.payments',['customer_id'=>$selectedCustomerId]) }}"><i class="fa fa-plus"></i> Customer Payment</a>@endif@endsection
@section('rcm-content')
@include('RiceMill::customer-accounts.partials.sync-note',['syncPendingCount'=>0])
<div class="rcm-card">
    @include('RiceMill::partials.functionality-bar',['tableId'=>'rcm-customer-payment-history-table','exportName'=>'rice-mill-customer-payment-history','serverPaged'=>true,'paginator'=>$rows,'rowsLabel'=>'payments'])
    @include('RiceMill::customer-accounts.partials.filters',['filterAction'=>route('rice-mill.customer-accounts.payment-history')])
    <div class="rcm-table-wrap"><table id="rcm-customer-payment-history-table" class="rcm-table rcm-managed-table"><thead><tr><th>Date &amp; Time</th><th>Payment Ref.</th><th>Customer</th><th>Sales Invoice</th><th>Movement</th><th>Method</th><th class="rcm-num">Amount</th><th>Entered By</th><th>Note</th><th data-rcm-no-export>Action</th></tr></thead><tbody>
    @forelse($rows as $row)
        <tr><td>{{ $row->paid_on ? \Illuminate\Support\Carbon::parse($row->paid_on)->format('Y-m-d H:i:s') : '-' }}</td><td>{{ $row->payment_ref_no ?: ('Payment #'.$row->payment_id) }}</td><td>{{ $row->customer_name ?: ('Customer #'.$row->customer_id) }}</td><td>{{ $row->dispatch_no }}</td><td>{{ !empty($row->is_return)?'Payment Return':'Payment' }}</td><td>{{ ucwords(str_replace('_',' ',$row->method)) }}</td><td class="rcm-num">{{ !empty($row->is_return)?'-':'' }}{{ number_format($row->amount,$rcmCurrencyPrecision) }}</td><td>{{ $row->username ?: ('User #'.$row->created_by) }}</td><td>{{ $row->note ?: '-' }}</td><td><a class="rcm-btn" href="{{ route('rice-mill.dispatch.show',$row->dispatch_id) }}">Invoice</a></td></tr>
    @empty<tr data-rcm-empty-row><td colspan="10" class="rcm-muted">No Rice Mill customer payments found.</td></tr>@endforelse
    </tbody></table></div>
    {{ $rows->links() }}
</div>
@endsection
