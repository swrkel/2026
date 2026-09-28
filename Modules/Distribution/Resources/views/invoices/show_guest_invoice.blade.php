@extends('distribution::layouts.guest')

@section('title', $title)

@section('css')
<style>
    /* Styles adapted from distribution print layout for screen viewing */
    .invoice-card {
        background: #fff;
        border: 1px solid #ccc;
        padding: 30px;
        margin-top: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .top-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        border-bottom: 2px solid #d22;
        padding-bottom: 15px;
    }
    
    .business-header {
        flex: 1;
        text-align: center;
    }
    
    .business-header h2 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #d22;
    }
    
    .invoice-header-right {
        text-align: right;
    }
    
    .invoice-title {
        color: #0b77d1;
        font-weight: 700;
        font-size: 24px;
        display: block;
        margin-bottom: 8px;
    }
    
    /* Info sections */
    .info-section {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .info-block {
        flex: 1;
        min-width: 250px;
    }
    
    .info-block.right {
        text-align: right;
    }
    
    .info-row {
        display: flex;
        margin-bottom: 8px;
        align-items: baseline;
    }
    
    .info-row label {
        min-width: 140px;
        font-weight: 700;
        margin-right: 8px;
    }
    
    .info-row .value {
        flex: 1;
    }
    
    .info-block.right .info-row {
        justify-content: flex-end;
    }
    
    .info-block.right label {
        min-width: auto;
        margin-right: 8px;
    }
    
    /* Table styles */
    .invoice-table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 20px;
    }
    
    .invoice-table th,
    .invoice-table td {
        border: 1px solid #d22;
        padding: 8px;
        vertical-align: middle;
    }
    
    .invoice-table thead th {
        background: #f9f9f9;
        font-weight: 700;
        color: #900;
        text-align: center;
    }
    
    .invoice-table tfoot td {
        font-weight: 700;
        background: #f9f9f9;
    }
    
    /* Payment details */
    .payment-details {
        margin-top: 30px;
    }
    
    .payment-table {
        max-width: 400px;
        border-collapse: collapse;
    }
    
    .payment-table th,
    .payment-table td {
        border: 1px solid #ddd;
        padding: 6px 12px;
    }
    
    .payment-table th {
        background: #f5f5f5;
        font-weight: 700;
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-right {
        text-align: right;
    }
    
    .text-left {
        text-align: left;
    }

    @media print {
        .no-print {
            display: none !important;
        }
        .invoice-card {
            border: none;
            box-shadow: none;
            padding: 0;
            margin: 0;
        }
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="spacer"></div>
    <div class="row no-print">
        <div class="col-md-12">
            <button type="button" class="btn btn-primary pull-right"
                 aria-label="Print" onclick="window.print();"><i class="fa fa-print"></i> @lang( 'messages.print' )
            </button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-12">
            <div class="invoice-card">
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
                        <div class="invoice-title">INVOICE</div>
                        <div><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</div>
                        @if($invoice->loading_sheet_no)
                            <div><strong>Loading Sheet:</strong> {{ $invoice->loading_sheet_no }}</div>
                        @endif
                    </div>
                </div>

                <div class="info-section">
                    <div class="info-block">
                        <div class="info-row">
                            <label>Customer:</label>
                            <div class="value">{{ $invoice->customer_name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <label>Address:</label>
                            <div class="value">{{ $invoice->customer_address ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <label>Contact No:</label>
                            <div class="value">{{ $invoice->customer_contact ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="info-block right">
                        <div class="info-row">
                            <label>Date:</label>
                            <div class="value">
                                @if($invoice->date)
                                    @php
                                        try {
                                            $dateStr = (string)$invoice->date;
                                            if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}):(\d{2})(?::(\d{2}))?$/', $dateStr, $matches)) {
                                                if (!isset($matches[4]) || $matches[4] === '') {
                                                    $dateStr = $matches[1] . ' ' . $matches[2] . ':' . $matches[3] . ':00';
                                                }
                                                $dateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateStr, 'UTC');
                                                echo $dateTime->format('Y-m-d g:i A');
                                            } else {
                                                $dateTime = \Carbon\Carbon::parse($invoice->date)->setTimezone('UTC');
                                                echo $dateTime->format('Y-m-d g:i A');
                                            }
                                        } catch (\Exception $e) {
                                            echo $invoice->date;
                                        }
                                    @endphp
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <label>Sales Rep:</label>
                            <div class="value">{{ $invoice->salesRep->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <label>Route:</label>
                            <div class="value">{{ $invoice->route->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <label>Vehicle No:</label>
                            <div class="value">{{ $invoice->vehicle->vehicle_no ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <label>Category:</label>
                            <div class="value">{{ $invoice->category->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <label>Shipping Status:</label>
                            <div class="value">{{ ucfirst($invoice->shipping_status ?? 'ordered') }}</div>
                        </div>
                        <div class="info-row">
                            <label>Shipping Details:</label>
                            <div class="value">{{ $invoice->shipping_details ?: '-' }}</div>
                        </div>
                    </div>
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 10%;">Qty</th>
                            <th style="width: 35%;">Product</th>
                            <th style="width: 15%;">Unit Price</th>
                            <th style="width: 15%;">Amount</th>
                            <th style="width: 20%;">Discount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNumber = 1; @endphp
                        @forelse($invoice->lines as $line)
                            <tr>
                                <td class="text-center">{{ $rowNumber++ }}</td>
                                <td class="text-center">{{ number_format($line->qty, 2) }}</td>
                                <td class="text-left">
                                    {{ $line->product->name ?? '-' }}
                                    @if($line->is_free || $line->is_free_bottles || $line->is_free_auto)
                                        <span style="color: red; font-weight: bold;"> 
                                            ({{ $line->is_free ? 'Free' : ($line->is_free_bottles ? 'Free Bottles' : 'Free Issue') }})
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                                <td class="text-right">{{ number_format($line->amount, 2) }}</td>
                                <td class="text-right">{{ number_format($line->discount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                            <td class="text-right"><strong>{{ number_format($invoice->total, 2) }}</strong></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right"><strong>DISCOUNT:</strong></td>
                            <td class="text-right"><strong>{{ number_format($invoice->discount, 2) }}</strong></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right"><strong>GRAND TOTAL:</strong></td>
                            <td class="text-right"><strong>{{ number_format($invoice->grand_total, 2) }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="payment-details">
                    <h4>Payment Details</h4>
                    <table class="payment-table">
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
                            <th style="color:#d22;">Total Paid</th>
                            <td class="text-right" style="color:#d22; font-weight:700;">{{ number_format($invoice->payment_total ?? 0, 2) }}</td>
                        </tr>
                    </table>

                    @if($invoice->cheques && $invoice->cheques->count())
                        <h4 style="margin-top: 20px;">Cheque Details</h4>
                        <table class="table table-bordered table-condensed" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 6px;">Bank</th>
                                    <th style="border: 1px solid #ddd; padding: 6px;">Branch</th>
                                    <th style="border: 1px solid #ddd; padding: 6px;">Cheque No</th>
                                    <th style="border: 1px solid #ddd; padding: 6px;">Cheque Date</th>
                                    <th style="border: 1px solid #ddd; padding: 6px;" class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->cheques as $cheque)
                                    <tr>
                                        <td style="border: 1px solid #ddd; padding: 6px;">{{ $cheque->bank }}</td>
                                        <td style="border: 1px solid #ddd; padding: 6px;">{{ $cheque->branch }}</td>
                                        <td style="border: 1px solid #ddd; padding: 6px;">{{ $cheque->cheque_no }}</td>
                                        <td style="border: 1px solid #ddd; padding: 6px;">{{ $cheque->cheque_date }}</td>
                                        <td style="border: 1px solid #ddd; padding: 6px;" class="text-right">{{ number_format($cheque->amount ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="report-footer text-center" style="margin-top: 30px; font-size: 10px; color: #666;">
                    <p>Thank you for your business!</p>
                    @if($business->name)
                        <p>{{ $business->name }} - Authorized Signature</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="spacer"></div>
</div>
@endsection
