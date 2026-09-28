@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Products')
@section('productsnew_page_subtitle', 'Manage product master records')
@section('productsnew_content')
@include('productsnew::products.partials.filters')
<div class="pn-card pn-products-list-card" id="pn-products-list-card">
    <div class="pn-toolbar">
        <div><strong>Product Master</strong><span id="pn-products-total-label">{{ $products->total() }} records</span></div>
        <div class="pn-toolbar-actions"><a class="pn-btn pn-btn-primary" href="{{ route('products-new.products.create') }}">+ Add Product</a><a class="pn-btn pn-btn-light" href="{{ route('products-new.import-export.index') }}">Import / Export</a></div>
    </div>
    <div id="pn-products-table-region">
        @include('productsnew::products.partials.table')
    </div>
    <div class="pn-pagination" id="pn-products-pagination">{{ $products->links() }}</div>
</div>
@endsection
