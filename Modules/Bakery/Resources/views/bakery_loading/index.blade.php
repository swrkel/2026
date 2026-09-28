@extends('layouts.app')
@section('title', 'Loading')
<style>
    .select2 {
        width: 100% !important;
    }

    #product_modal_bakery {
        width: 500px;
        margin: auto;
    }
</style>
@section('content')

    <section class="content-header">
        <div class="row">
            <div class="col-md-12 dip_tab">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        @if (auth()->user()->can('bakery_add_loading'))
                            <li class=" @if (session('status.tab') == 'loading' || empty(session('status.tab'))) ) active @endif">
                                <a style="font-size:13px;" href="#loading" data-toggle="tab">
                                    <i class="fa-solid fa-car"></i><strong>@lang('bakery::lang.loading')</strong>
                                </a>
                            </li>
                        @endif

                        @if (auth()->user()->can('bakery_list_loading'))
                            <li class=" @if (session('status.tab') == 'list_loading') active @endif">
                                <a style="font-size:13px;" href="#list_loading" data-toggle="tab">
                                    <strong>@lang('bakery::lang.list_loading')</strong>
                                </a>
                            </li>
                        @endif

                        @if (auth()->user()->can('bakery_returns'))
                            <li class=" @if (session('status.tab') == 'returns') active @endif">
                                <a style="font-size:13px;" href="#returns" data-toggle="tab">
                                    <strong>@lang('bakery::lang.returns')</strong>
                                </a>
                            </li>
                        @endif

                    </ul>
                </div>
            </div>
        </div>
        <div class="tab-content">


            @if (auth()->user()->can('bakery_add_loading'))
                <div class="tab-pane @if (session('status.tab') == 'loading' || empty(session('status.tab'))) active @endif" id="loading">
                    @include('bakery::bakery_loading.partials.loading')
                </div>
            @endif

            @if (auth()->user()->can('bakery_list_loading'))
                <div class="tab-pane  @if (session('status.tab') == 'list_loading') active @endif" id="list_loading">
                    @include('bakery::bakery_loading.partials.list_loading')
                </div>
            @endif

            @if (auth()->user()->can('bakery_returns'))
                <div class="tab-pane  @if (session('status.tab') == 'returns') active @endif" id="returns">
                    @include('bakery::bakery_loading.partials.returns')
                </div>
            @endif

        </div>


    </section>

@endsection

@section('javascript')
    <script>
        function initializeBakeryLoadingSelects(context) {
            var $context = context ? $(context) : $(document);
            $context.find('.select2').each(function() {
                var $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    minimumResultsForSearch: 0
                });
            });
        }

        function rebuildPendingReturnLoadingOptions() {
            var selectedVehicleId = $('#return_vehicle_id').val();
            var selectedDriverId = $('#return_driver_id').val();
            var selectedLoadingId = $('#return_loading_form_no').val();
            var $loadingSelect = $('#return_loading_form_no');

            $loadingSelect.empty().append('<option value="">{{ __("lang_v1.please_select") }}</option>');

            $.each(window.pendingReturnLoadings || {}, function(loadingId, loadingData) {
                var matchesVehicle = !selectedVehicleId || String(loadingData.vehicle_id) === String(selectedVehicleId);
                var matchesDriver = !selectedDriverId || String(loadingData.driver_id) === String(selectedDriverId);

                if (matchesVehicle && matchesDriver) {
                    var isSelected = String(selectedLoadingId) === String(loadingId) ? ' selected' : '';
                    $loadingSelect.append('<option value="' + loadingId + '"' + isSelected + '>' + loadingData.form_no + '</option>');
                }
            });

            $loadingSelect.trigger('change.select2');
        }

        function populateReturnProductOptions(products, selectedProductId) {
            var $productSelect = $('#return_product_id');

            $productSelect.empty().append('<option value="">{{ __("bakery::lang.select_the_product") }}</option>');

            $.each(products || [], function(index, product) {
                var productId = product.id || product.product_id;
                var productName = product.name;

                if (!productId || !productName) {
                    return;
                }

                var isSelected = String(selectedProductId) === String(productId) ? ' selected' : '';
                $productSelect.append('<option value="' + productId + '"' + isSelected + '>' + productName + '</option>');
            });

            $productSelect.trigger('change.select2');
        }

        function rebuildReturnProductOptions(done) {
            var selectedLoadingId = $('#return_loading_form_no').val();
            var selectedProductId = $('#return_product_id').val();
            var loadingData = selectedLoadingId ? (window.pendingReturnLoadings || {})[selectedLoadingId] : null;

            if (!selectedLoadingId) {
                populateReturnProductOptions([], selectedProductId);
                if (typeof done === 'function') {
                    done();
                }
                return;
            }

            if (loadingData && loadingData.products && loadingData.products.length) {
                populateReturnProductOptions(loadingData.products, selectedProductId);
                if (typeof done === 'function') {
                    done();
                }
                return;
            }

            $.ajax({
                url: '/bakery/get-products-returns',
                dataType: 'json',
                data: {
                    id: selectedLoadingId,
                    length: -1
                },
                success: function(result) {
                    var products = $.map(result.data || [], function(product) {
                        return {
                            id: product.product_id,
                            name: product.name
                        };
                    });

                    if (!window.pendingReturnLoadings) {
                        window.pendingReturnLoadings = {};
                    }

                    if (!window.pendingReturnLoadings[selectedLoadingId]) {
                        window.pendingReturnLoadings[selectedLoadingId] = {};
                    }

                    window.pendingReturnLoadings[selectedLoadingId].products = products;
                    populateReturnProductOptions(products, selectedProductId);
                },
                complete: function() {
                    if (typeof done === 'function') {
                        done();
                    }
                }
            });
        }

        console.log('inside the script');
        //driver tab script
        $(document).on('submit', "#route_add_form", function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var data = form.serialize();

            var submitButton = form.find('.submit-btn');
            submitButton.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg, 'Success');
                        $("#route_id").append('<option value="' + response.data.id + '">' + response
                            .data.route + '</option>').val(response.data.id).trigger('change');
                        $('.modal').modal('hide');
                    } else {
                        toastr.error(response.msg, 'Error');
                    }

                },
                error: function(xhr, status, error) {
                    // Handle error response
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = errors;

                        toastr.error(errorMessage, 'Validation Errors');
                    } else {
                        var error = xhr.responseJSON.message ?? "";
                        if (error == "") {
                            var error = 'Something Went Wrong!, Try again!';
                        }
                        toastr.error(error, 'Error');
                    }
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                }
            });

        });

        $(document).on('submit', "#product_add_form", function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var data = form.serialize();

            var submitButton = form.find('.submit-btn');
            submitButton.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg, 'Success');
                        window.bakeryLoadingProducts[response.data.id] = {
                            name: response.data.name,
                            unit_cost: parseFloat(response.data.unit_cost || 0),
                            created_by: '{{ auth()->user()->username }}'
                        };
                        $("#loading_product_id").append('<option value="' + response.data.id + '">' + response
                            .data.name + '</option>').val(response.data.id).trigger('change');
                        $('.modal').modal('hide');
                    } else {
                        toastr.error(response.msg, 'Error');
                    }

                },
                error: function(xhr, status, error) {
                    // Handle error response
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = errors;

                        toastr.error(errorMessage, 'Validation Errors');
                    } else {
                        var error = xhr.responseJSON.message ?? "";
                        if (error == "") {
                            var error = 'Something Went Wrong!, Try again!';
                        }
                        toastr.error(error, 'Error');
                    }
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                }
            });

        });

        $(document).on('submit', "#driver_add_form", function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var data = form.serialize();

            var submitButton = form.find('.submit-btn');
            submitButton.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg, 'Success');
                        $("#driver_id").append('<option value="' + response.data.id + '">' + response
                            .data.driver_name + '</option>').val(response.data.id).trigger('change');
                        $('.modal').modal('hide');
                    } else {
                        toastr.error(response.msg, 'Error');
                    }

                },
                error: function(xhr, status, error) {
                    // Handle error response
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = errors;

                        toastr.error(errorMessage, 'Validation Errors');
                    } else {
                        var error = xhr.responseJSON.message ?? "";
                        if (error == "") {
                            var error = 'Something Went Wrong!, Try again!';
                        }
                        toastr.error(error, 'Error');
                    }
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                }
            });

        });

        $(document).on('submit', "#fleet_form", function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var data = form.serialize();

            var submitButton = form.find('.submit-btn');
            submitButton.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg, 'Success');
                        $("#vehicle_id").append('<option value="' + response.data.id + '">' + response
                            .data.vehicle_number + '</option>').val(response.data.id).trigger(
                            'change');
                        $('.modal').modal('hide');
                    } else {
                        toastr.error(response.msg, 'Error');
                    }

                },
                error: function(xhr, status, error) {
                    // Handle error response
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = errors;

                        toastr.error(errorMessage, 'Validation Errors');
                    } else {
                        var error = xhr.responseJSON.message ?? "";
                        if (error == "") {
                            var error = 'Something Went Wrong!, Try again!';
                        }
                        toastr.error(error, 'Error');
                    }
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                }
            });

        });

        $(document).ready(function() {
            try {
                initializeBakeryLoadingSelects(document);
                $('#return_loading_date').datepicker('setDate', new Date());
            } catch (e) {
                console.error('Plugin initialization failed:', e);
            }

            $('#date').datepicker('setDate', new Date());
            console.log('Loading form ID sent to server:', $("#form_no").val());

            returns_product_table = $('#returns_product_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '/bakery/get-products-returns',
                    data: function(d) {

                        var return_loading_form_no = $("#return_loading_form_no").val();

                        if (return_loading_form_no) {
                            $(".submit-return, .submit-return-print").prop('disabled', false);
                        } else {
                            $(".submit-return, .submit-return-print").prop('disabled', true);
                            return; // stop further AJAX call if no form selected
                        }

                        console.log('Return loading form no:', return_loading_form_no);
                        d.product_id = $("#return_product_id").val();
                        d.id = return_loading_form_no;

                    }
                },
                @include('layouts.partials.datatable_export_button')
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'unit_cost',
                        name: 'unit_cost',
                        className: 'text-right'
                    },
                    {
                        data: 'qty',
                        name: 'qty',
                        searchable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'total_loaded',
                        name: 'total_loaded',
                        searchable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'returned_qty',
                        name: 'returned_qty',
                        searchable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'returned_qty_amt',
                        name: 'returned_qty_amt',
                        searchable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'due_amount',
                        name: 'due_amount',
                        searchable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'settled_amt',
                        name: 'settled_amt',
                        searchable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'short_amt',
                        name: 'short_amt',
                        searchable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'created_by',
                        name: 'users.username'
                    }
                ],

                fnDrawCallback: function(oSettings) {
                    $(".loading_table_returned_qty").trigger('input');
                },
            });

            rebuildPendingReturnLoadingOptions();
            rebuildReturnProductOptions();
        })

        $(document).on('change', "#return_vehicle_id,#return_driver_id", function() {
            rebuildPendingReturnLoadingOptions();
            rebuildReturnProductOptions(function() {
                returns_product_table.ajax.reload();
            });
        });

        $(document).on('change', "#return_loading_form_no", function() {
            var selectedLoadingId = $(this).val();
            var loadingData = selectedLoadingId ? (window.pendingReturnLoadings || {})[selectedLoadingId] : null;
            if (loadingData && loadingData.date) {
                $('#return_loading_date').val(loadingData.date);
            } else {
                $('#return_loading_date').val('');
            }
            rebuildReturnProductOptions(function() {
                returns_product_table.ajax.reload();
            });
        });

        $(document).on('change', "#return_product_id", function() {
            returns_product_table.ajax.reload();
        })


        $(document).ready(function() {
            function updateLoadingTotals() {
                var grandQty = 0;
                var grandTotal = 0;

                $('#loading_product_table tbody tr').each(function() {
                    var $row = $(this);

                    var unitCost = parseFloat($row.find('.table_unit_cost').val()) || 0;
                    var qty = parseFloat($row.find('input[name="table_qty[]"]').val()) || 0;

                    grandQty += qty;

                    var total = unitCost * qty;
                    grandTotal += total;

                    var qtyPrecision = {{ $quantity_precision }};
                    var currencyPrecision = {{ $currency_precision }};

                    $row.find('.table_total_due').val(total.toFixed(currencyPrecision));
                    $row.find('.table_span_total').text(total.toFixed(currencyPrecision));
                });

                $('.grand_total').text(grandTotal.toFixed({{ $currency_precision }}));
                $('.grand_qty').text(grandQty.toFixed({{ $quantity_precision }}));
                $('.table_total_due_footer').text(grandTotal.toFixed({{ $currency_precision }}));
            }

            function updateEditTotals() {
                var grandQty = 0;
                var grandTotal = 0;

                $('#edit_loading_product_table tbody tr').each(function() {
                    var $row = $(this);

                    var unitCost = parseFloat($row.find('.table_unit_cost').val());
                    var qty = parseFloat($row.find('input[name="table_qty[]"]').val());

                    if (isNaN(qty)) {
                        qty = 0;
                    }

                    grandQty += qty;

                    var total = unitCost * qty;

                    grandTotal += !isNaN(total) ? parseFloat(total) : 0;

                    var qtyPrecision = {{ $quantity_precision }};
                    var currencyPrecision = {{ $currency_precision }};

                    $row.find('.table_total_due').val(total.toFixed(currencyPrecision));
                    $row.find('.table_span_total').text(total.toFixed(currencyPrecision));

                    $('.grand_total').text(grandTotal.toFixed(currencyPrecision));
                    $('.grand_qty').text(grandQty.toFixed(qtyPrecision));

                });
            }

            $(document).on('input', 'input[name="table_qty[]"]', function() {
                updateLoadingTotals();
                updateEditTotals();
            });

            $(document).on('click', '#add_loading_product_btn', function() {
                var productId = $('#loading_product_id').val();
                var qtyValue = $('#issued_qty').val();
                var productData = window.bakeryLoadingProducts[productId];

                if (!productId || !productData) {
                    toastr.error("{{ __('bakery::lang.select_the_product') }}");
                    return;
                }

                var qty = parseFloat(qtyValue);
                if (!qtyValue || isNaN(qty) || qty <= 0) {
                    toastr.error("{{ __('bakery::lang.please_enter_qty') }}");
                    return;
                }

                var currencyPrecision = {{ $currency_precision }};
                var dueAmount = productData.unit_cost * qty;
                var existingRow = $('#loading_product_table tbody').find('tr[data-product-id="' + productId + '"]');

                if (existingRow.length) {
                    existingRow.find('input[name="table_qty[]"]').val(qty.toFixed({{ $quantity_precision }}));
                    existingRow.find('.table_total_due').val(dueAmount.toFixed(currencyPrecision));
                    existingRow.find('.table_span_total').text(dueAmount.toFixed(currencyPrecision));
                } else {
                    var rowHtml = '<tr data-product-id="' + productId + '">' +
                        '<td>' + productData.name +
                        '<input type="hidden" class="table_product_id" name="table_product_id[]" value="' + productId + '">' +
                        '</td>' +
                        '<td class="text-right">' + productData.unit_cost.toFixed(currencyPrecision) +
                        '<input type="hidden" class="table_unit_cost" name="table_unit_cost[]" value="' + productData.unit_cost.toFixed(currencyPrecision) + '">' +
                        '</td>' +
                        '<td class="text-right">' +
                        '<input type="text" name="table_qty[]" class="form-control table_entered_qty text-right" value="' + qty.toFixed({{ $quantity_precision }}) + '">' +
                        '</td>' +
                        '<td class="text-right">' +
                        '<input type="hidden" class="table_total_due form-control" name="table_total_due[]" value="' + dueAmount.toFixed(currencyPrecision) + '">' +
                        '<span class="text-bold table_span_total">' + dueAmount.toFixed(currencyPrecision) + '</span>' +
                        '</td>' +
                        '<td class="text-right">{{ auth()->user()->username }}</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-xs btn-danger remove-loading-row"><i class="fa fa-trash"></i></button></td>' +
                        '</tr>';

                    $('#loading_product_table tbody').append(rowHtml);
                }

                $('#loading_product_id').val(null).trigger('change');
                $('#issued_qty').val('');
                updateLoadingTotals();
            });

            $(document).on('click', '.remove-loading-row', function() {
                $(this).closest('tr').remove();
                updateLoadingTotals();
            });

            $(document).on('click', '#save_and_print_btn, #save_and_print_btn_bottom', function() {
                $('#save_and_print').val('1');
                $('#bakery_loading_form').trigger('submit');
            });

            $(document).on('click', '#bakery_loading_form button[type="submit"]', function() {
                $('#save_and_print').val('');
            });

            $('#bakery_loading_form').on('submit', function() {
                if ($('#loading_product_table tbody tr').length === 0) {
                    toastr.error("{{ __('bakery::lang.you_must_load_atleast_one_product') }}");
                    return false;
                }
            });

            updateLoadingTotals();
            updateEditTotals();
        });


        $(document).ready(function() {
            function updateReturnTotals() {

                var grand_total_qty_returned = 0;
                var grand_total_loaded = 0;
                var grand_total_returned = 0;
                var grand_total_due = 0;
                var grand_total_settled = 0;
                var grand_total_short = 0;

                $('#returns_product_table tbody tr').each(function() {
                    var $row = $(this);

                    var unitCost = parseFloat($row.find('.loading_table_unit_cost').val());
                    var qty_returned = __read_number($row.find('.loading_table_returned_qty'));
                    grand_total_qty_returned += qty_returned;
                    var qty_returned_value = qty_returned * unitCost;
                    grand_total_returned += qty_returned_value;

                    var loaded_amount = __read_number($row.find('.loading_table_loaded_amount'));
                    var total_due = loaded_amount - qty_returned_value
                    grand_total_due += total_due;
                    grand_total_loaded += loaded_amount;

                    var total_settled = __read_number($row.find('.loading_table_settled_amt'));
                    grand_total_settled += total_settled;

                    var total_short = total_due - total_settled;
                    grand_total_short += total_short;




                    $row.find('.loading_table_total_returned').val(qty_returned_value);
                    $row.find('.loading_table_span_total_returned').text(__number_f(qty_returned_value));

                    $row.find('.loading_table_due_amount').val(total_due);
                    $row.find('.loading_table_span_due_amount').text(__number_f(total_due));

                    $row.find('.loading_table_short_amt').val(total_short);
                    $row.find('.loading_table_span_short_amt').text(__number_f(total_short));

                });

                $('.grand_total_qty_returned').text(__number_f(grand_total_qty_returned));
                $('.grand_total_returned').text(__number_f(grand_total_returned));
                $('.grand_total_due').text(__number_f(grand_total_due));
                $('.grand_total_loaded').text(__number_f(grand_total_loaded));
                $('.grand_total_settled').text(__number_f(grand_total_settled));
                $('.grand_total_short').text(__number_f(grand_total_short));
            }

            $(document).on('input', '.loading_table_returned_qty, .loading_table_settled_amt', function() {
                updateReturnTotals();
            });

            $(document).on('click', '.submit-return-print', function() {
                $('#return_save_and_print').val('1');
                $('#bakery_loading_return_form').trigger('submit');
            });

            $(document).on('click', '#bakery_loading_return_form button[type="submit"]', function() {
                $('#return_save_and_print').val('');
            });

            $('#bakery_loading_return_form').on('submit', function() {
                var hasReturnedQty = false;

                $('#returns_product_table tbody tr').each(function() {
                    var qtyReturned = __read_number($(this).find('.loading_table_returned_qty'));
                    if (qtyReturned > 0) {
                        hasReturnedQty = true;
                    }
                });

                if (!hasReturnedQty) {
                    toastr.error("{{ __('bakery::lang.you_must_load_atleast_one_product') }}");
                    return false;
                }
            });

            updateReturnTotals();
        });


        $(document).ready(function() {
            if ($('#list_loading_date_range').length) {
                $('#list_loading_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#list_loading_date_range').val(
                        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                    );
                    if (list_loading_table) list_loading_table.ajax.reload();
                });

                $('#list_loading_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#list_loading_date_range').val('');
                });

                // Set initial date
                $('#list_loading_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
                $('#list_loading_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
            }

            // 2. Initialize DataTable only after daterangepicker exists
            function initListLoadingTable() {
                if (!$.fn.DataTable.isDataTable('#list_loading_table')) {
                    list_loading_table = $('#list_loading_table').DataTable({
                        processing: true,
                        serverSide: true,
                        aaSorting: [
                            [1, 'desc']
                        ],
                        ajax: {
                            url: '{{ action('\Modules\Bakery\Http\Controllers\BakeryLoadingController@index') }}',
                            data: function(d) {
                                var drp = $('input#list_loading_date_range').data('daterangepicker');
                                // fallback if undefined
                                var start = drp ? drp.startDate.format('YYYY-MM-DD') : '';
                                var end = drp ? drp.endDate.format('YYYY-MM-DD') : '';
                                d.start_date = start;
                                d.end_date = end;
                                d.driver_id = $("#list_loading_driver_id").val();
                                d.vehicle_id = $("#list_loading_vehicle_id").val();
                                d.form_no = $("#list_loading_form_no").val();
                                d.route_id = $("#list_loading_route_id").val();
                                d.created_by = $("#list_loading_user_id").val();
                            }
                        },
                        columns: [{
                                data: 'action',
                                searchable: false
                            },
                            {
                                data: 'date',
                                name: 'date'
                            },
                            {
                                data: 'form_no',
                                name: 'form_no'
                            },
                            {
                                data: 'vehicle_number',
                                name: 'bakery_fleets.vehicle_number'
                            },
                            {
                                data: 'driver_name',
                                name: 'bakery_drivers.driver_name'
                            },
                            {
                                data: 'route_name',
                                name: 'bakery_routes.route'
                            },
                            {
                                data: 'total_due_amount',
                                name: 'total_due_amount',
                                searchable: false,
                                className: 'text-right'
                            },
                            {
                                data: 'total_sold_amount',
                                name: 'total_sold_amount',
                                searchable: false,
                                className: 'text-right'
                            },
                            {
                                data: 'total_returned_amount',
                                name: 'total_returned_amount',
                                searchable: false,
                                className: 'text-right'
                            },
                            {
                                data: 'total_short_amount',
                                name: 'total_short_amount',
                                searchable: false,
                                className: 'text-right'
                            },
                            {
                                data: 'username',
                                name: 'users.username'
                            },
                        ],
                    });
                } else {
                    list_loading_table.ajax.reload();
                }
            }

            if ($('#list_loading').hasClass('active')) {
                initListLoadingTable();
            }

            $('a[href="#loading"], a[href="#returns"], a[href="#list_loading"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');
                initializeBakeryLoadingSelects(target);
            });

            $('a[href="#list_loading"]').on('shown.bs.tab', function() {
                initListLoadingTable();
            });

            $('#list_loading_driver_id, #list_loading_vehicle_id, #list_loading_form_no, #list_loading_route_id, #list_loading_user_id').change(function() {
                if (list_loading_table) list_loading_table.ajax.reload();
            });
        });
    </script>

@endsection
