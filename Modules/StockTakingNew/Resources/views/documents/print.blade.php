<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $session->stock_take_no }}</title>
    <style>
        @page{margin:14mm}body{font-family:DejaVu Sans,Arial,sans-serif;color:#172033;font-size:11px}
        .header{display:flex;justify-content:space-between;border-bottom:3px solid #2563eb;padding-bottom:12px;margin-bottom:14px}
        .header h1{font-size:22px;margin:0}.meta{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px}
        .meta div{border:1px solid #dbe5f1;border-radius:7px;padding:8px}.meta span{display:block;color:#64748b;font-size:9px;text-transform:uppercase}.meta strong{font-size:12px}
        table{width:100%;border-collapse:collapse}th{background:#eff5fc;text-align:left;padding:7px;border:1px solid #dbe5f1}td{padding:6px;border:1px solid #dbe5f1}.num{text-align:right}
        .count-box{height:20px;min-width:70px}.footer{margin-top:14px;display:flex;justify-content:space-between}.signature{width:28%;border-top:1px solid #334155;padding-top:5px;margin-top:35px}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<button class="no-print" onclick="window.print()">Print</button>
<div class="header">
    <div>
        <h1>Stock Taking {{ ucwords(str_replace('_', ' ', $documentType)) }}</h1>
        <div>{{ $session->stock_take_no }} – {{ $session->title }}</div>
    </div>
    <div><strong>{{ strtoupper($session->status) }}</strong><br>{{ optional($session->count_date)->format('d M Y') }}</div>
</div>
<div class="meta">
    <div><span>Location</span><strong>{{ $locationName }}</strong></div>
    <div><span>Store</span><strong>{{ $storeName }}</strong></div>
    <div><span>Method / Mode</span><strong>{{ ucfirst($session->count_method) }} / {{ ucfirst($session->count_mode) }}</strong></div>
    <div><span>Products</span><strong>{{ $lines->count() }}</strong></div>
    @if(! $isCountSheet)
        <div><span>System Quantity</span><strong>{{ number_format((float) $session->system_qty_total, 4) }}</strong></div>
        <div><span>Counted Quantity</span><strong>{{ number_format((float) $session->counted_qty_total, 4) }}</strong></div>
        <div><span>Variance Quantity</span><strong>{{ number_format((float) $session->variance_qty_total, 4) }}</strong></div>
        <div><span>Variance Value</span><strong>{{ number_format((float) $session->variance_value_total, 4) }}</strong></div>
    @endif
</div>
<table>
    <thead>
        <tr>
            <th>#</th><th>SKU</th><th>Product</th><th>Bin</th>
            @if($showSystemQuantity)<th class="num">System</th>@endif
            <th class="num">{{ $isCountSheet ? 'Physical Count' : 'Counted' }}</th>
            @if($showFinancials)<th class="num">Variance</th><th class="num">Unit Cost</th><th class="num">Value</th>@endif
            @if($isCountSheet)<th>Notes</th>@endif
        </tr>
    </thead>
    <tbody>
        @forelse($lines as $index => $line)
            <tr>
                <td>{{ $index + 1 }}</td><td>{{ $line->sku }}</td><td>{{ $line->product_name }}</td><td>{{ $line->bin_location }}</td>
                @if($showSystemQuantity)<td class="num">{{ number_format((float) $line->system_qty, 4) }}</td>@endif
                @if($isCountSheet)
                    <td class="count-box"></td>
                @else
                    <td class="num">{{ $line->final_count_qty === null ? '' : number_format((float) $line->final_count_qty, 4) }}</td>
                @endif
                @if($showFinancials)
                    <td class="num">{{ number_format((float) $line->variance_qty, 4) }}</td>
                    <td class="num">{{ number_format((float) $line->unit_cost, 4) }}</td>
                    <td class="num">{{ number_format((float) $line->variance_value, 4) }}</td>
                @endif
                @if($isCountSheet)<td></td>@endif
            </tr>
        @empty
            <tr><td colspan="10">No product lines are available.</td></tr>
        @endforelse
    </tbody>
</table>
<div class="footer"><div class="signature">Counted By</div><div class="signature">Checked By</div><div class="signature">Approved By</div></div>
</body>
</html>
