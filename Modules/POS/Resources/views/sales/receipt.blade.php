@extends('pos::layouts.app')
@section('title', $title ?? 'POS Receipt')
@section('pos_content')
<div class="pos-hero no-print"><div><span class="eyebrow">Receipt</span><h2>{{ $receipt['sale']->sale_no }}</h2><p>Print-ready POS receipt generated from standalone POS tables.</p></div><div><button class="btn btn-primary" onclick="window.print()">Print</button> <a class="btn btn-default" href="{{ route('pos.sales.index') }}">New Sale</a></div></div>
<div class="receipt-card">
    <h2>POS Receipt</h2><p>{{ $receipt['sale']->sale_no }}<br>{{ $receipt['sale']->sale_date }}</p><hr>
    <p><strong>Customer:</strong> {{ $receipt['sale']->customer_name }}</p>
    <table class="table receipt-table"><thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead><tbody>
    @foreach($receipt['lines'] as $line)<tr><td>{{ $line->product_name }}</td><td class="text-right">{{ number_format($line->quantity,3) }}</td><td class="text-right">{{ number_format($line->unit_price,2) }}</td><td class="text-right">{{ number_format($line->line_total,2) }}</td></tr>@endforeach
    </tbody></table>
    <div class="receipt-totals"><div><span>Subtotal</span><strong>{{ number_format($receipt['sale']->subtotal,2) }}</strong></div><div><span>Discount</span><strong>{{ number_format($receipt['sale']->discount_amount,2) }}</strong></div><div><span>Tax</span><strong>{{ number_format($receipt['sale']->tax_amount,2) }}</strong></div><div class="grand"><span>Total</span><strong>{{ number_format($receipt['sale']->total_amount,2) }}</strong></div><div><span>Paid</span><strong>{{ number_format($receipt['sale']->paid_amount,2) }}</strong></div><div><span>Balance</span><strong>{{ number_format($receipt['sale']->balance_amount,2) }}</strong></div></div>
    <hr><p class="text-center">Thank you. Please come again.</p>
</div>
@endsection
