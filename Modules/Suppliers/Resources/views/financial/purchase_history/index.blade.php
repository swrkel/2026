@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.purchase_history'))

@section('suppliers_content')
<section class="content-header">
    <h1>{{ __('suppliers::lang.purchase_history') }}</h1>
</section>

<section class="content suppliers-module suppliers-financial-page" data-supplier-financial-page="purchase_history">
    @include('suppliers::financial.purchase_history.partials.filters')
    @include('suppliers::financial.purchase_history.partials.summary')
    @include('suppliers::financial.purchase_history.partials.table')
</section>
@endsection

@section('javascript')
    <script src="{{ asset('modules/suppliers/js/suppliers/financial/purchase_history.js') }}"></script>
@endsection
