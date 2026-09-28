@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.advances'))

@section('suppliers_content')
<section class="content-header">
    <h1>{{ __('suppliers::lang.advances') }}</h1>
</section>

<section class="content suppliers-module suppliers-financial-page" data-supplier-financial-page="advances">
    @include('suppliers::financial.advances.partials.filters')
    @include('suppliers::financial.advances.partials.summary')
    @include('suppliers::financial.advances.partials.table')
</section>
@endsection

@section('javascript')
    <script src="{{ asset('modules/suppliers/js/suppliers/financial/advances.js') }}"></script>
@endsection
