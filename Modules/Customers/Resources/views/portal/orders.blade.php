@extends('customers::portal.layout')
@section('title', 'My Orders')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    <div class="dd-card">
        <div class="dd-card-header clearfix">
            <h3 class="dd-card-title pull-left">My Orders</h3>
            <div class="pull-right dd-no-print">
                <a href="{{ route('customers.portal.orders.create') }}" class="dd-btn dd-btn-primary">Place Order</a>
                <button type="button" onclick="window.print()" class="dd-btn dd-btn-default">Print</button>
            </div>
        </div>
        <div class="dd-card-body">
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order No</th>
                            <th>Required Date</th>
                            <th>Status</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Balance</th>
                            <th class="dd-no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '' }}</td>
                                <td><strong>{{ $row->order_no }}</strong></td>
                                <td>{{ $row->required_date ?? $row->ref_no ?? '' }}</td>
                                <td><span class="dd-badge dd-badge-open">{{ ucwords(str_replace('_', ' ', $row->status ?? $row->payment_status ?? 'Open')) }}</span></td>
                                <td class="text-right">{{ number_format((float)($row->qty ?? $row->total_qty ?? 0), 4) }}</td>
                                <td class="text-right">{{ number_format((float)($row->final_total ?? $row->total_amount ?? 0), 2) }}</td>
                                <td class="text-right"><strong>{{ number_format((float)($row->balance ?? 0), 2) }}</strong></td>
                                <td class="dd-no-print">
                                    @if(($row->source ?? '') === 'portal')
                                        <a class="dd-btn dd-btn-default" href="{{ route('customers.portal.orders.show', $row->id) }}">View</a>
                                        <a class="dd-btn dd-btn-default" href="{{ route('customers.portal.orders.workflow', $row->id) }}">Track</a>
                                        <a class="dd-btn dd-btn-primary" href="{{ route('customers.portal.orders.repeat', $row->id) }}">Repeat</a>
                                    @else
                                        <span class="dd-badge dd-badge-read">ERP Order</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
