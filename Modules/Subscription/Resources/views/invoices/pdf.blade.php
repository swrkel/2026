<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        .invoice-wrapper {
            background: #fff;
            padding: 12px;
            width: 100%;
            margin: auto;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        .invoice-banner {
            padding: 12px;
            text-align: center;
            color: red;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .invoice-banner img {
            max-height: 90px;
            max-width: 100%;
        }

        .divider {
            border-top: 2px solid #1aa3a3;
            margin-bottom: 10px;
        }

        .invoice-title {
            text-align: center;
            font-weight: bold;
            margin: 8px 0 12px;
        }

        .row {
            width: 100%;
            display: table;
        }

        .col-6 {
            width: 50%;
            display: table-cell;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        .invoice-table th {
            background: #0b1d59;
            color: #fff;
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 10px;
        }

        .invoice-table td {
            border: 1px solid #000;
            padding: 4px;
            word-wrap: break-word;
            vertical-align: top;
        }

        .col-sl {
            width: 5%;
        }

        .col-desc {
            width: 39%;
        }

        .col-cycle {
            width: 14%;
        }

        .col-qty {
            width: 10%;
        }

        .col-price {
            width: 14%;
        }

        .col-total {
            width: 18%;
        }

        .total-box {
            width: 42%;
            float: right;
            margin-top: 10px;
        }

        .total-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-box th {
            background: #0b1d59;
            color: #fff;
            border: 1px solid #000;
            padding: 6px;
            text-align: right;
        }

        .thank-you {
            font-family: Brush Script MT, Brush Script Std, cursive;
            font-size: 28px;
            color: #0b1d59;
            text-align: right;
            margin-top: 20px;
        }

        .small-text {
            font-size: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="invoice-wrapper">
        <div class="invoice-banner">
            @if (!empty($invoice->banner_image))
                <img src="{{ $invoice->banner_image }}" height="80">
            @else
                Upload Banner to show here
            @endif
        </div>

        <div class="divider"></div>
        <div class="invoice-title">INVOICE</div>

        <div class="row">
            <div class="col-6">
                <p><strong>Customer Name:</strong> {{ $invoice->customer_name }}</p>
                <p><strong>Customer Code:</strong> {{ $invoice->customer_code }}</p>
                <p><strong>Address:</strong> {{ $invoice->customer_address }}</p>
                <p><strong>Subscription Period:</strong> {{ $invoice->from_date }} to {{ $invoice->to_date }}</p>
            </div>

            <div class="col-6 text-right">
                <p><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</p>
                <p><strong>Date:</strong> {{ date('Y-m-d') }}</p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="col-sl">#</th>
                    <th class="col-desc">Description</th>
                    <th class="col-cycle">Cycle</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-price">Price</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td>{{ $item['cycle'] ?? '-' }}</td>
                        <td>{{ number_format($item['qty'], 2) }}</td>
                        <td>{{ number_format($item['price'], 2) }}</td>
                        <td>{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-box">
            <table>
                <tr>
                    <th>Total</th>
                    <th width="40%">{{ number_format($invoice->total, 2) }}</th>
                </tr>
            </table>
        </div>

        <div style="clear:both;"></div>

        <div class="row" style="margin-top:15px;">
            <div class="col-6">
                <p><strong>Payment Details:</strong> {{ $invoice->payment_details }}</p>
                <p><strong>Payment Method:</strong> {{ $invoice->payment_method }}</p>
                <p><strong>Payment Terms:</strong> {{ $invoice->payment_term }}</p>
            </div>

            <div class="col-6">
                <p><strong>Bank:</strong> {{ $invoice->bank['name'] }}</p>
                <p>AC Name: {{ $invoice->bank['ac_name'] }}</p>
                <p>AC No: {{ $invoice->bank['ac_no'] }}</p>
                <p>Branch: {{ $invoice->bank['branch'] }}</p>
            </div>
        </div>

        <div class="thank-you">Thank You!</div>

        <div class="small-text">
            Computer Generated Invoice. Signature not required.<br>
            * All sales are final. The amount paid for the software/service will not be refunded.
        </div>
    </div>
</body>

</html>
