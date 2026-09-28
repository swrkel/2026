<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loan Ledger Print</title>
    <style>
        body{font-family: Arial, sans-serif; font-size:12px; color:#222;}
        h2,h4{margin:0 0 8px 0;}
        table{width:100%; border-collapse:collapse; margin-top:15px;}
        th,td{border:1px solid #999; padding:6px;}
        th{background:#f1f1f1;}
        .text-right{text-align:right;}
        .header{display:flex; justify-content:space-between; margin-bottom:12px;}
        @media print{.no-print{display:none;}}
    </style>
</head>
<body>
    <div class="no-print" style="text-align:right; margin-bottom:10px;"><button onclick="window.print()">Print</button></div>
    <div class="header">
        <div>
            <h2>{{ optional($business)->name ?? 'Business' }}</h2>
            <h4>Loan Ledger / Statement</h4>
        </div>
        <div>
            <strong>Printed:</strong> {{ date('Y-m-d H:i') }}<br>
            <strong>From:</strong> {{ $filters['date_from'] ?? '-' }}<br>
            <strong>To:</strong> {{ $filters['date_to'] ?? '-' }}
        </div>
    </div>
    @include('loan::loan_ledgers.partials_statement_table', ['rows' => $rows, 'summary' => $summary])
</body>
</html>
