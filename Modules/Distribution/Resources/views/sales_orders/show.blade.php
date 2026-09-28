@extends('distribution::layouts.app')

@section('title', 'Sales Order View')

@section('content')
    <section class="content">
        @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Sales Order #' . $sales_order->sales_order_no])
            <div class="row">
                <div class="col-md-4"><strong>Date:</strong> {{ \Carbon\Carbon::parse($sales_order->date)->format('Y-m-d H:i') }}</div>
                <div class="col-md-4"><strong>Delivery Date:</strong> {{ $sales_order->delivery_date ?: '-' }}</div>
                <div class="col-md-4"><strong>Status:</strong> {{ ucfirst($sales_order->status) }}</div>
            </div>
            <div class="row" style="margin-top:8px;">
                <div class="col-md-4"><strong>Customer:</strong> {{ $sales_order->customer_name }}</div>
                <div class="col-md-4"><strong>Shipping Status:</strong> {{ ucfirst($sales_order->shipping_status) }}</div>
                <div class="col-md-4"><strong>Total:</strong> {{ number_format($sales_order->grand_total, 2) }}</div>
            </div>
            <div class="row" style="margin-top:8px;">
                <div class="col-md-12"><strong>Note:</strong> {{ $sales_order->invoice_note ?: '-' }}</div>
                <div class="col-md-12"><strong>Shipping Note:</strong> {{ $sales_order->shipping_note ?: '-' }}</div>
                <div class="col-md-12"><strong>Shipping Details:</strong> {{ $sales_order->shipping_details ?: '-' }}</div>
            </div>

            <div class="table-responsive" style="margin-top: 12px;">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Discount</th>
                            <th>Final Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales_order->lines as $line)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ optional($line->product)->name }}</td>
                                <td>{{ number_format($line->qty, 2) }}</td>
                                <td>{{ number_format($line->unit_price, 2) }}</td>
                                <td>{{ number_format($line->discount, 2) }}</td>
                                <td>{{ number_format($line->final_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endcomponent
    </section>
@endsection
