@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Product Details')
@section('productsnew_page_subtitle', 'View complete product information')
@section('productsnew_content')
<div class="pn-card pn-product-hero">
    <div><h3>{{ $product->name }}</h3><p>SKU: {{ $product->sku ?: '-' }} | Barcode: {{ $product->barcode ?: '-' }}</p></div>
    <div><span class="pn-health pn-health-large">{{ $detail->health_score ?? 0 }}%</span></div>
</div>
<div class="pn-grid pn-grid-3 pn-product-details-grid">
    <div class="pn-card pn-product-detail-card"><h4>General</h4><p><b>Type:</b> {{ $product->type }}</p><p><b>Category:</b> {{ $detail->category_name ?? '-' }}</p><p><b>Brand:</b> {{ $detail->brand_name ?? '-' }}</p><p><b>Unit:</b> {{ $detail->unit_short_name ?? '-' }}</p></div>
    <div class="pn-card pn-product-detail-card"><h4>Tax & Selling</h4><p><b>Tax:</b> {{ $detail->tax_name ?? '-' }}</p><p><b>Tax Type:</b> {{ ucfirst($product->tax_type ?? 'exclusive') }}</p><p><b>Status:</b> {{ $product->not_for_selling ? 'Inactive' : 'Active' }}</p></div>
    <div class="pn-card pn-product-detail-card"><h4>Inventory</h4><p><b>Stock Enabled:</b> {{ $product->enable_stock ? 'Yes' : 'No' }}</p><p><b>Alert Qty:</b> {{ $product->alert_quantity ?: '0' }}</p><p><b>Weight:</b> {{ $product->weight ?: '-' }}</p></div>
</div>
<div class="pn-card pn-product-description-card"><h4>Description</h4><p>{{ $product->product_description ?: 'No description available.' }}</p></div>
<div class="pn-card pn-tabs-line pn-product-actions-card"><a href="{{ route('products-new.products.edit',$product->id) }}">Edit</a><a href="{{ route('products-new.products.timeline',$product->id) }}">Timeline</a><a href="{{ route('products-new.products.health',$product->id) }}">Health Score</a><a href="{{ route('products-new.barcode.index') }}?product_id={{ $product->id }}">Barcode</a></div>
@endsection
