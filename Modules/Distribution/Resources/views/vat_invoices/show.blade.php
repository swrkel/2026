@extends('distribution::layouts.app')

@section('title', 'View VAT Dis. Invoice')

@section('content')
    <style>
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .business-header {
            flex: 1;
            text-align: center;
        }

        .business-header h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .invoice-header-right {
            text-align: right;
        }

        .invoice-title {
            color: #0b77d1;
            font-weight: 700;
            font-size: 28px;
            display: block;
            margin-bottom: 8px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .customer-block {
            flex: 1;
            min-width: 300px;
        }

        .invoice-block {
            flex: 1;
            min-width: 300px;
            text-align: right;
        }

        .invoice-block .form-field-row {
            justify-content: flex-end;
        }

        .form-field-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 0;
        }

        .form-field-row label {
            min-width: 140px;
            margin: 0;
            margin-right: 0;
            font-weight: 700;
        }

        .invoice-block .form-field-row {
            justify-content: flex-end;
        }

        .invoice-block .form-field-row label {
            min-width: auto;
            margin-right: 4px;
            white-space: nowrap;
            font-weight: 700;
        }

        .form-field-row .value {
            flex: 0 0 250px;
            max-width: 250px;
        }

        .invoice-block .form-field-row .value {
            flex: 0 0 auto;
            max-width: none;
            min-width: auto;
            margin-left: 0;
        }

        .invoice-table {
            border-collapse: collapse;
            width: 100%;
            border-top: 2px solid #d22;
        }

        .invoice-table th,
        .invoice-table td {
            border: 2px solid #d22;
            padding: 8px;
            vertical-align: middle;
        }

        .invoice-table thead th {
            background: #fff;
            font-weight: 700;
            color: #900;
            border-top: 2px solid #d22;
            text-align: center;
        }

        .invoice-table tbody td {
            height: 34px;
        }

        .invoice-table tfoot td {
            height: 34px;
            font-weight: 700;
            color: #900;
        }

        .invoice-table tfoot td[colspan] {
            border: none !important;
            padding: 0;
        }

        .invoice-table tfoot td:last-child {
            border: none !important;
            padding: 0;
            width: 0;
        }

        .invoice-table tfoot td.invoice-amount {
            border: 2px solid #d22 !important;
        }

        .invoice-index {
            width: 70px;
            text-align: center;
        }

        .invoice-qty {
            width: 100px;
            text-align: center;
        }

        .invoice-product {
            width: 35%;
        }

        .invoice-unitprice,
        .invoice-amount,
        .invoice-disc {
            width: 140px;
            text-align: right;
        }

        .payment-details {
            margin-top: 40px;
        }

        .btn-back {
            margin-top: 20px;
        }
    </style>

    <section class="content">
        <div class="top-header">
            <div class="business-header">
                <h2>{{ $business->name ?? 'Business Name' }}</h2>
                <div><strong>Address:</strong>
                    @if($location)
                        {{ $location->landmark ?? $location->address_1 ?? '' }}
                        @if($location->city || $location->state || $location->country)
                            {{ ', ' . implode(', ', array_filter([$location->city, $location->state, $location->country])) }}
                        @endif
                    @else
                        Address
                    @endif
                </div>
                <div><strong>Contact Number:</strong> {{ $location->mobile ?? $location->alternate_number ?? '' }}</div>
            </div>
            <div class="invoice-header-right">
                <div class="invoice-title">Tax Invoice</div>
                <div><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</div>
            </div>
        </div>

        <div class="invoice-header">
            <div class="customer-block">
                <div class="form-field-row">
                    <label><strong>Customer:</strong></label>
                    <div class="value">{{ $invoice->customer->name ?? $invoice->customer_name ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Customer Address:</strong></label>
                    <div class="value">{{ $invoice->customer->address ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Customer Contact No:</strong></label>
                    <div class="value">{{ $invoice->customer->phone ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Customer VAT No:</strong></label>
                    <div class="value">{{ $invoice->customer_vat_no ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="invoice-block">
                <div class="form-field-row">
                    <label><strong>Date:</strong></label>
                    <div class="value">
                        @if($invoice->date)
                            {{ \Carbon\Carbon::parse($invoice->date)->format('Y-m-d g:i A') }}
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="form-field-row">
                    <label><strong>Place of Supply:</strong></label>
                    <div class="value">{{ $invoice->place_of_supply ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Additional Info:</strong></label>
                    <div class="value">{{ $invoice->additional_info ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Sales Rep:</strong></label>
                    <div class="value">{{ optional($invoice->salesRep)->name ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Route:</strong></label>
                    <div class="value">{{ optional($invoice->route)->name ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Vehicle No:</strong></label>
                    <div class="value">{{ optional($invoice->vehicle)->vehicle_no ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Product Category:</strong></label>
                    <div class="value">{{ optional($invoice->category)->name ?? 'N/A' }}</div>
                </div>
                <div class="form-field-row">
                    <label><strong>Shipping Status:</strong></label>
                    <div class="value">{{ ucfirst($invoice->shipping_status ?? 'ordered') }}</div>
                </div>
            </div>
        </div>

        <div class="row" style="margin-bottom: 12px;">
            <div class="col-md-6"><strong>Invoice Note:</strong> {{ $invoice->invoice_note ?: '-' }}</div>
            <div class="col-md-6"><strong>Shipping Note:</strong> {{ $invoice->shipping_note ?: '-' }}</div>
            <div class="col-md-12" style="margin-top: 8px;"><strong>Shipping Details:</strong> {{ $invoice->shipping_details ?: '-' }}</div>
        </div>

        {{-- Invoice table (red) --}}
        <div style="overflow:auto;">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th class="invoice-index">Index No</th>
                        <th class="invoice-qty">Quantity</th>
                        <th class="invoice-product">Product</th>
                        <th class="invoice-unitprice">Unit Price</th>
                        <th class="invoice-amount">Amount</th>
                        <th class="invoice-disc">Discount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->lines as $index => $line)
                        <tr>
                            <td style="text-align:center;">{{ $index + 1 }}</td>
                            <td style="text-align:center;">{{ number_format($line->qty, 2) }}</td>
                            <td>
                                {{ $line->product->name ?? '-' }}
                                @if($line->is_free) <span class="label label-info">Free</span> @endif
                                @if($line->is_free_bottles) <span class="label label-warning">Free Bottle</span> @endif
                            </td>
                            <td style="text-align:right;">{{ number_format($line->unit_price, 2) }}</td>
                            <td style="text-align:right;">{{ number_format($line->amount, 2) }}</td>
                            <td style="text-align:right;">{{ number_format($line->discount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No items found</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"></td>
                        <td class="invoice-amount" style="text-align:right; font-weight:700;">TOTAL {{ number_format($invoice->total, 2) }}</td>
                        <td style="border: none !important; padding: 0; width: 0;"></td>
                    </tr>
                    <tr>
                        <td colspan="4"></td>
                        <td class="invoice-amount" style="text-align:right; font-weight:700;">DISCOUNT {{ number_format($invoice->discount, 2) }}</td>
                        <td style="border: none !important; padding: 0; width: 0;"></td>
                    </tr>
                    <tr>
                        <td colspan="4"></td>
                        <td class="invoice-amount" style="text-align:right; font-weight:700;">GRAND TOTAL {{ number_format($invoice->grand_total, 2) }}</td>
                        <td style="border: none !important; padding: 0; width: 0;"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="payment-details">
            <h4>Payment Details</h4>
            <table class="table table-bordered table-condensed" style="max-width:400px;">
                <tr>
                    <th>Cash</th>
                    <td class="text-right">{{ number_format($invoice->payment_cash ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Card</th>
                    <td class="text-right">{{ number_format($invoice->payment_card ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Credit</th>
                    <td class="text-right">{{ number_format($invoice->payment_credit ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Cheque</th>
                    <td class="text-right">{{ number_format($invoice->payment_cheque ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th style="color:#d22; font-weight:700;">Total</th>
                    <td class="text-right" style="color:#d22; font-weight:700; border:2px solid #d22;">
                        {{ number_format($invoice->payment_total ?? 0, 2) }}
                    </td>
                </tr>
            </table>

            @if($invoice->cheques && $invoice->cheques->count())
                <h4>Cheque Details</h4>
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>Bank</th>
                            <th>Branch</th>
                            <th>Cheque No</th>
                            <th>Cheque Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->cheques as $cheque)
                            <tr>
                                <td>{{ $cheque->bank }}</td>
                                <td>{{ $cheque->branch }}</td>
                                <td>{{ $cheque->cheque_no }}</td>
                                <td>{{ $cheque->cheque_date }}</td>
                                <td class="text-right">{{ number_format($cheque->amount ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="text-right btn-back">
            <a href="{{ route('distribution.vat-invoices.index') }}" class="btn btn-default">Back</a>
        </div>
    </section>
@endsection
