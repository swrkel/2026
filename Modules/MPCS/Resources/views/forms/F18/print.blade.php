<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
        @page {
            margin: 0;
            size: auto;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 12px 16px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table th, table td { border: 1px solid #999; padding: 3px 5px; }
        table thead th { background: #f5f5f5; text-align: center; vertical-align: middle; }
        table tfoot th { background: #f9f9f9; }
        .sigs { display: flex; gap: 10px; margin-top: 30px; }
        .sig-block { flex: 1; text-align: center; }
        .sig-line { border-bottom: 1px dashed #777; height: 22px; margin-bottom: 3px; }
        .footer { margin-top: 20px; font-size: 13px; color: #333; padding-top: 8px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
@php
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
    $qp  = $qty_precision ?? 2;
    $fmt = fn($v) => number_format($v, 2);
@endphp

{{-- Header --}}
<div class="text-center" style="margin-bottom:8px;">
    <h4 style="font-weight:700; margin:0 0 2px;">{{ $business->name }}</h4>
    <div>Goods Exchange Note</div>
    <div style="font-size:14px; color:#555;">{{ $header->form_date }}</div>
</div>

<div style="display:flex; justify-content:space-between; margin-bottom:6px;">
    <div><strong>Location:</strong> {{ $from_location->name ?? '—' }}</div>
    <div style="text-align:right;">
        <strong>F 18 No:</strong> {{ $header->form_no }}<br>
        <strong>Date:</strong> {{ $header->form_date }}
    </div>
</div>

<div style="margin-bottom:8px;">
    <strong>Transferred to:</strong> {{ $to_location_text ?? '—' }}
</div>

{{-- Table --}}
<table>
    <thead>
        <tr>
            <th rowspan="3" style="vertical-align:middle;">#</th>
            <th rowspan="3" style="vertical-align:middle;">Description</th>
            <th rowspan="3" style="vertical-align:middle;">Qty</th>
            <th colspan="4">Issued Location</th>
            <th colspan="4">Received Location</th>
            <th rowspan="3" style="vertical-align:middle;">Office Use</th>
        </tr>
        <tr>
            <th colspan="2">Purchase Price</th>
            <th colspan="2">Sale Price</th>
            <th colspan="2">Purchase Price</th>
            <th colspan="2">Sale Price</th>
        </tr>
        <tr>
            <th>Unit</th><th>Total</th>
            <th>Unit</th><th>Total</th>
            <th>Unit</th><th>Total</th>
            <th>Unit</th><th>Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($details as $i => $row)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $row->product->name ?? '—' }}</td>
            <td class="text-right">{{ number_format($row->qty, $qp) }}</td>
            <td class="text-right">{{ $fmt($row->issued_purchase_unit_price) }}</td>
            <td class="text-right">{{ $fmt($row->issued_purchase_total) }}</td>
            {{-- IS2028: Issued Location / Sale Price / Unit must show the unit
                 SALE price. It rendered issued_purchase_unit_price, so the cell
                 repeated the purchase figure while the Total beside it was
                 already built from issued_sale_unit_price - unit x qty did not
                 equal its own total. Same defect fixed on the entry screen under
                 IS2013; print and view were missed then. --}}
            <td class="text-right">{{ $fmt($row->issued_sale_unit_price) }}</td>
            <td class="text-right">{{ $fmt($row->issued_sale_total) }}</td>
            <td class="text-right">{{ $fmt($row->received_purchase_unit_price) }}</td>
            <td class="text-right">{{ $fmt($row->received_purchase_total) }}</td>
            <td class="text-right">{{ $fmt($row->received_sale_unit_price) }}</td>
            <td class="text-right">{{ $fmt($row->received_sale_total) }}</td>
            <td></td>
        </tr>
        @empty
        <tr><td colspan="12" class="text-center">No items.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-right">Total</th>
            <th class="text-center">—</th>
            <th class="text-right">{{ $fmt($details->sum('issued_purchase_total')) }}</th>
            <th class="text-center">—</th>
            <th class="text-right">{{ $fmt($details->sum('issued_sale_total')) }}</th>
            <th class="text-center">—</th>
            <th class="text-right">{{ $fmt($details->sum('received_purchase_total')) }}</th>
            <th class="text-center">—</th>
            <th class="text-right">{{ $fmt($details->sum('received_sale_total')) }}</th>
            <th></th>
        </tr>
    </tfoot>
</table>

{{-- Signatures --}}
<div class="sigs">
    @foreach(['Prepared By','Approved By','Handed Over By','Received By','Date'] as $sig)
    <div class="sig-block">
        <div class="sig-line"></div>
        <small>{{ $sig }}</small>
    </div>
    @endforeach
</div>

{{-- Footer --}}
@if (!empty($reports_footer) && !empty($reports_footer->value))
    <div class="footer">{!! $reports_footer->value !!}</div>
@endif

<script>window.onload = function () { window.print(); };</script>
</body>
</html>
