@extends('layouts.app')
@section('title', $title ?? __('pos::messages.advanced_sales'))

@section('content')
@include('pos::partials.erp-standard-styles')
<section class="content pos-erp-standard-ui communication-hub-ui">
    <div class="ch-shell">
        <div class="ch-hero pos-module-hero">
            <div>
                <div class="ch-eyebrow">POS Module</div>
                <h1>{{ $title ?? __('pos::messages.advanced_sales') }}</h1>
                <p>Advanced product search, held sales and sale merging workspace.</p>
            </div>
            <div class="ch-quick-actions">
                <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a href="{{ url('/pos-module/sales') }}" class="btn btn-primary btn-sm"><i class="fa fa-shopping-cart"></i> Standard Sales</a>
            </div>
        </div>

        <div class="row ch-kpi-row">
            <div class="col-md-4"><div class="ch-kpi ch-kpi-blue"><div class="ch-kpi-icon"><i class="fa fa-search"></i></div><div><small>Product Search</small><h2>Ready</h2><span>Search products instantly</span></div></div></div>
            <div class="col-md-4"><div class="ch-kpi ch-kpi-orange"><div class="ch-kpi-icon"><i class="fa fa-pause-circle"></i></div><div><small>Held Sales</small><h2>0</h2><span>Sales waiting to resume</span></div></div></div>
            <div class="col-md-4"><div class="ch-kpi ch-kpi-green"><div class="ch-kpi-icon"><i class="fa fa-compress"></i></div><div><small>Merge Sales</small><h2>Ready</h2><span>Combine selected sales</span></div></div></div>
        </div>

        <div class="box box-solid ch-card">
            <div class="box-header with-border ch-card-header">
                <h3 class="box-title"><i class="fa fa-cogs"></i> Advanced Sales Workspace</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-8">
                        <label>Search products</label>
                        <div class="input-group">
                            <input type="text" id="pos-advanced-product-search" class="form-control" placeholder="Type product name, SKU or barcode">
                            <span class="input-group-btn"><button type="button" class="btn btn-primary"><i class="fa fa-search"></i> Search</button></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Workspace status</label>
                        <div class="alert alert-success" style="margin-bottom:0;padding:7px 12px;"><i class="fa fa-check-circle"></i> {{ __('pos::messages.advanced_sales_ready') }}</div>
                    </div>
                </div>
                <div id="pos-advanced-sale-screen" class="well text-center" style="margin-top:20px;padding:35px;">
                    <i class="fa fa-shopping-cart fa-3x text-muted"></i>
                    <h4>Advanced Sales is available.</h4>
                    <p class="text-muted">Search for a product to begin.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
