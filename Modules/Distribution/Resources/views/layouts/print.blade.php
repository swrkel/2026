<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .print-container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        /* Header styles */
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
        
        /* Text alignment */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-left {
            text-align: left;
        }
        
        /* ============================================ */
        /* FIXED: Report Footer on EVERY page - CENTERED */
        /* ============================================ */
        
        @page {
            size: A4;
            margin: 2cm 1.5cm 2.5cm 1.5cm;
            
            /* Combined Footer + Page Number - Centered on EVERY page */
            @bottom-center {
                content: "© All Rights Reserved | Version 9.9 | SYZYGY Technologies, Malabe, Sri Lanka. | Tel: 077 4055 434 / 071 1616 192 \A Page " counter(page) " of " counter(pages);
                font-size: 8px;
                font-family: Arial, sans-serif;
                color: #666;
                white-space: pre;
                text-align: center;
                line-height: 1.5;
            }
        }
        
        /* Print-specific styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            /* Hide the old footer div when printing */
            .report-footer {
                display: none;
            }
        }
    </style>
    
    @stack('styles')
</head>

<body>
    <div class="print-container">
        @yield('content')
    </div>
    
    <!-- Hidden div - kept for reference but not used in print -->
    <div class="report-footer" style="display: none;">
        @php
            $footer_text = '';
            try {
                $siteSetting = DB::table('site_settings')->select('login_page_footer')->first();
                if ($siteSetting && !empty($siteSetting->login_page_footer)) {
                    $footer_text = $siteSetting->login_page_footer;
                }
            } catch (\Exception $e) {
                $footer_text = '';
            }
        @endphp
        {!! nl2br(e($footer_text)) !!}
    </div>
    
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
    
    @stack('scripts')
</body>
</html>