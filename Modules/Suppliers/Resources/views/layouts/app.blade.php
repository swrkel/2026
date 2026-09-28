@extends('layouts.app')

@section('title', __('suppliers::lang.supplier_module'))

@section('css')
    <link rel="stylesheet" href="{{ asset('modules/suppliers/css/suppliers.css') }}?v=20260918-s763-supplier-payment-1">
    @stack('suppliers_styles')
@endsection

@section('content')
<section class="content-header suppliers-system-header">
    <h1>@yield('module_title', __('suppliers::lang.supplier_module'))</h1>
</section>
<section class="content suppliers-module">
    @php($supplierStatus = \Modules\Suppliers\Utils\SupplierViewRuntimeUtil::statusPayload())
    @if ($supplierStatus)
        <div class="alert {{ $supplierStatus['success'] ? 'alert-success' : 'alert-danger' }} suppliers-alert">
            {{ $supplierStatus['message'] }}
        </div>
    @endif
    @yield('suppliers_content')
</section>
@endsection

@push('javascript')
    <script src="{{ asset('modules/suppliers/js/suppliers.js') }}?v=20260919-s768-payment-edit-1"></script>
    @stack('suppliers_scripts')
@endpush
