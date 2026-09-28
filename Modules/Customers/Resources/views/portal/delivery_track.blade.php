@extends('customers::portal.layout')

@section('title', 'Track Delivery')

@section('content')
@include('customers::portal.partials_nav')

<style>
.dd-page{padding:22px;background:#f6f8fb;min-height:100vh;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}
.dd-title{font-size:24px;font-weight:800;margin:0 0 4px;color:#0f172a;}
.dd-subtitle{color:#64748b;margin-bottom:18px;}
.dd-panel{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:18px;box-shadow:0 8px 24px rgba(15,23,42,.06);margin-bottom:16px;}
.dd-grid{display:grid;grid-template-columns:2fr 1fr;gap:16px;}
.dd-info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
.dd-info{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:12px;}
.dd-info-label{font-size:11px;color:#64748b;font-weight:800;text-transform:uppercase;}
.dd-info-value{font-size:14px;color:#0f172a;font-weight:900;margin-top:5px;}
.dd-badge{display:inline-block;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:800;text-transform:capitalize;}
.dd-badge-pending{background:#fef3c7;color:#92400e;}
.dd-badge-approved{background:#dbeafe;color:#1d4ed8;}
.dd-badge-loaded,.dd-badge-dispatched,.dd-badge-in_transit,.dd-badge-arrived{background:#e0f2fe;color:#0369a1;}
.dd-badge-delivered{background:#dcfce7;color:#166534;}
.dd-badge-cancelled{background:#fee2e2;color:#991b1b;}
.dd-timeline{position:relative;padding-left:24px;}
.dd-timeline:before{content:"";position:absolute;left:9px;top:8px;bottom:8px;width:2px;background:#dbeafe;}
.dd-timeline-item{position:relative;margin-bottom:14px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:12px 14px;}
.dd-timeline-item:before{content:"";position:absolute;left:-21px;top:15px;width:12px;height:12px;border-radius:999px;background:#93c5fd;border:2px solid #fff;box-shadow:0 0 0 2px #93c5fd;}
.dd-timeline-title{font-weight:900;color:#0f172a;}
.dd-timeline-meta{font-size:12px;color:#64748b;font-weight:700;margin-top:4px;}
.dd-map{height:260px;border-radius:16px;border:1px dashed #cbd5e1;background:linear-gradient(135deg,#eff6ff,#f8fafc);display:flex;align-items:center;justify-content:center;text-align:center;color:#64748b;font-weight:800;padding:20px;}
.dd-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border-radius:12px;border:1px solid #e5e7eb;}
.dd-table{width:100%;min-width:760px;border-collapse:collapse;background:#fff;}
.dd-table th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em;text-align:left;padding:11px;border-bottom:1px solid #e5e7eb;white-space:nowrap;}
.dd-table td{padding:11px;border-bottom:1px solid #f1f5f9;vertical-align:middle;white-space:nowrap;}
.dd-action{border-radius:10px;padding:8px 12px;background:#2563eb;color:#fff;text-decoration:none;font-weight:800;font-size:12px;display:inline-block;}
@media(max-width:900px){.dd-grid{grid-template-columns:1fr;}.dd-info-grid{grid-template-columns:repeat(2,minmax(0,1fr));}.dd-page{padding:12px;}}
@media(max-width:560px){.dd-info-grid{grid-template-columns:1fr;}}
</style>

<div class="dd-page">
    <h1 class="dd-title">Track Delivery</h1>
    <div class="dd-subtitle">Delivery {{ $delivery->delivery_no }} · Order {{ $delivery->order_no }}</div>

    <div class="dd-grid">
        <div>
            <div class="dd-panel">
                @php $statusClass = 'dd-badge-' . str_replace(' ', '_', strtolower($delivery->status ?? 'pending')); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;">
                    <strong style="font-size:18px;">Delivery Information</strong>
                    <span class="dd-badge {{ $statusClass }}">{{ str_replace('_',' ', $delivery->status) }}</span>
                </div>
                <div class="dd-info-grid">
                    <div class="dd-info"><div class="dd-info-label">Delivery No</div><div class="dd-info-value">{{ $delivery->delivery_no }}</div></div>
                    <div class="dd-info"><div class="dd-info-label">Order No</div><div class="dd-info-value">{{ $delivery->order_no }}</div></div>
                    <div class="dd-info"><div class="dd-info-label">Vehicle</div><div class="dd-info-value">{{ $delivery->vehicle }}</div></div>
                    <div class="dd-info"><div class="dd-info-label">Driver</div><div class="dd-info-value">{{ $delivery->driver }}</div></div>
                    <div class="dd-info"><div class="dd-info-label">Dispatch Date</div><div class="dd-info-value">{{ !empty($delivery->dispatch_date) ? date('Y-m-d', strtotime($delivery->dispatch_date)) : '-' }}</div></div>
                    <div class="dd-info"><div class="dd-info-label">Expected Date</div><div class="dd-info-value">{{ !empty($delivery->expected_date) ? date('Y-m-d', strtotime($delivery->expected_date)) : '-' }}</div></div>
                </div>
            </div>

            <div class="dd-panel">
                <strong style="font-size:18px;display:block;margin-bottom:12px;">Delivery Timeline</strong>
                <div class="dd-timeline">
                    @foreach($timeline as $step)
                        <div class="dd-timeline-item">
                            <div class="dd-timeline-title">{{ $step->label ?? ucwords(str_replace('_',' ', $step->status ?? 'Pending')) }}</div>
                            <div class="dd-timeline-meta">
                                {{ !empty($step->datetime) ? date('Y-m-d H:i', strtotime($step->datetime)) : 'Pending update' }}
                            </div>
                            @if(!empty($step->remarks))
                                <div class="dd-timeline-meta">{{ $step->remarks }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div>
            <div class="dd-panel">
                <strong style="font-size:18px;display:block;margin-bottom:12px;">Latest Location</strong>
                <div class="dd-map">
                    @if(!empty($location) && !empty($location->latitude) && !empty($location->longitude))
                        <div>
                            <div>GPS Location</div>
                            <div style="font-size:18px;color:#0f172a;margin-top:8px;">{{ $location->latitude }}, {{ $location->longitude }}</div>
                            @if(!empty($location->last_updated_at))
                                <div style="font-size:12px;margin-top:8px;">Updated: {{ date('Y-m-d H:i', strtotime($location->last_updated_at)) }}</div>
                            @endif
                        </div>
                    @else
                        GPS location is not available yet. This page is ready for future GPS/device integration.
                    @endif
                </div>
            </div>

            <div class="dd-panel">
                <a class="dd-action" href="{{ route('customers.portal.tracking') }}">Back to Live Tracking</a>
                <a class="dd-action" href="{{ route('customers.portal.deliveries.print', $delivery->id) }}" target="_blank" style="background:#64748b;">Print</a>
            </div>
        </div>
    </div>

    <div class="dd-panel">
        <strong style="font-size:18px;display:block;margin-bottom:12px;">Products / Items</strong>
        <div class="dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Product</th><th>Quantity</th><th>Unit Price</th><th>Line Total</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->product_name ?? '-' }}</td>
                        <td style="text-align:right;">{{ number_format((float)($item->quantity ?? 0), 3) }}</td>
                        <td style="text-align:right;">{{ number_format((float)($item->unit_price ?? 0), 2) }}</td>
                        <td style="text-align:right;">{{ number_format((float)($item->line_total ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px;">No product lines found for this delivery.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
