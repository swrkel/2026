@extends('productsnew::layouts.app')
@section('productsnew_page_title','Product KPI Centre')
@section('productsnew_page_subtitle','Product quality, stock risk, expiry, warranty and price-change monitoring in one workspace.')
@section('productsnew_content')
<div class="pn-page-intro">
    <div>
        <div class="pn-eyebrow">Management Control Centre</div>
        <h2>Product KPI Centre</h2>
        <p>Prioritise product master quality, operational stock risks and time-sensitive inventory actions.</p>
    </div>
    <div class="pn-page-actions">
        @if(\Illuminate\Support\Facades\Route::has('products-new.products.create'))
            <a class="pn-btn pn-btn-light" href="{{ route('products-new.products.create') }}"><i class="fa fa-plus"></i> Add Product</a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('products-new.stock-history.index'))
            <a class="pn-btn pn-btn-light" href="{{ route('products-new.stock-history.index') }}"><i class="fa fa-history"></i> Stock History</a>
        @endif
        <form method="POST" action="{{ route('products-new.kpi.snapshot') }}">
            @csrf
            <input type="hidden" name="location_id" value="{{ $filters['location_id'] ?? '' }}">
            <button type="submit" class="pn-btn pn-btn-primary"><i class="fa fa-camera"></i> Save Snapshot</button>
        </form>
    </div>
</div>

@include('productsnew::dashboard.partials.kpi-cards', ['overview' => $overview])

<div class="productsnew-grid-3">
    <section class="pn-card">
        <header class="pn-card-header"><div><h3>Product Master</h3><p>Master-data coverage and daily additions</p></div><span class="pn-badge">{{ number_format($overview['master']['total'] ?? 0) }}</span></header>
        <div class="pn-card-body">
            <table class="table compact-table"><tbody>
            <tr><td>Total Products</td><td class="text-right"><strong>{{ number_format($overview['master']['total'] ?? 0) }}</strong></td></tr>
            <tr><td>Stock Enabled</td><td class="text-right"><strong>{{ number_format($overview['master']['stock_enabled'] ?? 0) }}</strong></td></tr>
            <tr><td>Service Items</td><td class="text-right"><strong>{{ number_format($overview['master']['service_items'] ?? 0) }}</strong></td></tr>
            <tr><td>Created Today</td><td class="text-right"><strong>{{ number_format($overview['master']['created_today'] ?? 0) }}</strong></td></tr>
            </tbody></table>
        </div>
    </section>
    <section class="pn-card">
        <header class="pn-card-header"><div><h3>Data Quality</h3><p>Incomplete product master information</p></div><span class="pn-badge badge-warning">Review</span></header>
        <div class="pn-card-body">
            <table class="table compact-table"><tbody>
            <tr><td>Without SKU</td><td class="text-right"><strong>{{ number_format($overview['quality']['without_sku'] ?? 0) }}</strong></td></tr>
            <tr><td>Without Category</td><td class="text-right"><strong>{{ number_format($overview['quality']['without_category'] ?? 0) }}</strong></td></tr>
            <tr><td>Without Brand</td><td class="text-right"><strong>{{ number_format($overview['quality']['without_brand'] ?? 0) }}</strong></td></tr>
            <tr><td>Low Health</td><td class="text-right"><strong>{{ number_format($overview['quality']['low_health'] ?? 0) }}</strong></td></tr>
            </tbody></table>
        </div>
    </section>
    <section class="pn-card">
        <header class="pn-card-header"><div><h3>Inventory Position</h3><p>Quantity and stock exception overview</p></div><span class="pn-badge badge-info">Live</span></header>
        <div class="pn-card-body">
            <table class="table compact-table"><tbody>
            <tr><td>Available Quantity</td><td class="text-right"><strong>{{ number_format($overview['stock']['available_qty'] ?? 0, 3) }}</strong></td></tr>
            <tr><td>Low Stock Lines</td><td class="text-right"><strong>{{ number_format($overview['stock']['low_stock_lines'] ?? 0) }}</strong></td></tr>
            <tr><td>Negative Lines</td><td class="text-right"><strong>{{ number_format($overview['stock']['negative_stock_lines'] ?? 0) }}</strong></td></tr>
            <tr><td>Zero Stock Lines</td><td class="text-right"><strong>{{ number_format($overview['stock']['zero_stock_lines'] ?? 0) }}</strong></td></tr>
            </tbody></table>
        </div>
    </section>
</div>

<section class="pn-card">
    <header class="pn-card-header"><div><h3>Operational Review Queues</h3><p>Open detailed pages for corrective action</p></div><span class="pn-muted">Generated {{ $overview['generated_at'] ?? '' }}</span></header>
    <div class="pn-card-body productsnew-quick-links">
        <a href="{{ route('products-new.reports.low-stock') }}"><i class="fa fa-level-down"></i> Low Stock</a>
        <a href="{{ route('products-new.reports.negative-overstock') }}"><i class="fa fa-exclamation-triangle"></i> Negative / Overstock</a>
        <a href="{{ route('products-new.reports.expiry') }}"><i class="fa fa-calendar-times-o"></i> Expiry</a>
        <a href="{{ route('products-new.reports.serial') }}"><i class="fa fa-barcode"></i> Serial</a>
        <a href="{{ route('products-new.data-cleanup.index') }}"><i class="fa fa-magic"></i> Data Cleanup</a>
        <a href="{{ route('products-new.import-export.index') }}"><i class="fa fa-exchange"></i> Import / Export</a>
    </div>
</section>
@endsection
