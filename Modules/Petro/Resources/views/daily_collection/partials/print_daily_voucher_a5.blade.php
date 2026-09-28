{{-- A5 Print Template for Daily Credit Sale Voucher --}}
{{-- Professional layout for production use --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('petro::lang.daily_voucher') - A5</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #222;
            background: #fff;
        }
        
        .voucher-container {
            width: 100%;
            max-width: 128mm;
            margin: 0 auto;
        }
        
        /* Header Section */
        .header {
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 2px solid #333;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            color: #111;
        }
        
        .document-title {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #444;
            margin-top: 4px;
        }
        
        /* Info Section - Two Column Layout */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 30%;
            padding: 4px 8px 4px 0;
            font-weight: 600;
            color: #333;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            width: 70%;
            padding: 4px 0;
            color: #111;
            vertical-align: top;
        }
        
        .info-divider {
            border-bottom: 1px solid #ddd;
            margin: 8px 0;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            border: 1px solid #333;
            padding: 6px 8px;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            color: #333;
            text-align: left;
        }
        
        .items-table td {
            border: 1px solid #999;
            padding: 6px 8px;
            font-size: 11px;
            vertical-align: middle;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        /* Total Section */
        .total-section {
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 5px;
        }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 4px 0;
        }
        
        .total-label {
            font-weight: 600;
            font-size: 12px;
            color: #333;
            margin-right: 15px;
        }
        
        .total-value {
            font-weight: 700;
            font-size: 14px;
            color: #111;
            min-width: 80px;
            text-align: right;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #666;
            margin-top: 30px;
            padding-top: 5px;
            font-size: 10px;
            color: #555;
        }
        
        .thank-you {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 15px;
            font-style: italic;
        }
        
        /* Print specific */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .voucher-container {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="voucher-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ request()->session()->get('business.name') }}</div>
            <div class="document-title">@lang('petro::lang.daily_voucher')</div>
        </div>
        
        <!-- Voucher Info Section -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">@lang('petro::lang.date'):</span>
                <span class="info-value">{{ $daily_voucher->transaction_date }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">@lang('petro::lang.bill_no'):</span>
                <span class="info-value">{{ $daily_voucher->daily_vouchers_no }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">@lang('petro::lang.order_no'):</span>
                <span class="info-value">{{ $daily_voucher->voucher_order_number ?? '-' }}</span>
            </div>
            
            <div class="info-divider"></div>
            
            <div class="info-row">
                <span class="info-label">@lang('petro::lang.customer_name'):</span>
                <span class="info-value">{{ $daily_voucher->customer_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">@lang('petro::lang.vehicle_no'):</span>
                <span class="info-value">{{ $daily_voucher->reference ?? '-' }}</span>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">@lang('petro::lang.product')</th>
                    <th class="text-right" style="width: 20%;">@lang('petro::lang.unit_price')</th>
                    <th class="text-center" style="width: 15%;">@lang('petro::lang.qty')</th>
                    <th class="text-right" style="width: 25%;">@lang('petro::lang.sub_total')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($daily_voucher_items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-center">{{ $item->qty }}</td>
                        <td class="text-right">{{ number_format($item->sub_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <span class="total-label">@lang('petro::lang.total_amount'):</span>
                <span class="total-value">{{ number_format($daily_voucher->total_amount, 2) }}</span>
            </div>
        </div>
        
        <!-- Footer with Signatures -->
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line">Customer Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">Authorized Signature</div>
                </div>
            </div>
            
            <div class="thank-you">Thank You for Your Business</div>
        </div>
    </div>
</body>
</html>
