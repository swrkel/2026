@push('product_css')
<link rel="stylesheet" href="{{ asset('modules/product/css/products/form.css') }}">
@endpush
@push('product_scripts')
<script src="{{ asset('modules/product/js/products/form.js') }}"></script>
<script src="{{ asset('modules/product/js/products/images.js') }}"></script>
<script src="{{ asset('modules/product/js/products/variations.js') }}"></script>
@endpush

@extends('product::layouts.app')
@section('title', __('product::product.add_product'))

@section('content')
<section class="content-header">
    <h1>@lang('product::product.add_product')</h1>
</section>

<section class="content product-module product-create-page">
    {!! Form::open(['route' => 'product.products.store', 'method' => 'post', 'id' => 'product_create_form', 'enctype' => 'multipart/form-data']) !!}
        @include('product::products.forms.main', ['mode' => 'create'])
    {!! Form::close() !!}
</section>
@endsection
@push('scripts')
    <script src="{{ asset('modules/product/js/shared/module.js') }}?v={{ file_exists(public_path('modules/product/js/shared/module.js')) ? filemtime(public_path('modules/product/js/shared/module.js')) : time() }}"></script>
    <script src="{{ asset('modules/product/js/products/form.js') }}?v={{ file_exists(public_path('modules/product/js/products/form.js')) ? filemtime(public_path('modules/product/js/products/form.js')) : time() }}"></script>
    <script src="{{ asset('modules/product/js/products/variations.js') }}?v={{ file_exists(public_path('modules/product/js/products/variations.js')) ? filemtime(public_path('modules/product/js/products/variations.js')) : time() }}"></script>
    <script src="{{ asset('modules/product/js/products/barcode.js') }}?v={{ file_exists(public_path('modules/product/js/products/barcode.js')) ? filemtime(public_path('modules/product/js/products/barcode.js')) : time() }}"></script>
    <script src="{{ asset('modules/product/js/products/images.js') }}?v={{ file_exists(public_path('modules/product/js/products/images.js')) ? filemtime(public_path('modules/product/js/products/images.js')) : time() }}"></script>
@endpush
