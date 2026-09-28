@extends('distribution::layouts.print')

@section('title', 'Sales Order #' . $sales_order->sales_order_no)

@section('content')
    <div class="top-header">
        <div class="business-header">
            <h2>{{ $business->name ?? 'Business Name' }}</h2>
            <div><strong>Address:</strong>
                @if($location)
                    {{ $location->landmark ?? $location->address_1 ?? '' }}
                @else
                    -
                @endif
            </div>
            <div><strong>Contact Number:</strong> {{ $location->mobile ?? $location->alternate_number ?? '' }}</div>
        </div>
        <div class="invoice-header-right">
            <div class="invoice-title">SALES ORDER</div>
            <div><strong>Sales Order No:</strong> {{ $sales_order->sales_order_no }}</div>
        </div>
    </div>

    <div class="info-section">
        <div class="info-block">
            <div class="info-row"><label>Customer:</label><div class="value">{{ $sales_order->customer_name ?: optional($sales_order->customer)->name }}</div></div>
            <div class="info-row"><label>Address:</label><div class="value">{{ $sales_order->print_customer_address ?? ($sales_order->customer_address ?: optional($sales_order->customer)->address_line_1 ?: optional($sales_order->customer)->address ?: '-') }}</div></div>
            <div class="info-row"><label>Contact:</label><div class="value">{{ $sales_order->print_customer_contact ?? ($sales_order->customer_contact ?: '-') }}</div></div>
        </div>
        <div class="info-block right">
            <div class="info-row"><label>Date:</label><div class="value">{{ \Carbon\Carbon::parse($sales_order->date)->format('Y-m-d H:i') }}</div></div>
            <div class="info-row"><label>Delivery Date:</label><div class="value">{{ $sales_order->delivery_date ?: '-' }}</div></div>
            <div class="info-row"><label>Shipping Status:</label><div class="value">{{ ucfirst($sales_order->shipping_status) }}</div></div>
            <div class="info-row"><label>Location / Route:</label><div class="value">{{ optional($sales_order->distributionRoute)->name ?: '-' }}</div></div>
            <div class="info-row"><label>Status:</label><div class="value">{{ ucfirst($sales_order->status) }}</div></div>
        </div>
    </div>

    <table class="invoice-table">
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
            @foreach($sales_order->lines as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ optional($line->product)->name }}
                        @if(!empty($line->is_free)) <span style="font-size:10px; border:1px solid #777; padding:1px 4px;">Free</span> @endif
                        @if(!empty($line->is_free_bottles)) <span style="font-size:10px; border:1px solid #777; padding:1px 4px;">Free Bottle</span> @endif
                    </td>
                    <td class="text-right">{{ number_format($line->qty, 2) }}</td>
                    <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($line->discount, 2) }}</td>
                    <td class="text-right">{{ number_format($line->final_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="text-right"><strong>TOTAL</strong></td><td class="text-right">{{ number_format($sales_order->total, 2) }}</td></tr>
            <tr><td colspan="5" class="text-right"><strong>DISCOUNT</strong></td><td class="text-right">{{ number_format($sales_order->discount, 2) }}</td></tr>
            <tr><td colspan="5" class="text-right"><strong>GRAND TOTAL</strong></td><td class="text-right">{{ number_format($sales_order->grand_total, 2) }}</td></tr>
        </tfoot>
    </table>

    <div style="margin-top:12px;">
        <p><strong>Note:</strong> {{ $sales_order->invoice_note ?: '-' }}</p>
        <p><strong>Shipping Note:</strong> {{ $sales_order->shipping_note ?: '-' }}</p>
        <p><strong>Shipping Details:</strong> {{ $sales_order->shipping_details ?: '-' }}</p>
    </div>
@endsection
