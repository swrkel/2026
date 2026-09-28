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
                            {!! Form::select('location_id', $locations, $default_location, ['class' => 'form-control select2', 'required']) !!}
                        </div>
                    </div>

                    {{-- Store --}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('store_id', __('From Store:*')) !!}
                            {!! Form::select('store_id', $stores, $default_store, ['class' => 'form-control select2', 'required']) !!}
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
                                    <th>Store Balance Qty</th>
                                    <th>Vehicle Balance Qty</th>
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
        $(document).ready(function() {
            // Initialize all regular select2
            $('.select2').select2();

            // date-time picker
            $('.datetimepicker').datetimepicker({
                format: 'YYYY-MM-DD HH:mm'
            });

            // Initialize product search with Select2 AJAX
            $('#product_search').select2({
                ajax: {
                    url: "{{ action('\Modules\DisStockTransfer\Http\Controllers\DisStockTransferController@searchProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            query: params.term,
                            store_id: $('#store_id').val(),
                            vehicle_id: $('#vehicle_id').val(),
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
                                    vehicle_qty: item.vehicle_qty,
                                    unit_id: item.unit_id,
                                    unit_name: item.unit_name
                                }
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
                    'Vehicle Qty: ' + product.vehicle_qty + ' | ' +
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
                    '<td>' + product.vehicle_qty + '</td>' +
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
                    '][quantity]" class="form-control quantity" value="0" min="0" max="' + product.store_qty +
                    '" data-unit-price="' + product.unit_price + '" required>' +
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

                    if (quantity <= 0) {
                        toastr.error('Please enter a valid quantity for all products.');
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
        });
    </script>
@endsection
