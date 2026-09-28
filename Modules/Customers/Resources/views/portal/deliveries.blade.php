@extends('customers::portal.layout')

@section('title', 'My Deliveries')

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
.dd-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border-radius:12px;border:1px solid #e5e7eb;}
.dd-table{width:100%;min-width:980px;border-collapse:collapse;background:#fff;}
.dd-table th{background:#f8fafc;color:#334155;font-size:12px;text-transform:uppercase;letter-spacing:.03em;text-align:left;padding:11px;border-bottom:1px solid #e5e7eb;white-space:nowrap;}
.dd-table td{padding:11px;border-bottom:1px solid #f1f5f9;vertical-align:middle;white-space:nowrap;}
.dd-badge{display:inline-block;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:800;text-transform:capitalize;}
.dd-badge-pending{background:#fef3c7;color:#92400e;}
.dd-badge-approved{background:#dbeafe;color:#1d4ed8;}
.dd-badge-loaded,.dd-badge-dispatched,.dd-badge-in_transit{background:#e0f2fe;color:#0369a1;}
.dd-badge-delivered{background:#dcfce7;color:#166534;}
.dd-badge-cancelled{background:#fee2e2;color:#991b1b;}
.dd-action{color:#2563eb;font-weight:800;text-decoration:none;margin-right:10px;}
@media(max-width:900px){.dd-cards{grid-template-columns:repeat(2,minmax(0,1fr));}.dd-page{padding:12px;}}
@media(max-width:560px){.dd-cards{grid-template-columns:1fr;}.dd-filter-row .form-group{width:100%;}.dd-btn{width:100%;}}
</style>

<div class="dd-page">
    <h1 class="dd-title">My Deliveries</h1>
    <div class="dd-subtitle">Track dispatches, delivery status and delivery history for your account.</div>

    <div class="dd-cards">
        <div class="dd-card"><div class="dd-card-label">Pending</div><div class="dd-card-value">{{ $summary['pending'] ?? 0 }}</div></div>
        <div class="dd-card"><div class="dd-card-label">In Transit</div><div class="dd-card-value">{{ $summary['in_transit'] ?? 0 }}</div></div>
        <div class="dd-card"><div class="dd-card-label">Delivered This Month</div><div class="dd-card-value">{{ $summary['delivered_this_month'] ?? 0 }}</div></div>
        <div class="dd-card"><div class="dd-card-label">Total Records</div><div class="dd-card-value">{{ $summary['total'] ?? 0 }}</div></div>
    </div>

    <div class="dd-panel">
        <form method="GET" action="{{ route('customers.portal.deliveries') }}" class="dd-filter-row">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Delivery / Order / Vehicle">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Status</option>
                    @foreach(['pending'=>'Pending','approved'=>'Approved','loaded'=>'Loaded','dispatched'=>'Dispatched','in_transit'=>'In Transit','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['status'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="form-group"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
            <button type="submit" class="dd-btn">Filter</button>
            <a href="{{ route('customers.portal.deliveries') }}" class="dd-btn" style="background:#64748b;">Reset</a>
        </form>

        <div class="dd-table-wrap">
            <table class="dd-table">
                <thead>
                    <tr>
                        <th>Delivery No</th>
                        <th>Order No</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Dispatch Date</th>
                        <th>Expected Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                        @php $statusClass = 'dd-badge-' . str_replace(' ', '_', strtolower($delivery->status ?? 'pending')); @endphp
                        <tr>
                            <td><strong>{{ $delivery->delivery_no }}</strong></td>
                            <td>{{ $delivery->order_no }}</td>
                            <td>{{ $delivery->vehicle }}</td>
                            <td>{{ $delivery->driver }}</td>
                            <td>{{ !empty($delivery->dispatch_date) ? date('Y-m-d', strtotime($delivery->dispatch_date)) : '-' }}</td>
                            <td>{{ !empty($delivery->expected_date) ? date('Y-m-d', strtotime($delivery->expected_date)) : '-' }}</td>
                            <td><span class="dd-badge {{ $statusClass }}">{{ str_replace('_',' ', $delivery->status) }}</span></td>
                            <td style="text-align:right;">{{ number_format((float)($delivery->amount ?? 0), 2) }}</td>
                            <td>
                                <a class="dd-action" href="{{ route('customers.portal.deliveries.show', $delivery->id) }}">View</a>
                                <a class="dd-action" href="{{ route('customers.portal.deliveries.track', $delivery->id) }}">Track</a>
                                <a class="dd-action" href="{{ route('customers.portal.deliveries.print', $delivery->id) }}" target="_blank">Print</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;color:#64748b;padding:24px;">No delivery records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
