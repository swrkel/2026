@extends('layouts.app')

@section('title', __('disstocktransfer::lang.edit_dis_stock_transfer'))

@section('css')
    <style>
        .select2 { width: 100% !important; }
        .table th, .table td { vertical-align: middle !important; }
    </style>
@endsection

@section('content')
    <section class="content">
        {!! Form::open([
            'url' => route('disstocktransfer.dis-stock-transfer.update', $transfer->id),
            'method' => 'PUT',
            'id' => 'dis_stock_transfer_form',
        ]) !!}

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('disstocktransfer::lang.edit_dis_stock_transfer') }}</h3>
            </div>

            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date', __('Date:*')) !!}
                            {!! Form::text('date', \Carbon\Carbon::parse($transfer->date)->format('Y-m-d H:i'), ['class' => 'form-control datetimepicker', 'required']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('ref_no', __('Reference No:')) !!}
                            {!! Form::text('ref_no', $transfer->reference_no, ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('Location (From):*')) !!}
                            {!! Form::select('location_id', $locations, $transfer->location_id, ['class' => 'form-control select2', 'required', 'id' => 'location_id']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('store_id', __('From Store:*')) !!}
                            {!! Form::select('store_id', $stores, $transfer->store_id, ['class' => 'form-control select2', 'required', 'id' => 'store_id']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('to_store_id', __('To Store:*')) !!}
                            {!! Form::select('to_store_id', $stores, $transfer->to_store_id, ['class' => 'form-control select2', 'required', 'id' => 'to_store_id']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('vehicle_id', __('Vehicle No')) !!}
                            {!! Form::select('vehicle_id', $vehicles, $transfer->vehicle_id, ['class' => 'form-control select2', 'required', 'placeholder' => 'Please Select']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('product_category', __('Product Category')) !!}
                            {!! Form::select('product_category', $categories, $transfer->product_category_id, ['class' => 'form-control select2', 'placeholder' => 'Please Select']) !!}
                        </div>
                    </div>
                </div>

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

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('notes', __('Additional Notes')) !!}
                            {!! Form::textarea('notes', $transfer->note, ['class' => 'form-control', 'rows' => '3']) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-save"></i> Update Transfer
                </button>
                <a href="{{ route('disstocktransfer.dis-stock-transfer.index') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        {!! Form::close() !!}
    </section>
@endsection

@section('javascript')
    <script>
        var available_units = @json($units);
        var existing_lines = @json($line_items);

        $(document).ready(function() {
            $('.select2').select2();
            $('.datetimepicker').datetimepicker({ format: 'YYYY-MM-DD HH:mm' });

            function formatCurrency(amount) {
                return parseFloat(amount || 0).toFixed(2);
            }

            function escapeHtml(text) {
                var map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
                return String(text || '').replace(/[&<>"']/g, function(m){ return map[m]; });
            }

            function calculateRowTotal(rowId) {
                var $row = $('#' + rowId);
                var quantity = parseFloat($row.find('.quantity').val()) || 0;
                var unitPrice = parseFloat($row.find('.quantity').data('unit-price')) || 0;
                $row.find('.total').text(formatCurrency(quantity * unitPrice));
            }

            function calculateGrandTotal() {
                var total = 0;
                $('#product_rows .total').each(function() {
                    total += parseFloat($(this).text()) || 0;
                });
                $('#grand_total').text(formatCurrency(total));
            }

            function addProductToTable(product) {
                var rowId = 'product_row_' + product.variation_id;
                if ($('#' + rowId).length) {
                    return;
                }

                var row = '<tr id="' + rowId + '">' +
                    '<td>' + escapeHtml(product.text || product.product_name) +
                        '<input type="hidden" name="products[' + product.variation_id + '][variation_id]" value="' + product.variation_id + '">' +
                        '<input type="hidden" name="products[' + product.variation_id + '][product_id]" value="' + product.product_id + '">' +
                    '</td>' +
                    '<td>' + escapeHtml(product.sub_sku || '') + '</td>' +
                    '<td>' + formatCurrency(product.store_qty) + '</td>' +
                    '<td>' + formatCurrency(product.to_store_qty) + '</td>' +
                    '<td><select name="products[' + product.variation_id + '][unit_id]" class="form-control unit-select" required></select></td>' +
                    '<td><input type="number" name="products[' + product.variation_id + '][quantity]" class="form-control quantity" value="' + formatCurrency(product.qty || 0) + '" min="0.01" max="' + product.store_qty + '" step="0.01" data-unit-price="' + product.unit_price + '" required></td>' +
                    '<td>' + formatCurrency(product.unit_price) + '<input type="hidden" name="products[' + product.variation_id + '][unit_price]" value="' + product.unit_price + '"></td>' +
                    '<td class="total">0.00</td>' +
                    '<td><button type="button" class="btn btn-xs btn-danger remove-product" data-row-id="' + rowId + '"><i class="fa fa-times"></i></button></td>' +
                    '</tr>';

                $('#product_rows').append(row);

                var $unitSelect = $('#' + rowId + ' .unit-select');
                $.each(available_units, function(id, name) {
                    var selected = (String(product.unit_id) === String(id)) ? ' selected' : '';
                    $unitSelect.append('<option value="' + id + '"' + selected + '>' + name + '</option>');
                });
                $unitSelect.select2();

                calculateRowTotal(rowId);
                calculateGrandTotal();
            }

            existing_lines.forEach(function(line) {
                addProductToTable({
                    variation_id: line.variation_id,
                    product_id: line.product_id,
                    product_name: line.product_name + (line.variation_name && line.variation_name !== 'DUMMY' ? (' (' + line.variation_name + ')') : ''),
                    text: line.product_name + (line.variation_name && line.variation_name !== 'DUMMY' ? (' (' + line.variation_name + ')') : ''),
                    sub_sku: line.sub_sku,
                    store_qty: line.store_qty,
                    to_store_qty: line.to_store_qty,
                    unit_price: line.unit_price,
                    unit_id: line.unit_id,
                    qty: line.qty
                });
            });

            $('#product_search').select2({
                ajax: {
                    url: "{{ action('\\Modules\\DisStockTransfer\\Http\\Controllers\\DisStockTransferController@searchProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            query: params.term,
                            store_id: $('#store_id').val(),
                            to_store_id: $('#to_store_id').val()
                        };
                    },
                    processResults: function(data) {
                        return { results: data };
                    }
                },
                minimumInputLength: 2,
                placeholder: "{{ __('Type to search products...') }}"
            });

            $('#product_search').on('select2:select', function(e) {
                var d = e.params.data;
                addProductToTable({
                    variation_id: d.variation_id,
                    product_id: d.product_id,
                    text: d.text,
                    sub_sku: d.sub_sku,
                    store_qty: d.store_qty,
                    to_store_qty: d.to_store_qty,
                    unit_price: d.unit_price,
                    unit_id: d.unit_id,
                    qty: 0
                });
                $(this).val(null).trigger('change');
            });

            $(document).on('click', '.remove-product', function() {
                $('#' + $(this).data('row-id')).remove();
                calculateGrandTotal();
            });

            $(document).on('keyup change', '.quantity', function() {
                var rowId = $(this).closest('tr').attr('id');
                calculateRowTotal(rowId);
                calculateGrandTotal();
            });

            $('#dis_stock_transfer_form').on('submit', function(e) {
                if ($('#product_rows tr').length === 0) {
                    e.preventDefault();
                    toastr.error('Please add at least one product to the transfer.');
                }
            });
        });
    </script>
@endsection
