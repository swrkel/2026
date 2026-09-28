@extends('customers::portal.layout')

@section('title', 'Delivery Details')

@section('content')
@include('customers::portal.partials_nav')

<style>
.dd-page{padding:22px;background:#f6f8fb;min-height:100vh;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}
.dd-panel{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:18px;box-shadow:0 8px 24px rgba(15,23,42,.06);margin-bottom:16px;}
.dd-title{font-size:24px;font-weight:900;color:#0f172a;margin:0 0 14px;}
.dd-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
.dd-info{border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#f8fafc;}
.dd-label{font-size:12px;font-weight:800;text-transform:uppercase;color:#64748b;margin-bottom:4px;}
.dd-value{font-size:16px;font-weight:800;color:#0f172a;}
.dd-table-wrap{overflow-x:auto;border:1px solid #e5e7eb;border-radius:12px;}
.dd-table{width:100%;min-width:720px;border-collapse:collapse;background:#fff;}
.dd-table th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;text-align:left;padding:11px;border-bottom:1px solid #e5e7eb;}
.dd-table td{padding:11px;border-bottom:1px solid #f1f5f9;}
.dd-btn{height:38px;border:0;border-radius:10px;padding:0 14px;font-weight:800;background:#2563eb;color:#fff;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;}
@media(max-width:800px){.dd-grid{grid-template-columns:1fr;}.dd-page{padding:12px;}}
</style>

<div class="dd-page">
    <div class="dd-panel">
        <h1 class="dd-title">Delivery Details</h1>
        <a class="dd-btn" href="{{ route('customers.portal.deliveries') }}">Back</a>
        <button class="dd-btn" onclick="window.print();return false;" style="background:#16a34a;">Print</button>
    </div>

    <div class="dd-panel">
        <div class="dd-grid">
            <div class="dd-info"><div class="dd-label">Delivery No</div><div class="dd-value">{{ $delivery->delivery_no }}</div></div>
            <div class="dd-info"><div class="dd-label">Order No</div><div class="dd-value">{{ $delivery->order_no }}</div></div>
            <div class="dd-info"><div class="dd-label">Status</div><div class="dd-value">{{ ucwords(str_replace('_',' ', $delivery->status)) }}</div></div>
            <div class="dd-info"><div class="dd-label">Vehicle</div><div class="dd-value">{{ $delivery->vehicle }}</div></div>
            <div class="dd-info"><div class="dd-label">Driver</div><div class="dd-value">{{ $delivery->driver }}</div></div>
            <div class="dd-info"><div class="dd-label">Dispatch Date</div><div class="dd-value">{{ !empty($delivery->dispatch_date) ? date('Y-m-d', strtotime($delivery->dispatch_date)) : '-' }}</div></div>
            <div class="dd-info"><div class="dd-label">Expected Date</div><div class="dd-value">{{ !empty($delivery->expected_date) ? date('Y-m-d', strtotime($delivery->expected_date)) : '-' }}</div></div>
            <div class="dd-info"><div class="dd-label">Delivered Date</div><div class="dd-value">{{ !empty($delivery->delivered_date) ? date('Y-m-d', strtotime($delivery->delivered_date)) : '-' }}</div></div>
            <div class="dd-info"><div class="dd-label">Amount</div><div class="dd-value">{{ number_format((float)($delivery->amount ?? 0), 2) }}</div></div>
        </div>
    </div>

    <div class="dd-panel">
        <h3 style="font-weight:900;margin-top:0;">Products / Items</h3>
        <div class="dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->product_name ?? '-' }}</td>
                            <td style="text-align:right;">{{ number_format((float)($item->quantity ?? 0), 3) }}</td>
                            <td style="text-align:right;">{{ number_format((float)($item->unit_price ?? 0), 2) }}</td>
                            <td style="text-align:right;">{{ number_format((float)($item->line_total ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px;">No item details found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
