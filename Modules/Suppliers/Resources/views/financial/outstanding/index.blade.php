@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.outstanding'))

@section('suppliers_content')
<section class="content-header">
    <h1>{{ __('suppliers::lang.outstanding') }}</h1>
</section>

<section class="content suppliers-module suppliers-financial-page" data-supplier-financial-page="outstanding">
    @include('suppliers::financial.outstanding.partials.filters')
    @include('suppliers::financial.outstanding.partials.summary')
    @include('suppliers::financial.outstanding.partials.table')
</section>
@endsection

@section('javascript')
    <script src="{{ asset('modules/suppliers/js/suppliers/financial/outstanding.js') }}"></script>
@endsection
