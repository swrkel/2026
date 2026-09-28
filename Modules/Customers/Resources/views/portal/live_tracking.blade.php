@extends('customers::portal.layout')

@section('title', 'Live Delivery Tracking')

@section('content')
@include('customers::portal.partials_nav')

<style>
.dd-page{padding:22px;background:#f6f8fb;min-height:100vh;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}
.dd-title{font-size:24px;font-weight:800;margin:0 0 4px;color:#0f172a;}
.dd-subtitle{color:#64748b;margin-bottom:18px;}
.dd-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px;}
.dd-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.06);}
.dd-card-label{font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.dd-card-value{font-size:26px;font-weight:900;color:#0f172a;margin-top:6px;}
.dd-panel{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:18px;box-shadow:0 8px 24px rgba(15,23,42,.06);}
.dd-filter-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;align-items:end;}
.dd-filter-row .form-group{margin:0;min-width:170px;}
.dd-filter-row label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:5px;}
.dd-filter-row input,.dd-filter-row select{height:38px;border:1px solid #cbd5e1;border-radius:10px;padding:6px 10px;width:100%;}
.dd-btn{height:38px;border:0;border-radius:10px;padding:0 14px;font-weight:800;background:#2563eb;color:#fff;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;}
.dd-track-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
.dd-track-card{border:1px solid #e5e7eb;border-radius:16px;background:#fff;padding:16px;box-shadow:0 8px 20px rgba(15,23,42,.05);}
.dd-track-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px;}
.dd-track-no{font-size:16px;font-weight:900;color:#0f172a;}
.dd-track-meta{font-size:12px;color:#64748b;font-weight:700;margin-top:3px;}
.dd-badge{display:inline-block;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:800;text-transform:capitalize;white-space:nowrap;}
.dd-badge-pending{background:#fef3c7;color:#92400e;}
.dd-badge-approved{background:#dbeafe;color:#1d4ed8;}
.dd-badge-loaded,.dd-badge-dispatched,.dd-badge-in_transit,.dd-badge-arrived{background:#e0f2fe;color:#0369a1;}
.dd-badge-delivered{background:#dcfce7;color:#166534;}
.dd-badge-cancelled{background:#fee2e2;color:#991b1b;}
.dd-mini-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:10px 0 12px;}
.dd-mini-label{font-size:11px;text-transform:uppercase;color:#64748b;font-weight:800;}
.dd-mini-value{font-size:13px;color:#0f172a;font-weight:800;}
.dd-timeline{display:flex;gap:6px;align-items:center;overflow-x:auto;padding:6px 0 2px;}
.dd-step{min-width:96px;border-radius:12px;border:1px solid #e5e7eb;background:#f8fafc;padding:8px;text-align:center;font-size:11px;font-weight:800;color:#64748b;}
.dd-step-active{background:#eff6ff;border-color:#93c5fd;color:#1d4ed8;}
.dd-step-done{background:#ecfdf5;border-color:#86efac;color:#166534;}
.dd-location{margin-top:10px;border-radius:12px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px;color:#475569;font-size:12px;font-weight:700;}
.dd-empty{border:1px dashed #cbd5e1;border-radius:14px;padding:24px;text-align:center;color:#64748b;background:#f8fafc;font-weight:700;}
.dd-actions{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;}
.dd-action{border-radius:10px;padding:8px 12px;background:#2563eb;color:#fff;text-decoration:none;font-weight:800;font-size:12px;}
.dd-action-secondary{background:#64748b;}
@media(max-width:900px){.dd-cards{grid-template-columns:repeat(2,minmax(0,1fr));}.dd-track-list{grid-template-columns:1fr;}.dd-page{padding:12px;}}
@media(max-width:560px){.dd-cards{grid-template-columns:1fr;}.dd-filter-row .form-group{width:100%;}.dd-btn{width:100%;}.dd-mini-grid{grid-template-columns:1fr;}}
</style>

<div class="dd-page">
    <h1 class="dd-title">Live Delivery Tracking</h1>
    <div class="dd-subtitle">View active delivery status, timeline updates and latest vehicle location where GPS data is available.</div>

    <div class="dd-cards">
        <div class="dd-card"><div class="dd-card-label">Pending</div><div class="dd-card-value">{{ $summary['pending'] ?? 0 }}</div></div>
        <div class="dd-card"><div class="dd-card-label">In Transit</div><div class="dd-card-value">{{ $summary['in_transit'] ?? 0 }}</div></div>
        <div class="dd-card"><div class="dd-card-label">Delivered This Month</div><div class="dd-card-value">{{ $summary['delivered_this_month'] ?? 0 }}</div></div>
        <div class="dd-card"><div class="dd-card-label">Total Deliveries</div><div class="dd-card-value">{{ $summary['total'] ?? 0 }}</div></div>
    </div>

    <div class="dd-panel">
        <form method="GET" action="{{ route('customers.portal.tracking') }}" class="dd-filter-row">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Delivery / Order / Vehicle">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Status</option>
                    @foreach(['pending'=>'Pending','approved'=>'Approved','loaded'=>'Loaded','dispatched'=>'Dispatched','in_transit'=>'In Transit','arrived'=>'Arrived','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['status'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="form-group"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
            <button type="submit" class="dd-btn">Filter</button>
            <a href="{{ route('customers.portal.tracking') }}" class="dd-btn" style="background:#64748b;">Reset</a>
        </form>

        @if($deliveries->count() > 0)
            <div class="dd-track-list">
                @foreach($deliveries as $delivery)
                    @php
                        $status = $delivery->status ?? 'pending';
                        $statusClass = 'dd-badge-' . str_replace(' ', '_', strtolower($status));
                        $timeline = $delivery->timeline ?? collect();
                    @endphp
                    <div class="dd-track-card">
                        <div class="dd-track-head">
                            <div>
                                <div class="dd-track-no">{{ $delivery->delivery_no }}</div>
                                <div class="dd-track-meta">Order: {{ $delivery->order_no }}</div>
                            </div>
                            <span class="dd-badge {{ $statusClass }}">{{ str_replace('_',' ', $status) }}</span>
                        </div>

                        <div class="dd-mini-grid">
                            <div><div class="dd-mini-label">Vehicle</div><div class="dd-mini-value">{{ $delivery->vehicle }}</div></div>
                            <div><div class="dd-mini-label">Driver</div><div class="dd-mini-value">{{ $delivery->driver }}</div></div>
                            <div><div class="dd-mini-label">Dispatch Date</div><div class="dd-mini-value">{{ !empty($delivery->dispatch_date) ? date('Y-m-d', strtotime($delivery->dispatch_date)) : '-' }}</div></div>
                            <div><div class="dd-mini-label">Expected Date</div><div class="dd-mini-value">{{ !empty($delivery->expected_date) ? date('Y-m-d', strtotime($delivery->expected_date)) : '-' }}</div></div>
                        </div>

                        <div class="dd-timeline">
                            @foreach($timeline as $step)
                                @php
                                    $stepStatus = $step->status ?? 'pending';
                                    $isCurrent = $stepStatus === $status;
                                    $hasDate = !empty($step->datetime);
                                @endphp
                                <div class="dd-step {{ $isCurrent ? 'dd-step-active' : ($hasDate ? 'dd-step-done' : '') }}">
                                    {{ $step->label ?? ucwords(str_replace('_',' ', $stepStatus)) }}
                                </div>
                            @endforeach
                        </div>

                        <div class="dd-location">
                            @if(!empty($delivery->location) && !empty($delivery->location->latitude) && !empty($delivery->location->longitude))
                                Last GPS: {{ $delivery->location->latitude }}, {{ $delivery->location->longitude }}
                                @if(!empty($delivery->location->last_updated_at))
                                    · {{ date('Y-m-d H:i', strtotime($delivery->location->last_updated_at)) }}
                                @endif
                            @else
                                GPS location is not available yet. Current status is shown from delivery records.
                            @endif
                        </div>

                        <div class="dd-actions">
                            <a class="dd-action" href="{{ route('customers.portal.deliveries.track', $delivery->id) }}">Track Details</a>
                            <a class="dd-action dd-action-secondary" href="{{ route('customers.portal.deliveries.show', $delivery->id) }}">View Delivery</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="dd-empty">No delivery tracking records found.</div>
        @endif
    </div>
</div>
@endsection
