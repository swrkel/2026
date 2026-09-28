@extends('customers::portal.layout')
@section('title', 'Dealer Order')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    @if(session('status'))
        <div class="dd-card"><div class="dd-card-body"><strong>{{ session('status.msg') }}</strong></div></div>
    @endif

    <div class="dd-card">
        <div class="dd-card-header clearfix">
            <h3 class="dd-card-title pull-left">Order {{ $order->order_no }}</h3>
            <div class="pull-right dd-no-print">
                <a href="{{ route('customers.portal.orders.workflow', $order->id) }}" class="dd-btn dd-btn-default">Workflow</a>
                <a href="{{ route('customers.portal.orders.repeat', $order->id) }}" class="dd-btn dd-btn-primary">Repeat</a>
                @if(in_array(strtolower($order->status ?? ''), ['draft', 'submitted', 'under_review', 'amendment_requested']))
                    <a href="{{ route('customers.portal.orders.amend', $order->id) }}" class="dd-btn dd-btn-default">Amend</a>
                @endif
                @if(!in_array(strtolower($order->status ?? ''), ['dispatched', 'delivered', 'cancelled', 'cancellation_requested']))
                    <a href="{{ route('customers.portal.orders.cancel', $order->id) }}" class="dd-btn dd-btn-danger">Cancel</a>
                @endif
                <button type="button" onclick="window.print()" class="dd-btn dd-btn-default">Print</button>
                <a href="{{ route('customers.portal.orders') }}" class="dd-btn dd-btn-default">Back</a>
            </div>
        </div>
        <div class="dd-card-body">
            <div class="row">
                <div class="col-md-3"><strong>Order Date:</strong><br>{{ $order->order_date }}</div>
                <div class="col-md-3"><strong>Required Date:</strong><br>{{ $order->required_date ?: '-' }}</div>
                <div class="col-md-3"><strong>Status:</strong><br><span class="dd-badge dd-badge-open">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span></div>
                <div class="col-md-3"><strong>Total Amount:</strong><br>{{ number_format((float)$order->total_amount, 2) }}</div>
            </div>
            @if(!empty($order->remarks))
                <hr><strong>Remarks:</strong> {{ $order->remarks }}
            @endif
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header">
            <h3 class="dd-card-title">Order Items</h3>
        </div>
        <div class="dd-card-body">
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Unit</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Line Total</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $line)
                            <tr>
                                <td>{{ $line->sku }}</td>
                                <td><strong>{{ $line->product_name }}</strong></td>
                                <td>{{ $line->unit }}</td>
                                <td class="text-right">{{ number_format((float)$line->quantity, 4) }}</td>
                                <td class="text-right">{{ number_format((float)$line->unit_price, 2) }}</td>
                                <td class="text-right">{{ number_format((float)$line->line_total, 2) }}</td>
                                <td>{{ $line->remarks }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Total</th>
                            <th class="text-right">{{ number_format((float)$order->total_qty, 4) }}</th>
                            <th></th>
                            <th class="text-right">{{ number_format((float)$order->total_amount, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
