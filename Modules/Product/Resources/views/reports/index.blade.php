@push('product_css')
<link rel="stylesheet" href="{{ asset('modules/product/css/reports/index.css') }}">
@endpush
@push('product_scripts')
<script src="{{ asset('modules/product/js/reports/index.js') }}"></script>
@endpush

@extends('product::layouts.app', ['title'=>__('product::product.product_reports'), 'heading'=>__('product::product.product_reports')])
@section('product_content')
<a class="btn btn-primary" href="{{ route('product.reports.stock-alert') }}">@lang('product::stock.stock_alert')</a>
@endsection
