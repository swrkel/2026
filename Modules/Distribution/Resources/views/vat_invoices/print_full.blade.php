@php
    $formatDate = function ($value) {
        if (empty($value) || $value === '-') {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return '-';
        }
    };
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Full VAT Invoice</title>
    <style>
        @page { size: A4 portrait; margin: 10px; }
        body { margin: 10px; padding: 20px; font-family: sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .invoice-title { font-size: 24px; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="invoice-title">FULL VAT INVOICE</div>
        <div>{{ $business->name }}</div>
    </div>

    <div style="display: flex; justify-content: space-between;">
        <div>
            <strong>To:</strong><br>
            {{ $invoice->customer->name ?? $invoice->customer_name }}<br>
            {{ $invoice->customer_address }}<br>
            VAT No: {{ $invoice->customer_vat_no }}
        </div>
        <div style="text-align: right;">
            <strong>Invoice #:</strong> {{ $invoice->invoice_no }}<br>
            <strong>Date:</strong> {{ $formatDate($invoice->date) }}<br>
            <strong>Due Date:</strong> {{ $formatDate($invoice->delivery_date) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Discount</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td>{{ $line->product->name ?? '-' }}</td>
                    <td style="text-align: center;">{{ number_format($line->qty, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($line->unit_price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($line->discount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($line->final_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                <td style="text-align: right;">{{ number_format($invoice->total, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right;"><strong>Discount</strong></td>
                <td style="text-align: right;">{{ number_format($invoice->discount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;"><strong>Grand Total</strong></td>
                <td style="text-align: right;">{{ number_format($invoice->grand_total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($invoice->invoice_note)
        <div>
            <strong>Note:</strong><br>
            {{ $invoice->invoice_note }}
        </div>
    @endif
</body>
</html>
