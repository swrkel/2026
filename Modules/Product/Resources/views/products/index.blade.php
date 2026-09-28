@push('product_css')
<link rel="stylesheet" href="{{ asset('modules/product/css/products/index.css') }}">
@endpush
@push('product_scripts')
<script src="{{ asset('modules/product/js/products/index.js') }}"></script>
@endpush

@extends('product::layouts.app', ['title'=>__('product::product.products'), 'heading'=>__('product::product.products')])
@section('product_content')
@include('product::partials.toolbar')
<div class="box"><div class="box-header"><a class="btn btn-primary" href="{{ route('product.create') }}">@lang('product::common.add')</a></div><div class="box-body">
<table class="table table-bordered table-striped" id="product_table"><thead><tr><th>@lang('product::common.actions')</th><th>@lang('product::common.name')</th><th>@lang('product::product.sku')</th><th>@lang('product::product.type')</th><th>@lang('product::product.enable_stock')</th></tr></thead></table>
</div></div>
<script src="{{ asset('modules/product/js/products/index.js') }}"></script>
@endsection
