@extends('layouts.app')

@php
    $pnModuleName = \Illuminate\Support\Facades\Lang::has('productsnew::product.module_name')
        ? __('productsnew::product.module_name')
        : 'Products New';
    $pnModuleSubtitle = \Illuminate\Support\Facades\Lang::has('productsnew::product.module_subtitle')
        ? __('productsnew::product.module_subtitle')
        : 'Standalone product intelligence, inventory, barcode and reporting module.';
    $pnPopupFlash = trim($__env->yieldContent('productsnew_flash_mode')) === 'popup';
@endphp

@section('title', trim($__env->yieldContent('productsnew_page_title')) ?: $pnModuleName)

@push('css')
<link rel="stylesheet" href="{{ asset('modules/productsnew/css/productsnew.css?v=20260806-is1919-widths-and-fonts') }}">
<link rel="stylesheet" href="{{ asset('modules/productsnew/css/stock-history.css?v=20260911-is2236') }}">
@endpush

@section('content')
<section class="productsnew-shell pn-ui-v2">
    @include('productsnew::partials.header', [
        'title' => trim($__env->yieldContent('productsnew_page_title')) ?: $pnModuleName,
        'subtitle' => trim($__env->yieldContent('productsnew_page_subtitle')) ?: $pnModuleSubtitle,
    ])

    @if(!$pnPopupFlash && session('status'))
        <div class="alert alert-success productsnew-alert no-print">
            <i class="fa fa-check-circle" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(!$pnPopupFlash && $errors->any())
        <div class="alert alert-danger productsnew-alert no-print">
            <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
            <div>
                <strong>Please correct the following:</strong>
                <ul class="pn-error-list">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <main class="productsnew-content" id="productsnew-content">
        @yield('productsnew_content')
    </main>
</section>
@endsection

@push('javascript')
<script src="{{ asset('modules/productsnew/js/productsnew.js?v=20260920-s771-category-fixes') }}"></script>
@endpush
