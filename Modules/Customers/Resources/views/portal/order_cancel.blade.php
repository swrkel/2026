@extends('customers::portal.layout')
@section('title', 'Request Order Cancellation')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    <form method="POST" action="{{ route('customers.portal.orders.cancel.store', $order->id) }}">
        @csrf
        <div class="dd-card">
            <div class="dd-card-header clearfix">
                <h3 class="dd-card-title pull-left">Request Cancellation - {{ $order->order_no }}</h3>
                <div class="pull-right dd-no-print">
                    <a href="{{ route('customers.portal.orders.show', $order->id) }}" class="dd-btn dd-btn-default">Back</a>
                </div>
            </div>
            <div class="dd-card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Order Date:</strong><br>{{ $order->order_date }}</div>
                    <div class="col-md-3"><strong>Required Date:</strong><br>{{ $order->required_date ?: '-' }}</div>
                    <div class="col-md-3"><strong>Status:</strong><br>{{ ucwords(str_replace('_', ' ', $order->status)) }}</div>
                    <div class="col-md-3"><strong>Total:</strong><br>{{ number_format((float)$order->total_amount, 2) }}</div>
                </div>
                <hr>
                <div class="form-group">
                    <label>Cancellation Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="5" required placeholder="Please explain why you need to cancel this order.">{{ old('reason') }}</textarea>
                </div>
                <button type="submit" class="dd-btn dd-btn-danger">Submit Cancellation Request</button>
            </div>
        </div>
    </form>
</div>
@endsection
