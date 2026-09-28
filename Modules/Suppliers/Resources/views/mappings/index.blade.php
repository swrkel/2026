@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.supplier_product_mapping'))

@section('suppliers_content')
@php
    $mappingRows = old('mappings');

    if (! is_array($mappingRows) || count($mappingRows) === 0) {
        $mappingRows = [[
            'product_id' => old('product_id'),
            'supplier_sku' => old('supplier_sku'),
        ]];
    }
@endphp

<section class="content-header">
    <h1>@lang('suppliers::lang.supplier_product_mapping')</h1>
</section>

<section class="content main-content-inner supplier-mapping-page">
    <div class="box box-primary">
        <div class="box-body">
            @include('suppliers::partials.tabs', [
                'active' => 'mappings',
                'supplierId' => $selectedSupplierId,
            ])

            {!! Form::open([
                'route' => 'suppliers.mappings.store',
                'method' => 'post',
                'id' => 'supplier_product_mapping_form',
            ]) !!}
                <div class="row supplier-selector-row">
                    <div class="col-md-6">
                        {!! Form::label('supplier_id', __('suppliers::lang.supplier')) !!}
                        {!! Form::select('supplier_id', $suppliers, old('supplier_id', $selectedSupplierId), [
                            'class' => 'form-control supplier-remote-select',
                            'id' => 'supplier_mapping_supplier_id',
                            'required' => true,
                            'data-refresh-url' => route('suppliers.mappings.index'),
                            'data-ajax-url' => $supplierLookupUrl,
                            'data-allow-clear' => '0',
                        ]) !!}
                    </div>
                </div>

                <div class="mapping-section-heading">
                    <div>
                        <h4>Products to Map</h4>
                        <p>Select all products that should be mapped to this supplier.</p>
                    </div>
                    <button type="button" class="btn btn-info" id="add_supplier_product_mapping_row">
                        <i class="fa fa-plus"></i> Add Product
                    </button>
                </div>

                <div id="supplier_product_mapping_rows">
                    @foreach ($mappingRows as $rowIndex => $mappingRow)
                        <div class="supplier-product-mapping-row" data-row-index="{{ $rowIndex }}">
                            <div class="row">
                                <div class="col-md-6 mapping-product-column">
                                    <label for="mapping_product_{{ $rowIndex }}">
                                        @lang('suppliers::lang.product')
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        name="mappings[{{ $rowIndex }}][product_id]"
                                        id="mapping_product_{{ $rowIndex }}"
                                        class="form-control supplier-product-select"
                                        data-ajax-url="{{ $productLookupUrl }}"
                                        required
                                    >
                                        <option value="">@lang('suppliers::lang.please_select')</option>
                                        @php($selectedProductId = (int) data_get($mappingRow, 'product_id'))
                                        @if($selectedProductId > 0 && $products->has($selectedProductId))
                                            <option value="{{ $selectedProductId }}" selected>{{ $products->get($selectedProductId) }}</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <label for="mapping_sku_{{ $rowIndex }}">
                                        @lang('suppliers::lang.supplier_sku')
                                    </label>
                                    <input
                                        type="text"
                                        name="mappings[{{ $rowIndex }}][supplier_sku]"
                                        id="mapping_sku_{{ $rowIndex }}"
                                        value="{{ data_get($mappingRow, 'supplier_sku') }}"
                                        class="form-control"
                                        maxlength="191"
                                    >
                                </div>

                                <div class="col-md-1 mapping-remove-column">
                                    <button
                                        type="button"
                                        class="btn btn-danger remove-supplier-product-mapping-row"
                                        title="Remove product"
                                        aria-label="Remove product"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mapping-form-actions">
                    <button type="submit" class="btn btn-primary" id="save_supplier_product_mappings">
                        <i class="fa fa-save"></i> Save All Mappings
                    </button>
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</section>
@endsection

@push('suppliers_styles')
<style>
    .supplier-mapping-page .supplier-selector-row {
        margin-bottom: 22px;
    }

    .supplier-mapping-page .mapping-section-heading {
        align-items: center;
        border-bottom: 1px solid #e6edf5;
        display: flex;
        justify-content: space-between;
        margin-bottom: 14px;
        padding-bottom: 12px;
    }

    .supplier-mapping-page .mapping-section-heading h4 {
        font-weight: 700;
        margin: 0 0 4px;
    }

    .supplier-mapping-page .mapping-section-heading p {
        color: #6b7280;
        margin: 0;
    }

    .supplier-mapping-page .supplier-product-mapping-row {
        background: #f8fbff;
        border: 1px solid #dce8f4;
        border-radius: 10px;
        margin-bottom: 12px;
        padding: 15px 12px;
    }

    .supplier-mapping-page .mapping-remove-column {
        align-items: flex-end;
        display: flex;
        min-height: 74px;
    }

    .supplier-mapping-page .mapping-remove-column .btn {
        height: 40px;
        min-width: 42px;
    }

    .supplier-mapping-page .mapping-form-actions {
        margin-top: 18px;
    }

    @media (max-width: 991px) {
        .supplier-mapping-page .mapping-section-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .supplier-mapping-page .mapping-remove-column {
            min-height: auto;
            padding-top: 12px;
        }
    }
</style>
@endpush

@push('javascript')
<script type="text/template" id="supplier_product_mapping_row_template">
    <div class="supplier-product-mapping-row" data-row-index="__INDEX__">
        <div class="row">
            <div class="col-md-6 mapping-product-column">
                <label for="mapping_product___INDEX__">
                    @lang('suppliers::lang.product')
                    <span class="text-danger">*</span>
                </label>
                <select
                    name="mappings[__INDEX__][product_id]"
                    id="mapping_product___INDEX__"
                    class="form-control supplier-product-select"
                    data-ajax-url="{{ $productLookupUrl }}"
                    required
                >
                    <option value="">@lang('suppliers::lang.please_select')</option>
                </select>
            </div>

            <div class="col-md-5">
                <label for="mapping_sku___INDEX__">@lang('suppliers::lang.supplier_sku')</label>
                <input
                    type="text"
                    name="mappings[__INDEX__][supplier_sku]"
                    id="mapping_sku___INDEX__"
                    class="form-control"
                    maxlength="191"
                >
            </div>

            <div class="col-md-1 mapping-remove-column">
                <button
                    type="button"
                    class="btn btn-danger remove-supplier-product-mapping-row"
                    title="Remove product"
                    aria-label="Remove product"
                >
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</script>

<script>
(function ($) {
    'use strict';

    var nextMappingRowIndex = {{ count($mappingRows) }};

    function initialiseProductSelect($select) {
        if (!$.fn.select2) {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        var ajaxUrl = $select.data('ajax-url');
        var options = {
            width: '100%',
            placeholder: @json(__('suppliers::lang.please_select')),
            allowClear: true
        };

        if (ajaxUrl) {
            options.ajax = {
                url: ajaxUrl,
                dataType: 'json',
                delay: 150,
                cache: true,
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return data;
                }
            };
            options.minimumInputLength = 0;
        }

        $select.select2(options);
    }

    function updateRemoveButtons() {
        var rowCount = $('#supplier_product_mapping_rows .supplier-product-mapping-row').length;

        $('#supplier_product_mapping_rows .remove-supplier-product-mapping-row')
            .prop('disabled', rowCount <= 1)
            .toggleClass('disabled', rowCount <= 1);
    }

    function selectedProductIds() {
        var selected = {};

        $('#supplier_product_mapping_rows .supplier-product-select').each(function () {
            var value = String($(this).val() || '');
            if (value) {
                selected[value] = (selected[value] || 0) + 1;
            }
        });

        return selected;
    }

    function refreshDuplicateProductState() {
        var selected = selectedProductIds();

        $('#supplier_product_mapping_rows .supplier-product-select').each(function () {
            var $select = $(this);
            var value = String($select.val() || '');
            var isDuplicate = value && selected[value] > 1;
            var $row = $select.closest('.supplier-product-mapping-row');

            $row.toggleClass('has-error', !!isDuplicate);
            $row.find('.duplicate-product-message').remove();

            if (isDuplicate) {
                var $messageTarget = $select.next('.select2');
                if (!$messageTarget.length) {
                    $messageTarget = $select;
                }

                $('<div class="help-block duplicate-product-message">The same product cannot be selected more than once.</div>')
                    .insertAfter($messageTarget);
            }
        });
    }

    $(function () {
        if (window.SupplierTabsPerformance && typeof window.SupplierTabsPerformance.initRemoteSelect === 'function') {
            window.SupplierTabsPerformance.initRemoteSelect($('#supplier_mapping_supplier_id'));
        }

        $('#supplier_product_mapping_rows .supplier-product-select').each(function () {
            initialiseProductSelect($(this));
        });

        updateRemoveButtons();
        refreshDuplicateProductState();
    });

    $(document).on('click', '#add_supplier_product_mapping_row', function () {
        var template = $('#supplier_product_mapping_row_template').html()
            .replace(/__INDEX__/g, nextMappingRowIndex);

        var $row = $(template);
        $('#supplier_product_mapping_rows').append($row);
        initialiseProductSelect($row.find('.supplier-product-select'));

        nextMappingRowIndex += 1;
        updateRemoveButtons();

        if ($.fn.select2) {
            $row.find('.supplier-product-select').select2('open');
        } else {
            $row.find('.supplier-product-select').trigger('focus');
        }
    });

    $(document).on('click', '.remove-supplier-product-mapping-row', function () {
        var $rows = $('#supplier_product_mapping_rows .supplier-product-mapping-row');
        if ($rows.length <= 1) {
            return;
        }

        var $row = $(this).closest('.supplier-product-mapping-row');
        var $select = $row.find('.supplier-product-select');

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $row.remove();
        updateRemoveButtons();
        refreshDuplicateProductState();
    });

    $(document).on('change', '.supplier-product-select', refreshDuplicateProductState);

    $(document).on('submit', '#supplier_product_mapping_form', function (event) {
        refreshDuplicateProductState();

        if ($(this).find('.duplicate-product-message').length) {
            event.preventDefault();
            return false;
        }

        $('#save_supplier_product_mappings')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    });

    $(document).on('change', '#supplier_mapping_supplier_id', function () {
        var supplierId = parseInt($(this).val(), 10);
        var baseUrl = $(this).data('refresh-url');

        if (!supplierId || !baseUrl) {
            return;
        }

        var currentSupplierId = parseInt(new URLSearchParams(window.location.search).get('supplier_id'), 10);
        if (currentSupplierId === supplierId) {
            return;
        }

        var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
        window.location.assign(baseUrl + separator + 'supplier_id=' + encodeURIComponent(supplierId));
    });
})(jQuery);
</script>
@endpush
