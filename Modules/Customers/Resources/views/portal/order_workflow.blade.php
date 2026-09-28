@extends('customers::portal.layout')
@section('title', 'Order Workflow')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    @if(session('status'))
        <div class="dd-card"><div class="dd-card-body"><strong>{{ session('status.msg') }}</strong></div></div>
    @endif

    <div class="dd-card">
        <div class="dd-card-header clearfix">
            <h3 class="dd-card-title pull-left">Order Workflow - {{ $order->order_no }}</h3>
            <div class="pull-right dd-no-print">
                <a href="{{ route('customers.portal.orders.show', $order->id) }}" class="dd-btn dd-btn-default">Order Details</a>
                <a href="{{ route('customers.portal.orders.repeat', $order->id) }}" class="dd-btn dd-btn-primary">Repeat Order</a>
                <a href="{{ route('customers.portal.orders') }}" class="dd-btn dd-btn-default">Back</a>
            </div>
        </div>
        <div class="dd-card-body">
            <div class="row">
                <div class="col-md-3"><strong>Order No:</strong><br>{{ $order->order_no }}</div>
                <div class="col-md-3"><strong>Order Date:</strong><br>{{ $order->order_date }}</div>
                <div class="col-md-3"><strong>Required Date:</strong><br>{{ $order->required_date ?: '-' }}</div>
                <div class="col-md-3"><strong>Current Status:</strong><br><span class="dd-badge dd-badge-open">{{ ucwords(str_replace('_', ' ', $order->status)) }}</span></div>
            </div>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Status Timeline</h3></div>
        <div class="dd-card-body">
            @forelse($events as $event)
                <div class="dd-list-item">
                    <div class="dd-list-title">{{ ucwords(str_replace('_', ' ', $event->status ?? 'Status Update')) }}</div>
                    <div class="dd-list-meta">{{ !empty($event->created_at) ? date('Y-m-d H:i', strtotime($event->created_at)) : '' }}</div>
                    <div>{{ $event->remarks ?: '-' }}</div>
                </div>
            @empty
                <div class="dd-empty">No workflow updates found.</div>
            @endforelse
        </div>
    </div>

    <div class="dd-card dd-no-print">
        <div class="dd-card-header"><h3 class="dd-card-title">Available Actions</h3></div>
        <div class="dd-card-body">
            @if(in_array(strtolower($order->status ?? ''), ['draft', 'submitted', 'under_review', 'amendment_requested']))
                <a href="{{ route('customers.portal.orders.amend', $order->id) }}" class="dd-btn dd-btn-default">Request Amendment</a>
            @endif
            @if(!in_array(strtolower($order->status ?? ''), ['dispatched', 'delivered', 'cancelled', 'cancellation_requested']))
                <a href="{{ route('customers.portal.orders.cancel', $order->id) }}" class="dd-btn dd-btn-danger">Request Cancellation</a>
            @endif
            <a href="{{ route('customers.portal.orders.repeat', $order->id) }}" class="dd-btn dd-btn-primary">Repeat This Order</a>
        </div>
    </div>
</div>
@endsection
