@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.supplier_payments'))
@section('suppliers_content')
<section class="content-header"><h1>@lang('suppliers::lang.supplier_payments')</h1></section>
<section class="content main-content-inner">
    <div class="box box-primary"><div class="box-body">
        @include('suppliers::partials.tabs', [
            'active' => 'payments',
            'supplierId' => $selectedSupplierId,
        ])
        <div class="supplier-payment-filter-row">
            <div class="supplier-payment-filter-field">
                {!! Form::select('supplier_id', $suppliers, $selectedSupplierId ?: null, [
                    'class' => 'form-control supplier-remote-select',
                    'id' => 'supplier_payment_filter',
                    'placeholder' => __('lang_v1.all'),
                    'data-ajax-url' => $supplierLookupUrl,
                    'data-allow-clear' => '1',
                ]) !!}
            </div>
        </div>
        <div id="supplier_payments_table_toolbar" class="supplier-dt-toolbar-host" aria-label="Supplier payments table controls"></div>
        <div class="table-responsive supplier-full-table-shell supplier-payment-table-shell">
            <table
                class="table table-bordered table-striped supplier-full-width-table"
                id="supplier_payments_table"
                style="width:100%"
                data-source-url="{{ $paymentsDataUrl }}"
                data-selected-supplier="{{ $selectedSupplierId }}"
            >
                <thead><tr>
                    <th>@lang('suppliers::lang.date')</th>
                    <th>@lang('suppliers::lang.supplier')</th>
                    <th>@lang('suppliers::lang.reference_no')</th>
                    <th>@lang('suppliers::lang.amount')</th>
                    <th>@lang('suppliers::lang.payment_method')</th>
                    <th>@lang('suppliers::lang.actions')</th>
                </tr></thead>
            </table>
        </div>
    </div></div>
</section>

<div class="modal fade supplier-payment-view-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade supplier-payment-edit-modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
@endsection
