@extends('layouts.app')

@section('title', __('petro::lang.list_other_sales'))

@section('css')
    <style>
        @media print {
            .header-area, .header-area *, .sidebar, .main-sidebar {
                display: none !important;
                visibility: hidden !important;
            }
        }
    </style>
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">

        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="@if (empty($only_pumper)) col-md-3 @else col-md-6 @endif px-4">
                        <div class="form-group">
                            {!! Form::label('pump_operator_filter', __('realtimeentries::lang.pump_operator') . ':') !!}
                            <select class="form-control select2" style="width:100%"
                                id="pump_operators_list_other_sales_operator_id">
                                <option value="">-- All Operators --</option>
                                @foreach ($pump_operators as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="@if (empty($only_pumper)) col-md-3 @else col-md-6 @endif px-4">
                        <div class="form-group">
                            {!! Form::label('shift_id', __('petro::lang.shift') . ':') !!}
                            <select class="form-control select2" style = 'width:100%'
                                id="pump_operators_list_other_sales_shift_id">
                                <option value="">-- All Shifts --</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->id }}">
                                        {{ $shift->name . ' (' . @format_date($shift->shift_date) }}
                                        to {{ !empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['class' => 'box-primary', 'title' => __('petro::lang.list_other_sales')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="pump_operators_list_other_sales_table"
                    style="width: 100%;">
                    <thead>
                        <tr>
                            <th>@lang('petro::lang.action')</th>
                            <th>@lang('petro::lang.time_and_date')</th>
                            <th>@lang('petro::lang.product')</th>
                            <th>@lang('petro::lang.price')</th>
                            <th>@lang('petro::lang.quantity')</th>
                            <th>@lang('petro::lang.balance_stock')</th>
                            <th>@lang('petro::lang.discount')</th>
                            <th>@lang('petro::lang.sub_total')</th>
                            <th>@lang('petro::lang.shift_number')</th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr class="bg-gray font-17 footer-total">
                            <td colspan="7" class="text-right" style="color:brown">
                                <strong>@lang('sale.total'):</strong>
                            </td>
                            <td style="color:brown"><span class="display_currency" id="footer_list_other_sales_amount"
                                    data-currency_symbol="true"></span>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endcomponent
    </section>
    <div class="clearfix"></div>
    <div class="modal fade" id="editQuantityModal" tabindex="-1" role="dialog" aria-labelledby="editQuantityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editQuantityModalLabel">@lang('petro::lang.edit_sale_quantity')</h4>
                </div>
                <div class="modal-body">
                    <form id="editQuantityForm">
                        {{-- Add product name for context --}}
                        <div class="form-group">
                            <strong>@lang('petro::lang.product'):</strong> <span id="modalProductName"></span>
                        </div>
                        <div class="form-group">
                            <strong>@lang('petro::lang.current_quantity'):</strong> <span id="modalCurrentQuantity"></span>
                        </div>
                        <hr>
                        {{-- Input for new quantity --}}
                        <div class="form-group">
                            {!! Form::label('modalNewQuantity', __('petro::lang.new_quantity') . ':*') !!}
                            {!! Form::number('quantity', null, [
                                'class' => 'form-control',
                                'required',
                                'id' => 'modalNewQuantity',
                                'step' => 'any',
                                'placeholder' => __('petro::lang.new_quantity'),
                            ]) !!}
                        </div>
                        {{-- Hidden field to store the sale ID --}}
                        <input type="hidden" id="modalSaleId" name="sale_id">
                        <input type="hidden" id="stock_available" name="stock_available">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="saveQuantityButton">@lang('messages.save')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    <script type="text/javascript">
        // Ensure LANG object is available, or replace LANG.sure directly
        // Example: var LANG = { sure: '@lang('messages.sure')' }; // Place this if LANG isn't globally defined
        // Or directly use: title: '@lang('messages.sure')',

        var body = document.getElementsByTagName("body")[0];
        body.className += " sidebar-collapse";

        $(document).ready(function() {
            // Initialize select2 on filter dropdowns
            $('#pump_operators_list_other_sales_operator_id').select2({ width: '100%', placeholder: '-- All Operators --', allowClear: true });
            $('#pump_operators_list_other_sales_shift_id').select2({ width: '100%', placeholder: '-- All Shifts --', allowClear: true });

            var pump_operators_list_other_sales_table = $('#pump_operators_list_other_sales_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [1, 'desc']
                ], // Changed to sort by the second column (date)
                ajax: {
                    url: "{{ action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@pumpOtherSalesList') }}",
                    data: function(d) {
                        d.pump_operator_id = $('#pump_operators_list_other_sales_operator_id').val();
                        d.shift_id = $('#pump_operators_list_other_sales_shift_id').val();
                        d.print_other_sale_ids = @json($print_other_sale_ids);
                        d.print = {{ $print ? 1 : 0 }};
                    },
                },
                columnDefs: [{
                    "targets": [0], // Action column should not be sortable
                    "orderable": false,
                    "searchable": false,
                    "className": "notexport" // Add class to prevent export/print of action column
                }],
                columns: [{
                        data: 'id', // Use 'id' or 'action' if your controller returns 'action' html directly
                        name: 'id', // Or 'action'
                        width: '8%',
                        render: function(data, type, row) {

                            var deleteUrl = '/petro/pump-operator/delete-other-sale/' + row
                                .id; // Using row.id is safer

                            var html = '<div class="btn-group">';
                            html +=
                                '<button type="button" class="btn btn-xs btn-primary edit-quantity-btn" ' +
                                'data-id="' + row.id + '" ' +
                                'data-product="' + escapeHtml(row.product_name) + '" ' +
                                // Pass product name
                                'data-quantity="' + row.quantity + '">' + // Pass current quantity
                                '<i class="fa fa-edit"></i> @lang('messages.edit')</button>';
                            html +=
                                '<button type="button" class="btn btn-xs btn-danger delete-btn" data-id="' +
                                row.id + '" data-href="' + deleteUrl +
                                '"><i class="fa fa-trash"></i> @lang('messages.delete')</button>';
                            html += '</div>';
                            return html;
                        }
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        width: '10%',
                    },
                    {
                        data: 'product_name',
                        name: 'products.name',
                        width: '10%',
                    },
                    {
                        data: 'price',
                        name: 'price',
                        width: '6%',
                        className: 'text-right'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        width: '3%',
                        className: 'text-right'
                    },
                    {
                        data: 'balance_stock',
                        name: 'balance_stock',
                        width: '6%',
                        className: 'text-right'
                    },
                    {
                        data: 'discount',
                        name: 'discount',
                        width: '3.6%',
                        className: 'text-right'
                    },
                    {
                        data: 'sub_total',
                        name: 'sub_total',
                        width: '4.2%',
                        className: 'text-right'
                    },
                    {
                        data: 'shift_number',
                        name: 'shift_number',
                        width: '3%',
                        className: 'text-right'
                    }
                ],
                fnDrawCallback: function(oSettings) {
                    var footer_list_other_sales_amount = sum_table_col($(
                        '#pump_operators_list_other_sales_table'), 'sub_total');
                    $('#footer_list_other_sales_amount').text(footer_list_other_sales_amount);

                    __currency_convert_recursively($('#pump_operators_list_other_sales_table'));
                },
                buttons: [ // Your buttons definitions...
                    {
                        extend: 'csv',
                        text: '<i class="fa fa-file"></i> Export to CSV',
                        className: 'btn btn-default btn-sm',
                        exportOptions: {
                            columns: ':visible:not(.notexport)' // Exclude columns with 'notexport' class
                        },
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                        className: 'btn btn-default btn-sm',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                    },
                    {
                        extend: 'colvis',
                        text: '<i class="fa fa-columns"></i> Column Visibility',
                        className: 'btn btn-default btn-sm',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                        className: 'btn btn-default btn-sm',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        className: 'btn btn-default btn-sm',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                        customize: function (win) {
                            $(win.document.body).find('.header-area, .sidebar, .main-sidebar, .page-container, .main-content, .notification-area, .user-menu, .clear_cache_btn, a[href*="sms"], a[href*="SMS"], a[href*="helpguide"], a[href*="HelpGuide"], #btnLock, .packages_btn, .fa-lock, [class*="header"], [id*="header"]').remove();
                            $(win.document.body).find('script').remove();
                            
                            $(win.document.body).find('div').each(function() {
                                var text = $(this).text().trim();
                                if (text === '' || text === '()' || text === '.()' || text === ').(' || /^[\.\(\)\s]*$/.test(text)) {
                                    $(this).remove();
                                }
                            });
                            
                            var body = win.document.body;
                            var lastChild = body.lastChild;
                            while (lastChild) {
                                if (lastChild.nodeType === 3 || lastChild.nodeType === 1) {
                                    var text = lastChild.textContent.trim();
                                    if (text === '' || text === '()' || text === '.()' || text === ').(' || /^[\.\(\)\s]*$/.test(text)) {
                                        body.removeChild(lastChild);
                                        lastChild = body.lastChild;
                                    } else {
                                        break;
                                    }
                                } else {
                                    break;
                                }
                            }
                            
                            var cleanStyle = win.document.createElement('style');
                            cleanStyle.setAttribute('type', 'text/css');
                            cleanStyle.innerHTML = `
                                .header-area, .header-area *, .sidebar, .main-sidebar, .page-container, .main-content,
                                .notification-area, .notification-area *, .user-menu, .user-menu *, .clear_cache_btn,
                                a[href*="sms"], a[href*="SMS"], a[href*="helpguide"], a[href*="HelpGuide"], #btnLock,
                                .packages_btn, .fa-lock, [class*="header"], [id*="header"] {
                                    display: none !important;
                                    visibility: hidden !important;
                                }
                                html, body { margin: 0 !important; padding: 10px !important; background: white !important; }
                                h1 { margin: 0 0 10px 0 !important; padding: 0 !important; font-size: 18px !important; }
                                table { width: 100% !important; border-collapse: collapse !important; margin: 0 !important; }
                            `;
                            win.document.head.appendChild(cleanStyle);
                        }
                    }
                ],
                initComplete: function() {
                    if ({{ $print ? 1 : 0 }}) {
                        pump_operators_list_other_sales_table.button('.buttons-print').trigger();
                    }
                }
            });

            function escapeHtml(unsafe) {
                if (typeof unsafe !== 'string') {
                    return unsafe; // Return as is if not a string
                }
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            var loadingShifts = false;
            $('#pump_operators_list_other_sales_operator_id').on('change', function() {
                var operator_id = $(this).val();
                var $shiftSelect = $('#pump_operators_list_other_sales_shift_id');

                $shiftSelect.html('<option value="">-- All Shifts --</option>');
                $shiftSelect.select2({ width: '100%', placeholder: '-- All Shifts --', allowClear: true });

                if (operator_id) {
                    $shiftSelect.html('<option value="">Loading...</option>');
                    $.ajax({
                        url: '{{ route("realtime.get-shifts-by-operator") }}',
                        data: { pump_operator_id: operator_id },
                        success: function(data) {
                            $shiftSelect.empty().append('<option value="">-- All Shifts --</option>');
                            $.each(data, function(key, value) {
                                $shiftSelect.append('<option value="' + key + '">' + value + '</option>');
                            });
                            $shiftSelect.select2({ width: '100%', placeholder: '-- All Shifts --', allowClear: true });
                        }
                    });
                }

                pump_operators_list_other_sales_table.ajax.reload();
            });

            $('#pump_operators_list_other_sales_shift_id').on('change', function() {
                pump_operators_list_other_sales_table.ajax.reload();
            });

            $(document).on('click', '.edit-quantity-btn', function() { // Changed selector
                var button = $(this);
                var id = button.data('id');
                var productName = button.data('product');
                var currentQuantity = button.data('quantity');

                var rowData = $('#pump_operators_list_other_sales_table')
                    .DataTable()
                    .row(button.closest('tr'))
                    .data();
                $('#stock_available').val(rowData.balance_stock);

                // Populate the modal
                $('#modalSaleId').val(id);
                $('#modalProductName').text(productName);
                $('#modalCurrentQuantity').text(currentQuantity);
                $('#modalNewQuantity').val(currentQuantity).focus(); // Pre-fill and focus

                // Show the modal
                $('#editQuantityModal').modal('show');
            });

            // ***** NEW: Save button click handler inside the modal *****
            $(document).on('click', '#saveQuantityButton', function() {
                var button = $(this);
                var form = $('#editQuantityForm');
                var saleId = $('#modalSaleId').val();
                var newQuantity = $('#modalNewQuantity').val();
                var balance_stock = $('#stock_available').val();

                var currentQty = parseFloat($('#modalCurrentQuantity').html());

                var finalValNum = parseFloat(balance_stock);
                var newQuantityNum = parseFloat(newQuantity);
                var allowoverselling = $("#allowoverselling").val();

                if (newQuantityNum > finalValNum && allowoverselling != true) {
                    var stockInt = Math.floor(finalValNum);
                    toastr.error('Quantity exceeded available stock: ' + stockInt);
                    $('#modalNewQuantity').focus();
                    return;
                }

                // Basic validation
                if (!newQuantity || isNaN(newQuantity) || parseFloat(newQuantity) < 0) {
                    toastr.error('@lang('messages.please_enter_valid_quantity')'); // Add this lang key
                    $('#modalNewQuantity').focus();
                    return;
                }

                var url = '/petro/pump-operator/update-other-sale-quantity/' +
                    saleId; // Adjust URL if needed

                button.prop('disabled', true); // Disable button during AJAX

                $.ajax({
                    method: 'POST', // Or PUT if your route is defined as PUT
                    url: url,
                    dataType: 'json',
                    data: {
                        '_token': '{{ csrf_token() }}',
                        // '_method': 'PUT', // Uncomment if using POST request to simulate PUT
                        'quantity': newQuantity
                    },
                    success: function(result) {
                        if (result.success === true || result.success === 1) {
                            toastr.success(result.msg);
                            $('#editQuantityModal').modal('hide'); // Close modal on success
                            pump_operators_list_other_sales_table.ajax.reload(); // Reload table
                        } else {
                            toastr.error(result.msg || 'An error occurred.');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Error: ", textStatus, errorThrown);
                        console.error("Response Text: ", jqXHR.responseText);
                        var errorMsg = 'An error occurred while updating.';
                        if (jqXHR.responseJSON && jqXHR.responseJSON.msg) {
                            errorMsg = jqXHR.responseJSON.msg;
                        } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                            errorMsg = jqXHR.responseJSON.message;
                        }
                        toastr.error(errorMsg);
                    },
                    complete: function() {
                        button.prop('disabled', false); // Re-enable button
                    }
                });
            });


            // ***** CORRECTED DELETE BUTTON HANDLER *****
            $(document).on('click', 'button.delete-btn', function(e) {
                e.preventDefault(); // Prevent default button action
                var button = $(this); // Store the button element
                var href = button.data('href'); // Get the URL from data-href
                var table_to_reload =
                    pump_operators_list_other_sales_table; // Use the correct table variable

                // Ensure LANG.sure is defined or replace directly
                var confirmationTitle = typeof LANG !== 'undefined' && LANG.sure ? LANG.sure :
                    'Are you sure?';

                swal({
                    title: confirmationTitle,
                    text: "This action cannot be undone!", // Optional: Add more descriptive text
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((
                    willDelete
                ) => { // Use arrow function to keep 'this' context if needed, although we stored 'button'
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: href, // Use the href obtained from the button's data attribute
                            dataType: 'json', // Expect a JSON response from the server
                            data: {
                                '_token': '{{ csrf_token() }}' // Include CSRF token
                            },
                            success: function(result) {
                                if (result.success === true || result.success ===
                                    1) { // Check boolean true or integer 1
                                    toastr.success(result.msg);
                                    table_to_reload.ajax
                                        .reload(); // Reload the correct table
                                } else {
                                    toastr.error(result.msg ||
                                        'An error occurred.'
                                    ); // Show error message from server or a default one
                                }
                            },
                            error: function(jqXHR, textStatus,
                                errorThrown) { // Add error handling for debugging
                                console.error("AJAX Error: ", textStatus, errorThrown);
                                console.error("Response Text: ", jqXHR.responseText);
                                var errorMsg = 'An error occurred while deleting.';
                                // Try to get a more specific error message from the response
                                if (jqXHR.responseJSON && jqXHR.responseJSON.msg) {
                                    errorMsg = jqXHR.responseJSON.msg;
                                } else if (jqXHR.responseJSON && jqXHR.responseJSON
                                    .message) {
                                    errorMsg = jqXHR.responseJSON
                                        .message; // Common in Laravel validation errors
                                }
                                toastr.error(errorMsg);
                            }
                        });
                    }
                });
            });

            function tryPrintWithFallback() { // Your existing function...
                let isPopupBlocked = true;
                pump_operators_list_other_sales_table.button('.buttons-print').trigger();
                const checkPopup = setTimeout(() => {
                    if (isPopupBlocked) {
                        console.warn("Print popup blocked, falling back to custom function.");
                        executeCustomPrint();
                    }
                }, 1000);
                window.addEventListener('focus', () => {
                    isPopupBlocked = false;
                    clearTimeout(checkPopup);
                }, {
                    once: true
                });
            }

            function executeCustomPrint() {
                window.print();
            }
            
            window.addEventListener('beforeprint', function() {
                $('.header-area, .sidebar, .main-sidebar').hide();
            });
            
            window.addEventListener('afterprint', function() {
                $('.header-area, .sidebar, .main-sidebar').show();
            });
        });
    </script>
@endsection
