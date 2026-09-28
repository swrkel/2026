@extends('layouts.app')

@section('title', __('disstocktransfer::lang.add_dis_stock_transfer'))

@section('css')
    <style>
        .select2 {
            width: 100% !important;
        }

        .table th,
        .table td {
            vertical-align: middle !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #5897fb;
            color: white;
        }
    </style>
@endsection

@section('content')
    <section class="content">

        {!! Form::open([
            'url' => action('\Modules\DisStockTransfer\Http\Controllers\DisStockTransferController@store'),
            'method' => 'POST',
            'id' => 'dis_stock_transfer_form',
        ]) !!}

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('disstocktransfer::lang.add_dis_stock_transfer') }}</h3>
            </div>

            <div class="box-body">

                <div class="row">

                    {{-- DATE --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date', __('Date:*')) !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('date', \Carbon\Carbon::now()->format('Y-m-d H:i'), [
                                    'class' => 'form-control datetimepicker',
                                    'required',
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    {{-- Reference No --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('ref_no', __('Reference No:')) !!}
                            {!! Form::text('ref_no', $ref_no, ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>

                    {{-- Location (From) --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('Location (From):*')) !!}
                            <div class="input-group">
                            {!! Form::select('location_id', $locations, $default_location, ['class' => 'form-control select2', 'required', 'id' => 'location_id']) !!}
                            @if(auth()->user()->can('business_settings.access'))
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default btn-modal"
                                        data-href="{{ action('BusinessLocationController@create') }}"
                                        data-container=".view_modal">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            @endif
                            </div>
                        </div>
                    </div>

                    {{-- Store --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('store_id', __('From Store:*')) !!}
                            {!! Form::select('store_id', $stores, $default_store, ['class' => 'form-control select2', 'required', 'id' => 'store_id']) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('to_store_id', __('To Store:*')) !!}
                            {!! Form::select('to_store_id', $stores, $default_to_store, ['class' => 'form-control select2', 'required', 'id' => 'to_store_id']) !!}
                        </div>
                    </div>

                </div>

                <div class="row">

                    {{-- Vehicle --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('vehicle_id', __('Vehicle No')) !!}
                            {!! Form::select('vehicle_id', $vehicles, null, [
                                'class' => 'form-control select2',
                                'placeholder' => 'Please Select',
                            ]) !!}
                        </div>
                    </div>

                    {{-- Product Category --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('product_category', __('Product Category')) !!}
                            {!! Form::select('product_category', $categories, null, [
                                'class' => 'form-control select2',
                                'placeholder' => 'Please Select',
                            ]) !!}
                        </div>
                    </div>
                </div>

                {{-- Product Search with Select2 --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('product_search', __('Search Products:*')) !!}
                            <select id="product_search" class="form-control select2" style="width: 100%;">
                                <option value="">{{ __('Type to search products...') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <br>

                {{-- Products Table --}}
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered" id="products_table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>From Store Balance Qty</th>
                                    <th>To Store Balance Qty</th>
                                    <th>Units</th>
                                    <th>Transfer Qty</th>
                                    <th>Unit Sale Price</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="product_rows"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-right"><strong>Total:</strong></td>
                                    <td id="grand_total">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Additional Notes --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('notes', __('Additional Notes')) !!}
                            {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => '3']) !!}
                        </div>
                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-save"></i> Save Transfer
                </button>
            </div>

        </div>

        {!! Form::close() !!}

    </section>
@endsection

@section('javascript')
    <script>
        var available_units = @json($units);
        $(document).ready(function() {
            $('#product_search').on('select2:opening', function(e) {
                if (!$('#store_id').val()) {
                    e.preventDefault();
                    toastr.error('Please select a store first');
                    return;
                }
                if (!$('#to_store_id').val()) {
                    e.preventDefault();
                    toastr.error('Please select a destination store first');
                    return;
                }
                if ($('#store_id').val() === $('#to_store_id').val()) {
                    e.preventDefault();
                    toastr.error('From Store and To Store must be different');
                    return;
                }
            });

            // Initialize all regular select2
            $('.select2').select2();

            // date-time picker
            $('.datetimepicker').datetimepicker({
                format: 'YYYY-MM-DD HH:mm'
            });

            function rebuildStoreDropdowns(stores) {
                var currentFrom = $('#store_id').val();
                var currentTo = $('#to_store_id').val();

                $('#store_id, #to_store_id').empty();
                $('#store_id').append('<option value="">Select From Store</option>');
                $('#to_store_id').append('<option value="">Select To Store</option>');

                $.each(stores, function(i, store) {
                    $('#store_id').append(`<option value="${store.id}">${store.name}</option>`);
                    $('#to_store_id').append(`<option value="${store.id}">${store.name}</option>`);
                });

                if (currentFrom && $('#store_id option[value="' + currentFrom + '"]').length) {
                    $('#store_id').val(currentFrom);
                } else if (stores.length > 0) {
                    $('#store_id').val(stores[0].id);
                }

                if (currentTo && $('#to_store_id option[value="' + currentTo + '"]').length && currentTo !== $('#store_id').val()) {
                    $('#to_store_id').val(currentTo);
                } else {
                    var fallbackTo = '';
                    $.each(stores, function(i, store) {
                        if (String(store.id) !== String($('#store_id').val()) && fallbackTo === '') {
                            fallbackTo = store.id;
                        }
                    });
                    $('#to_store_id').val(fallbackTo);
                }

                $('#store_id, #to_store_id').trigger('change.select2');
            }

            // when location changes, reload stores for that location
            $('#location_id').on('change', function() {
                $('#product_rows').empty();
                $('#grand_total').text('0.00');
                $.ajax({
                    method: 'get',
                    url: "{{ url('disstocktransfer/get-store') }}",
                    data: { location_id: $(this).val() },
                    success: function(result) {
                        var stores = (result && result.stores) ? result.stores : [];
                        rebuildStoreDropdowns(stores);
                    }
                });
            });

            // update destination store qty column anytime destination store changes
            $('#to_store_id').on('change', function() {
                var toStoreId = $(this).val();
                if (!toStoreId) {
                    return;
                }
                $('#product_rows tr').each(function() {
                    var $row = $(this);
                    var variationId = $row.find('input[name^="products"][name$="[variation_id]"]').val();
                    if (variationId) {
                        $.ajax({
                            url: "{{ url('disstocktransfer/store-qty') }}",
                            data: { store_id: toStoreId, variation_id: variationId },
                            dataType: 'json',
                            success: function(res) {
                                $row.find('td').eq(3).text(res.qty);
                                var $qtyInput = $row.find('input.quantity');
                                if ($qtyInput.length) {
                                    $qtyInput.data('to-store-qty', res.qty);
                                }
                            },
                            error: function() {
                                console.log('Error fetching destination store qty for variation ' + variationId);
                            }
                        });
                    }
                });
            });

            // initialize product search select2 with ajax
            $('#product_search').select2({
                ajax: {
                    url: "{{ action('\\Modules\\DisStockTransfer\\Http\\Controllers\\DisStockTransferController@searchProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            query: params.term,
                            store_id: $('#store_id').val(),
                            to_store_id: $('#to_store_id').val(),
                            page: params.page
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    id: item.variation_id,
                                    text: item.text,
                                    product_id: item.product_id,
                                    variation_id: item.variation_id,
                                    sub_sku: item.sub_sku,
                                    unit_price: item.unit_price,
                                    store_qty: item.store_qty,
                                    to_store_qty: item.to_store_qty,
                                    unit_id: item.unit_id,
                                    unit_name: item.unit_name
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: "{{ __('Type to search products...') }}",
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateResult: formatProduct,
                templateSelection: formatProductSelection
            });

            // Custom formatter for Select2 results
            function formatProduct(product) {
                if (product.loading) return product.text;

                var markup = '<div class="select2-result-product">' +
                    '<div class="select2-result-product__title">' + product.text + '</div>';

                if (product.sub_sku) {
                    markup += '<div class="select2-result-product__sku">SKU: ' + product.sub_sku + '</div>';
                }

                markup += '<div class="select2-result-product__info">' +
                    'Store Qty: ' + product.store_qty + ' | ' +
                    'To Store Qty: ' + product.to_store_qty + ' | ' +
                    'Unit: ' + (product.unit_name || 'N/A') + ' | ' +
                    'Price: ' + formatCurrency(product.unit_price) +
                    '</div>' +
                    '</div>';

                return markup;
            }

            function formatProductSelection(product) {
                return product.text || product.text;
            }

            // When a product is selected
            $('#product_search').on('select2:select', function(e) {
                var data = e.params.data;

                // Check if product already exists in table
                if ($('#product_row_' + data.variation_id).length) {
                    toastr.warning('Product already added to the list');
                    $('#product_search').val(null).trigger('change');
                    return;
                }

                // Add product to table
                addProductToTable(data);

                // Clear the search
                $('#product_search').val(null).trigger('change');
            });

            // Function to add product to table
            function addProductToTable(product) {
                var rowId = 'product_row_' + product.variation_id;
                var row = '<tr id="' + rowId + '">' +
                    '<td>' + product.text +
                    '<input type="hidden" name="products[' + product.variation_id + '][variation_id]" value="' +
                    product.variation_id + '">' +
                    '<input type="hidden" name="products[' + product.variation_id + '][product_id]" value="' +
                    product.product_id + '">' +
                    '<input type="hidden" name="products[' + product.variation_id + '][product_name]" value="' +
                    escapeHtml(product.text) + '">' +
                    '</td>' +
                    '<td>' + product.sub_sku + '</td>' +
                    '<td>' + product.store_qty + '</td>' +
                    '<td>' + product.to_store_qty + '</td>' +
                    '<td>' +
                    '<select name="products[' + product.variation_id +
                    '][unit_id]" class="form-control unit-select" style="width: 100%;" required>' +
                    '<option value="">Select Unit</option>' +
                    (product.unit_id ? '<option value="' + product.unit_id + '" selected>' + (product.unit_name ||
                        'Unit') + '</option>' : '') +
                    '</select>' +
                    '</td>' +
                    '<td>' +
                    '<input type="number" name="products[' + product.variation_id +
                    '][quantity]" class="form-control quantity" value="0" min="0.01" max="' + product.store_qty +
                    '" step="0.01" data-unit-price="' + product.unit_price + '" data-store-qty="' + product.store_qty + '" data-to-store-qty="' + product.to_store_qty + '" required>' +
                    '</td>' +
                    '<td class="unit_price">' + formatCurrency(product.unit_price) +
                    '<input type="hidden" name="products[' + product.variation_id + '][unit_price]" value="' +
                    product.unit_price + '">' +
                    '</td>' +

                    '<td class="total">0.00</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-xs btn-danger remove-product" data-row-id="' + rowId +
                    '">' +
                    '<i class="fa fa-times"></i>' +
                    '</button>' +
                    '</td>' +
                    '</tr>';

                $('#product_rows').append(row);

                // Populate unit select with all available units
                var $unitSelect = $('#' + rowId + ' .unit-select');
                $unitSelect.empty();
                $unitSelect.append('<option value="">Select Unit</option>');
                $.each(available_units, function(id, name) {
                    var selected = (product.unit_id == id) ? ' selected' : '';
                    $unitSelect.append('<option value="' + id + '"' + selected + '>' + name + '</option>');
                });

                // Initialize select2 for the unit select
                $unitSelect.select2();

                // Calculate total
                calculateRowTotal(rowId);
                calculateGrandTotal();
            }

            // Remove product from table
            $(document).on('click', '.remove-product', function() {
                var rowId = $(this).data('row-id');
                $('#' + rowId).remove();
                calculateGrandTotal();
            });

            // Calculate row total when quantity changes
            $(document).on('keyup change', '.quantity', function() {
                var rowId = $(this).closest('tr').attr('id');
                calculateRowTotal(rowId);
                calculateGrandTotal();
            });

            // Calculate row total
            function calculateRowTotal(rowId) {
                var $row = $('#' + rowId);
                var quantity = parseFloat($row.find('.quantity').val()) || 0;
                var unitPrice = parseFloat($row.find('.quantity').data('unit-price')) || 0;
                var total = quantity * unitPrice;

                $row.find('.total').text(formatCurrency(total));
            }

            // Calculate grand total
            function calculateGrandTotal() {
                var grandTotal = 0;

                $('.total').each(function() {
                    var total = parseFloat($(this).text().replace(/[^0-9.-]+/g, "")) || 0;
                    grandTotal += total;
                });

                $('#grand_total').text(formatCurrency(grandTotal));
            }

            // Format currency
            function formatCurrency(amount) {
                return parseFloat(amount).toFixed(2);
            }

            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) {
                    return map[m];
                });
            }

            // Prevent form submission if no products added
            $('#dis_stock_transfer_form').on('submit', function(e) {
                var productCount = $('#product_rows tr').length;

                if (productCount === 0) {
                    e.preventDefault();
                    toastr.error('Please add at least one product to the transfer.');
                    return false;
                }

                // Validate all quantities
                var isValid = true;
                $('.quantity').each(function() {
                    var quantity = parseFloat($(this).val()) || 0;
                    var maxQty = parseFloat($(this).attr('max')) || 0;

                    if (quantity < 0.01) {
                        toastr.error('Transfer quantity must be at least 0.01 for all products.');
                        isValid = false;
                        return false;
                    }

                    if (quantity > maxQty) {
                        toastr.error('Quantity cannot exceed available stock.');
                        isValid = false;
                        return false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Validate unit selection
                $('.unit-select').each(function() {
                    if (!$(this).val()) {
                        toastr.error('Please select a unit for all products.');
                        isValid = false;
                        return false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                return true;
            });

            $('#location_id').trigger('change');
        });
    </script>
@endsection
