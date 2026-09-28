@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Edit Product')
@section('productsnew_page_subtitle', 'Update product master information safely')

@section('productsnew_content')

    <div class="pn-card pn-form-card">
        @include('productsnew::products.partials.form', [
            'action' => route('products-new.products.update', $product->id),
            'method' => 'put'
        ])
    </div>
@endsection
