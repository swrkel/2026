@push('product_css')
<link rel="stylesheet" href="{{ asset('modules/product/css/settings/index.css') }}">
@endpush
@push('product_scripts')
<script src="{{ asset('modules/product/js/settings/index.js') }}"></script>
@endpush

@extends('product::layouts.app', ['title'=>__('product::product.product_settings'), 'heading'=>__('product::product.product_settings')])
@section('product_content')
<ul class="nav nav-tabs">
<li class="active"><a href="#general" data-toggle="tab">@lang('product::lang.general')</a></li>
<li><a href="#categories" data-toggle="tab">@lang('product::categories.categories')</a></li>
<li><a href="#brands" data-toggle="tab">@lang('product::brands.brands')</a></li>
<li><a href="#units" data-toggle="tab">@lang('product::units.units')</a></li>
<li><a href="#variations" data-toggle="tab">@lang('product::variations.variations')</a></li>
</ul>
<div class="tab-content" style="padding-top:15px;">
@include('product::tabs.general')
@include('product::tabs.categories')
@include('product::tabs.brands')
@include('product::tabs.units')
@include('product::tabs.variations')
</div>
@endsection
