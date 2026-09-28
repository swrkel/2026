@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="productsnew-page-title-row">
    <div>
        <h2>Products New Dashboard</h2>
        <p class="pn-muted">Management summary for the new standalone Products module. Legacy Product module remains untouched.</p>
    </div>
    <div class="productsnew-toolbar-actions">
        <a class="pn-btn pn-btn-primary" href="{{ route('products-new.products.create') }}">Add Product</a>
        <a class="pn-btn" href="{{ route('products-new.kpi.index') }}">KPI Centre</a>
        <a class="pn-btn" href="{{ route('products-new.reports.index') }}">Reports</a>
    </div>
</div>

@include('productsnew::dashboard.partials.kpi-cards', ['overview' => $overview])

<div class="productsnew-dashboard-grid">
    <div class="pn-card">
        <div class="pn-card-header"><strong>Immediate Attention</strong><a href="{{ route('products-new.reports.low-stock') }}">Low Stock Report</a></div>
        <div class="pn-card-body">
            @php
                $lowStock = $overview['attention']['low_stock'] ?? [];
            @endphp
            @if(count($lowStock))
                <table class="table productsnew-table compact-table">
                    <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Qty</th><th class="text-right">Alert</th></tr></thead>
                    <tbody>
                    @foreach($lowStock as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->sku }}</td>
                            <td class="text-right">{{ number_format((float)$row->qty_available, 3) }}</td>
                            <td class="text-right">{{ number_format((float)$row->alert_quantity, 3) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="pn-muted">No immediate low stock attention items found for the current business/location scope.</p>
            @endif
        </div>
    </div>
    <div class="pn-card">
        <div class="pn-card-header"><strong>Recent Product Activity</strong><a href="{{ route('products-new.intelligence.index') }}">Intelligence</a></div>
        <div class="pn-card-body">
            @php
                $activities = $overview['recent_activity'] ?? [];
            @endphp
            @if(count($activities))
                <div class="productsnew-activity-list">
                    @foreach($activities as $activity)
                        <div class="productsnew-activity-item">
                            <strong>{{ ucwords(str_replace('_',' ', $activity->event)) }}</strong>
                            <span>{{ $activity->product_name ?? 'Product' }} @if(!empty($activity->sku)) / {{ $activity->sku }} @endif</span>
                            <small>{{ $activity->created_at }}</small>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="pn-muted">Activity will appear after product create/edit, pricing, stock, batch, serial, and workflow events are posted.</p>
            @endif
        </div>
    </div>
</div>

<div class="pn-card">
    <div class="pn-card-header"><strong>Recently Added Products</strong><a href="{{ route('products-new.products.index') }}">View All</a></div>
    <div class="pn-card-body">@include('productsnew::products.partials.table', ['products'=>$recent])</div>
</div>
@endsection
