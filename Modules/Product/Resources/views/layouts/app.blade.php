@extends('layouts.app')
@section('title', $title ?? __('product::product.product_module'))

@section('css')
    @parent
    <link rel="stylesheet" href="{{ asset('modules/product/css/product-module.css') }}">
    @stack('product_css')
@endsection

@section('content')
<section class="content-header product-content-header">
    <h1>{{ $heading ?? __('product::product.product_module') }}</h1>
</section>
<section class="content main-content-inner product-module-wrapper">
    @yield('product_content')
</section>
@endsection

@section('javascript')
    @parent
    <script src="{{ asset('modules/product/js/shared/module.js') }}"></script>
    @stack('product_scripts')
@endsection
