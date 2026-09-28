@extends('layouts.app')
@section('title', __('restaurantnew::lang.advanced_pos'))

@section('content')
<section class="content-header restaurant-new-page-header">
    <h1>Restaurant Advanced POS <small>Split bill, hold/resume, merge and table operations</small></h1>
</section>
<section class="content restaurant-new-pos restaurant-new-advanced-pos">
    <div class="row rn-summary-row">
        <div class="col-md-3"><div class="rn-card rn-card-blue"><span>Running Orders</span><strong>{{ $orders->count() }}</strong></div></div>
        <div class="col-md-3"><div class="rn-card rn-card-green"><span>Split Billing</span><strong>Ready</strong></div></div>
        <div class="col-md-3"><div class="rn-card rn-card-orange"><span>Table Operations</span><strong>Enabled</strong></div></div>
        <div class="col-md-3"><div class="rn-card rn-card-purple"><span>Multi Payment</span><strong>Enabled</strong></div></div>
    </div>

    <div class="box box-solid rn-pos-box">
        <div class="box-header with-border">
            <h3 class="box-title">Live Restaurant Orders</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('restaurantnew.sales.create') }}" class="btn btn-primary btn-sm">Create Sale</a>
                <a href="{{ route('restaurantnew.kitchen.screen') }}" class="btn btn-warning btn-sm">Kitchen Screen</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped rn-datatable" id="rn_advanced_pos_table">
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Type</th>
                        <th>Table</th>
                        <th>Guests</th>
                        <th>Status</th>
                        <th>Kitchen</th>
                        <th>Payment</th>
                        <th class="text-right">Total</th>
                        <th width="260">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr data-order-id="{{ $order->id }}">
                            <td><strong>{{ $order->order_no }}</strong></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $order->order_type)) }}</td>
                            <td>{{ $order->table_id ?: '-' }}</td>
                            <td>{{ $order->guest_count ?: '-' }}</td>
                            <td><span class="label label-info">{{ $order->status }}</span></td>
                            <td><span class="label label-warning">{{ $order->kitchen_status }}</span></td>
                            <td><span class="label label-default">{{ $order->payment_status }}</span></td>
                            <td class="text-right">{{ number_format((float)$order->grand_total, 2) }}</td>
                            <td>
                                <button class="btn btn-xs btn-default rn-hold-order" data-url="{{ route('restaurantnew.orders.hold', $order->id) }}">Hold</button>
                                <button class="btn btn-xs btn-primary rn-split-bill" data-url="{{ route('restaurantnew.orders.split_bill', $order->id) }}">Split</button>
                                <button class="btn btn-xs btn-success rn-multi-payment" data-url="{{ route('restaurantnew.orders.multi_payments', $order->id) }}">Pay</button>
                                <button class="btn btn-xs btn-warning rn-transfer-table" data-url="{{ route('restaurantnew.orders.transfer_table', $order->id) }}">Transfer</button>
                                <button class="btn btn-xs btn-info rn-change-waiter" data-url="{{ route('restaurantnew.orders.change_waiter', $order->id) }}">Waiter</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('Modules/RestaurantNew/Resources/assets/js/advanced-pos.js') }}"></script>
@endsection
