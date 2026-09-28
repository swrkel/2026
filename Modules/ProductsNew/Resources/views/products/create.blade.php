@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Add Product')
@section('productsnew_page_subtitle', 'Create a product using the Products New master form')

@section('productsnew_content')

    <div class="pn-card pn-form-card">
        @include('productsnew::products.partials.form', [
            'action' => route('products-new.products.store'),
            'method' => 'post'
        ])
    </div>
@endsection
