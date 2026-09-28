@extends('productsnew::layouts.app')
@section('productsnew_page_title', 'Opening Stock Session: ' . $session->reference_no)
@section('productsnew_page_subtitle', 'Enter products individually or import many opening stock lines from a validated CSV template.')
@section('productsnew_content')
<div class="pn-card">
    <div class="pn-card-header">
        <div><strong>Session Information</strong><span class="pn-muted">{{ $session->session_date }} · {{ ucfirst($session->status) }}</span></div>
        <div class="pn-opening-actions">
            <a class="pn-btn pn-btn-light" href="{{ route('products-new.opening-stock.import.template') }}"><i class="fa fa-download"></i> Download CSV Template</a>
            <a class="pn-btn pn-btn-light" href="{{ route('products-new.opening-stock.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="pn-card-body">
        <div class="pn-summary-row">
            <div><span>Date</span><strong>{{ $session->session_date }}</strong></div>
            <div><span>Status</span><strong>{{ ucfirst($session->status) }}</strong></div>
            <div><span>Location ID</span><strong>{{ $session->location_id ?: 'Not selected' }}</strong></div>
        </div>
    </div>
</div>

<div class="pn-grid-2 pn-mt">
    <section class="pn-card">
        <div class="pn-card-header"><div><strong>Enter One Product</strong><span class="pn-muted">Post an individual opening stock line</span></div></div>
        <div class="pn-card-body">
            <form class="pn-form-grid" method="post" action="{{ route('products-new.opening-stock.line',$session->id) }}">@csrf
                <div class="pn-col-span"><label>Product</label>
                    <select name="product_id" class="form-control" data-placeholder="Type product name or SKU" required>
                        <option value="">Select Product</option>
                        @foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}</option>@endforeach
                    </select>
                </div>
                <div><label>Variation ID</label><input name="variation_id" class="form-control" placeholder="Optional"></div>
                <div><label>Qty</label><input type="number" step="0.001" min="0.001" name="qty" class="form-control" required></div>
                <div><label>Unit Cost</label><input type="number" step="0.0001" min="0" name="unit_cost" class="form-control"></div>
                <div class="pn-col-span"><label>Notes</label><input name="notes" class="form-control"></div>
                <div><button class="pn-btn pn-btn-success"><i class="fa fa-check"></i> Post Opening Stock Line</button></div>
            </form>
        </div>
    </section>

    <section class="pn-import-panel">
        <h4><i class="fa fa-file-excel-o"></i> Import Opening Stock</h4>
        <p class="pn-import-help">Use the downloadable CSV template to add many products at once. The complete file is validated before any stock is posted.</p>
        <form method="post" action="{{ route('products-new.opening-stock.import',$session->id) }}" enctype="multipart/form-data">@csrf
            <div class="pn-import-file-row">
                <div><label>Opening Stock CSV File</label><input type="file" class="form-control" name="opening_stock_file" accept=".csv,text/csv" required></div>
                <button class="pn-btn pn-btn-primary" type="submit"><i class="fa fa-upload"></i> Validate & Import</button>
            </div>
        </form>
        <div class="pn-import-meta">
            <span>Product by ID or SKU</span><span>Variation optional</span><span>Qty: 3 decimals</span><span>Cost: 4 decimals</span><span>Duplicate-file protection</span>
        </div>
        <p class="pn-muted pn-mt">Required columns: <code>product_id, sku, variation_id, variation_sku, quantity, unit_cost, notes</code>. Enter either Product ID or SKU for each row.</p>
    </section>
</div>
@endsection
