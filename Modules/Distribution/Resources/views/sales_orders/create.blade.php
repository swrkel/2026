@extends('distribution::layouts.app')

@section('title', 'Create Sales Order')

@section('content')
    <section class="content">
        <form method="POST" action="{{ route('distribution.sales_orders.store') }}" id="sales_order_form">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    @component('distribution::components.widget', ['class' => 'box-primary'])
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-12 text-center">
                                <h4 style="margin: 0; font-weight: bold;">{{ $business->name }}</h4>
                                <p style="margin: 0; font-size: 12px;">Address: {{ $location->name ?? '' }}</p>
                                <p style="margin: 0; font-size: 12px;">Contact Number: {{ $location->mobile ?? '' }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Date:</label>
                                    <div class="col-sm-8">
                                        <input type="datetime-local" class="form-control" name="date" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Customer:</label>
                                    <div class="col-sm-8">
                                        <div style="display:flex; gap:6px;">
                                            <select name="customer_id" id="customer_id" class="form-control select2" style="width: 100%;" required>
                                                <option value="">Please Select</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->id }}" 
                                                        data-address="{{ $customer->landmark ?: ($customer->address_line_1 ?: ($customer->address ?: '')) }}"
                                                        data-contact="{{ $customer->mobile ?? $customer->landline }}">
                                                        {{ $customer->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-primary btn-sm" id="so_add_new_customer_btn" style="padding: 2px 8px;">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Location:</label>
                                    <div class="col-sm-8">
                                        <input type="text" id="customer_address" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Customer Contact No:</label>
                                    <div class="col-sm-8">
                                        <input type="text" id="customer_contact" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-right" style="margin-bottom: 15px;">
                                    <h4 style="margin: 0; color: #d9534f; font-weight: bold;">Sales Order</h4>
                                    <p style="margin: 0; font-weight: bold;">Sales Order No: <span id="display_so_no">{{ $sales_order_no }}</span></p>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Sales Rep:</label>
                                    <div class="col-sm-8">
                                        {!! Form::select('sales_rep_id', $salesReps, $default_sales_rep_id, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => 'Please Select']) !!}
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Route:</label>
                                    <div class="col-sm-8">
                                        {!! Form::select('route_id', [], null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => 'Please select the Route']) !!}
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Vehicle No:</label>
                                    <div class="col-sm-8">
                                        {!! Form::select('vehicle_id', $vehicles, null, ['class' => 'form-control select2', 'id' => 'vehicle_id', 'style' => 'width: 100%;', 'placeholder' => 'Please Select']) !!}
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-4 control-label text-right">Product Category:</label>
                                    <div class="col-sm-8">
                                        {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => 'Please Select']) !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row" style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                            <div class="col-md-3">
                                <label>Search Products</label>
                                <select id="search_product" class="form-control select2" style="width: 100%;">
                                    <option value="">Please select a category first</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label>Unit</label>
                                <input type="text" id="temp_unit" class="form-control" readonly>
                            </div>
                            <div class="col-md-1">
                                <label>Quantity</label>
                                <input type="number" id="temp_qty" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-1">
                                <label>Unit Price</label>
                                <input type="number" id="temp_unit_price" class="form-control" value="0">
                            </div>
                            <div class="col-md-1">
                                <label>Tax Type</label>
                                <select id="temp_tax_type" class="form-control select2" style="width: 100%;">
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label>Price Inc. Tax</label>
                                <input type="number" id="temp_price_inc_tax" class="form-control" value="0" readonly>
                            </div>
                            <div class="col-md-2">
                                <label>Discount Type</label>
                                <select id="temp_discount_type" class="form-control select2" style="width: 100%;">
                                    <option value="fixed">Fixed</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label>Discount</label>
                                <input type="number" id="temp_discount" class="form-control" value="0">
                            </div>
                            <div class="col-md-1" style="padding-top: 25px;">
                                <button type="button" class="btn btn-success" id="add_product_btn">Add</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="so_lines_table">
                                <thead style="background: #fff; border: 1px solid #ff0000;">
                                    <tr style="color: #ff0000; font-weight: bold;">
                                        <th>Index No</th>
                                        <th>Quantity</th>
                                        <th>Product</th>
                                        <th>Unit Price</th>
                                        <th>Tax</th>
                                        <th>Price Inc. Tax</th>
                                        <th>Total Price</th>
                                        <th>Amount</th>
                                        <th>Discount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="text-right" style="font-weight: bold; color: #ff0000;">TOTAL 0.00</td>
                                        <td id="footer_total_amount" style="font-weight: bold; border: 1px solid #ff0000;">0.00</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="text-right" style="font-weight: bold; color: #ff0000;">DISCOUNT 0.00</td>
                                        <td id="footer_total_discount" style="font-weight: bold; border: 1px solid #ff0000;">0.00</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row" style="margin-top: 15px;">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="shipping_note">Shipping Note:</label>
                                    <textarea name="shipping_note" class="form-control" rows="3" placeholder="Shipping Note"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="shipping_details">Shipping Details:</label>
                                    <textarea name="shipping_details" class="form-control" rows="3" placeholder="Shipping Details"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="invoice_note">Sales Order Note:</label>
                                    <textarea name="invoice_note" class="form-control" rows="3" placeholder="Sales Order Note"></textarea>
                                </div>
                            </div>
                        </div>
                    @endcomponent
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary btn-lg">Save Sales Order</button>
                </div>
            </div>
        </form>
    </section>

    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('distribution::contacts.quick_create', ['quick_add' => true])
    </div>
@endsection

@section('javascript')
    <script>
        $(function() {
            $('.select2').select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
            
            // Customer selection logic
            $('#customer_id').on('change', function() {
                var selected = $(this).find(':selected');
                $('#customer_address').val(selected.data('address') || '');
                $('#customer_contact').val(selected.data('contact') || '');
            });
            if ($('#customer_id').val()) {
                $('#customer_id').trigger('change');
            }

            // Product searching by category
            $('select[name="category_id"]').on('change', function() {
                var catId = $(this).val();
                var $search = $('#search_product');
                if (!catId) {
                    $search.empty().append('<option value="">Please select a category first</option>').trigger('change');
                    $search.select2({ width: '100%', minimumResultsForSearch: 0 });
                    return;
                }
                $.get('{{ route('distribution.invoices.products') }}', { category_id: catId }, function(products) {
                    $search.empty().append('<option value="">Please Select</option>');
                    Object.keys(products).forEach(function(id) {
                        $search.append('<option value="' + id + '">' + products[id] + '</option>');
                    });
                    $search.trigger('change');
                    $search.select2({ width: '100%', minimumResultsForSearch: 0 });
                });
            });

            // Fetch product info when selected in search
            $('#search_product').on('change', function() {
                var productId = $(this).val();
                if (!productId) return;
                $.get('{{ url('distribution/invoices/product-info') }}', { product_id: productId }, function(data) {
                    var unit_name = (data.units && data.units[0]) ? data.units[0].name : '';
                    var price = data.unit_price || 0;
                    $('#temp_unit').val(unit_name);
                    $('#temp_unit_price').val(price);
                    $('#temp_price_inc_tax').val(data.price_inc_tax || price);
                });
            });

            function parseNum(v) { return parseFloat(String(v || '0').replace(/,/g, '')) || 0; }
            function formatMoney(v) { return parseNum(v).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
            function addSalesOrderLine(productId, productName, qty, unitPrice, discType, lineDiscount, isFree, isFreeBottles, isFreeAuto) {
                qty = parseNum(qty); unitPrice = parseNum(unitPrice); lineDiscount = parseNum(lineDiscount);
                var amount = qty * unitPrice;
                var finalAmount = Math.max(0, amount - lineDiscount);
                var index = $('#so_lines_table tbody tr').length + 1;
                var badge = '';
                if (parseInt(isFree || 0) === 1) { badge += ' <span class="label label-success">Free</span>'; }
                if (parseInt(isFreeBottles || 0) === 1) { badge += ' <span class="label label-info">Free Bottle</span>'; }
                var row = '<tr>' +
                    '<td>' + index + '</td>' +
                    '<td><input type="hidden" name="product_id[]" value="' + productId + '">' +
                    '<input type="hidden" name="is_free[]" value="' + (isFree || 0) + '">' +
                    '<input type="hidden" name="is_free_bottles[]" value="' + (isFreeBottles || 0) + '">' +
                    '<input type="hidden" name="is_free_auto[]" value="' + (isFreeAuto || 0) + '">' +
                    '<input type="number" step="0.01" class="form-control qty-input" name="qty[]" value="' + qty.toFixed(2) + '"></td>' +
                    '<td>' + productName + badge + '</td>' +
                    '<td><input type="number" step="0.01" class="form-control price-input" name="unit_price[]" value="' + unitPrice.toFixed(2) + '"></td>' +
                    '<td>0.00</td>' +
                    '<td>' + formatMoney(unitPrice) + '</td>' +
                    '<td>' + formatMoney(amount) + '</td>' +
                    '<td class="final-amount">' + formatMoney(finalAmount) + '</td>' +
                    '<td><input type="hidden" name="discount_type[]" value="' + discType + '"><input type="number" step="0.01" class="form-control disc-input" name="discount[]" value="' + lineDiscount.toFixed(2) + '"></td>' +
                    '<td><button type="button" class="btn btn-xs btn-danger remove-line"><i class="fa fa-trash"></i></button></td>' +
                    '</tr>';
                $('#so_lines_table tbody').append(row);
            }

            // Add product to table
            $('#add_product_btn').on('click', function() {
                var productId = $('#search_product').val();
                var productName = $('#search_product').find(":selected").text();
                if (!productId) {
                    toastr.error('Please select a product');
                    return;
                }

                var qty = parseFloat($('#temp_qty').val()) || 0;
                var unitPrice = parseFloat($('#temp_unit_price').val()) || 0;
                var discount = parseFloat($('#temp_discount').val()) || 0;
                var discType = $('#temp_discount_type').val();
                var amount = qty * unitPrice;
                var lineDiscount = discType === 'fixed' ? discount : (amount * (discount / 100));

                addSalesOrderLine(productId, productName, qty, unitPrice, discType, lineDiscount, 0, 0, 0);

                $.get('{{ route('distribution.invoices.free_issues_check') }}', { product_id: productId, qty: qty }, function(rows) {
                    $.each(rows || [], function(i, row) {
                        addSalesOrderLine(row.product_id, row.product_name, row.free_qty, 0, 'fixed', 0, row.is_free || 0, row.is_free_bottles || 0, 1);
                    });
                    updateTotals();
                }).fail(function() {
                    updateTotals();
                });
            });

            function updateTotals() {
                var totalAmount = 0;
                var totalDiscount = 0;
                $('#so_lines_table tbody tr').each(function() {
                    totalAmount += parseNum($(this).find('.final-amount').text()) || 0;
                    totalDiscount += parseNum($(this).find('.disc-input').val()) || 0;
                });
                $('#footer_total_amount').text(formatMoney(totalAmount));
                $('#footer_total_discount').text(formatMoney(totalDiscount));
            }

            $(document).on('click', '.remove-line', function() {
                $(this).closest('tr').remove();
                updateTotals();
            });

            // Quick Add Customer
            $(document).on('click', '#so_add_new_customer_btn', function() {
                $('.contact_modal').find('select#contact_type').val('customer').trigger('change');
                $('.contact_modal').modal('show');
            });

            $(document).on('contact.quick_add.success', function(e, result) {
                if (!result || !result.data) return;
                var option = new Option(result.data.name, result.data.id, true, true);
                $('#customer_id').append(option).trigger('change');
                $('.contact_modal').modal('hide');
            });
        });
    </script>
@endsection
