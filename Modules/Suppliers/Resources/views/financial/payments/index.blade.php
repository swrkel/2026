@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.payments'))

@section('suppliers_content')
<section class="content-header">
    <h1>{{ __('suppliers::lang.payments') }}</h1>
</section>

<section class="content suppliers-module suppliers-financial-page" data-supplier-financial-page="payments">
    @include('suppliers::financial.payments.partials.filters')
    @include('suppliers::financial.payments.partials.summary')
    @include('suppliers::financial.payments.partials.table')
</section>
@endsection

@section('javascript')
    <script src="{{ asset('modules/suppliers/js/suppliers/financial/payments.js') }}"></script>
@endsection
