@extends('customers::portal.layout')
@section('title', 'Request Order Amendment')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    @if(session('status'))
        <div class="dd-card"><div class="dd-card-body"><strong>{{ session('status.msg') }}</strong></div></div>
    @endif

    <form method="POST" action="{{ route('customers.portal.orders.amend.store', $order->id) }}">
        @csrf
        <div class="dd-card">
            <div class="dd-card-header clearfix">
                <h3 class="dd-card-title pull-left">Request Amendment - {{ $order->order_no }}</h3>
                <div class="pull-right dd-no-print">
                    <a href="{{ route('customers.portal.orders.show', $order->id) }}" class="dd-btn dd-btn-default">Back</a>
                </div>
            </div>
            <div class="dd-card-body">
                <div class="row">
                    <div class="col-md-4"><strong>Order Date:</strong><br>{{ $order->order_date }}</div>
                    <div class="col-md-4"><strong>Status:</strong><br>{{ ucwords(str_replace('_', ' ', $order->status)) }}</div>
                    <div class="col-md-4"><strong>Total Amount:</strong><br>{{ number_format((float)$order->total_amount, 2) }}</div>
                </div>
                <hr>
                <div class="form-group">
                    <label>Requested Changes <span class="text-danger">*</span></label>
                    <textarea name="requested_changes" class="form-control" rows="6" required placeholder="Example: Please change Product A quantity from 100 to 150, remove Product B, or update required date.">{{ old('requested_changes') }}</textarea>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Optional reason for this amendment request.">{{ old('reason') }}</textarea>
                </div>
                <button type="submit" class="dd-btn dd-btn-primary">Submit Amendment Request</button>
            </div>
        </div>
    </form>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Current Order Items</h3></div>
        <div class="dd-card-body">
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead><tr><th>Product</th><th class="text-right">Quantity</th><th class="text-right">Unit Price</th><th class="text-right">Line Total</th></tr></thead>
                    <tbody>
                    @foreach($lines as $line)
                        <tr><td>{{ $line->product_name }}</td><td class="text-right">{{ number_format((float)$line->quantity, 4) }}</td><td class="text-right">{{ number_format((float)$line->unit_price, 2) }}</td><td class="text-right">{{ number_format((float)$line->line_total, 2) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
