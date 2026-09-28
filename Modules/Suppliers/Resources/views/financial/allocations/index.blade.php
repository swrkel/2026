@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.allocations'))

@section('suppliers_content')
<section class="content-header">
    <h1>{{ __('suppliers::lang.allocations') }}</h1>
</section>

<section class="content suppliers-module suppliers-financial-page" data-supplier-financial-page="allocations">
    @include('suppliers::financial.allocations.partials.filters')
    @include('suppliers::financial.allocations.partials.summary')
    @include('suppliers::financial.allocations.partials.table')
</section>
@endsection

@section('javascript')
    <script src="{{ asset('modules/suppliers/js/suppliers/financial/allocations.js') }}"></script>
@endsection
