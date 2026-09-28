@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.supplier_records'))

@section('suppliers_content')
@php
    // SUPPLIERS-LIST-NO-FLASH-20260821
    // Render the amount completely on the server. Do not rely on the global
    // display_currency pass to repair an uncompiled/custom Blade directive.
    $supplierListCurrencySymbol = (string) (session('currency.symbol') ?: session('business.currency_symbol') ?: '');
    $supplierListCurrencyPrecision = max(0, min(6, (int) (session('business.currency_precision') ?? 2)));
@endphp
<section class="content-header supplier-content-header">
    <h1>@lang('suppliers::lang.supplier_records')</h1>
</section>

<section class="content supplier-module-content">
    <div class="box box-primary supplier-list-box">
        <div class="box-body">
            @include('suppliers::suppliers.partials.list-toolbar')

            <div class="table-responsive supplier-table-wrap">
                <table class="table table-bordered table-striped supplier-standard-table" id="supplier_records_table">
                    <thead>
                        <tr>
                            <th class="supplier-actions-col">@lang('suppliers::lang.actions')</th>
                            <th class="supplier-number-col">@lang('suppliers::lang.supplier_no')</th>
                            <th>@lang('suppliers::lang.name')</th>
                            <th>@lang('suppliers::lang.mobile')</th>
                            <th>@lang('suppliers::lang.email')</th>
                            <th class="text-right">@lang('contact.total_due')</th>
                            <th>@lang('suppliers::lang.created_at')</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td class="supplier-actions-col">
                                @include('suppliers::suppliers.partials.actions', ['supplier' => $supplier])
                            </td>
                            <td class="supplier-number-col" title="{{ $supplier->contact_id }}">{{ $supplier->contact_id }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->mobile }}</td>
                            <td>{{ $supplier->email }}</td>
                            <td class="text-right">
                                <span class="supplier-total-due"
                                      data-orig-value="{{ (float) ($supplier->total_due ?? 0) }}">
                                    {{ $supplierListCurrencySymbol }}{{ $supplierListCurrencySymbol !== '' ? ' ' : '' }}{{ number_format((float) ($supplier->total_due ?? 0), $supplierListCurrencyPrecision) }}
                                </span>
                            </td>
                            <td>{{ optional($supplier->created_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center supplier-empty-row">@lang('suppliers::lang.no_suppliers_found')</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="supplier-pagination-wrap">
                {{ $suppliers->appends(\Modules\Suppliers\Utils\SupplierViewRuntimeUtil::query())->links() }}
            </div>
        </div>
    </div>
</section>

{{-- Required by the established supplier financial and linked-account actions. --}}
<div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade linked_account_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@push('javascript')
<script src="{{ asset('modules/suppliers/js/suppliers/list/index.js') }}?v=20260910-action-anchor-2"></script>
<script src="{{ asset('modules/suppliers/js/suppliers/list/paid-on-native.js') }}?v=20260918-date-authority-1"></script>
<script src="{{ asset('modules/suppliers/js/suppliers/list/account-selection-stable.js') }}?v=20260917-account-fast-stable-3"></script>
@endpush
