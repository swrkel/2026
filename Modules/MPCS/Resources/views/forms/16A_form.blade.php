@extends('layouts.app')
@section('title', __('mpcs::lang.16A_form'))
@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="page-title-area">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div class="breadcrumbs-area clearfix">
                        <h4 class="page-title pull-left">FORM F16A</h4>
                        <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                            <li><a href="#">F16A</a></li>
                            <li><span>Last Record</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        <!-- @if (auth()->user()->can('f16a_form'))
    <li class="active">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <a href="#16a_form_tab" class="16a_form_tab" data-toggle="tab">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.16A_form')</strong>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </a>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </li>
    @endif -->
                        @if (auth()->user()->can('f16a_form'))
                            <li class="active">
                                <a href="#16A_form_tab" class="16A_form_tab" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.16A_form')</strong>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->can('f16a_form'))
                            <li class="">
                                <a href="#16a_form_list_tab" class="16a_form_tab" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.16A_form_settings')</strong>
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="tab-content">

                        @if (auth()->user()->can('f16a_form'))
                            <div class="tab-pane active" id="16A_form_tab">
                                @include('mpcs::forms.partials.16a_form')
                            </div>
                        @endif
                        @if (auth()->user()->can('16a_form'))
                            <div class="tab-pane" id="16a_form_list_tab">
                                @include('mpcs::forms.partials.list_f16')
                            </div>
                        @endif


                    </div>

                </div>
            </div>
        </div>

    </section>

    <!-- /.content -->

@endsection
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#form-id').hide();
            let previousFormNo = null;
            let previousInvoiceNo = null;
            let previousSupplier = null;
            let previousDateRange = null;

            // Initialize Product filter with Select2 for type & auto search
            $('#16a_product_filter').select2({
                placeholder: '@lang('lang_v1.all')',
                allowClear: true,
                ajax: {
                    url: '/products/list',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data.results, function(item) {
                                return {
                                    id: item.id,
                                    text: item.name
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            // Reload table when product filter changes
            $('#16a_product_filter').on('change', function() {
                form_16a_table.ajax.reload();
            });

            // Improve tab switching performance by using event delegation and caching
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                if ($(e.target).hasClass('16a_form_tab')) {
                    // Only reload if data is stale or first time shown
                    if (typeof form_f22_list_table !== 'undefined' && !$(e.target).data('loaded')) {
                        form_f22_list_table.ajax.reload(null, false); // false = no page reset
                        $(e.target).data('loaded', true);
                    }
                }
            });

            form_f22_list_table = $('#form_f22_list_table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '/mpcs/get-form-f16-list',
                    data: function(d) {
                        let formNo = $('select[id="form_no"] option:selected').text();
                        let invoiceNo = $('select[id="invoice_no"] option:selected').text();
                        let supplierName = $('select[id="supplier"] option:selected').text();
                        let dateRange = $('#form_16a_date_range_list').val();

                        if (formNo !== previousFormNo) {
                            d.form_no = formNo;
                            previousFormNo = formNo;
                        }
                        if (invoiceNo !== previousInvoiceNo) {
                            d.invoice_no = invoiceNo;
                            previousInvoiceNo = invoiceNo;
                        }
                        if (supplierName !== previousSupplier) {
                            d.supplier = supplierName;
                            previousSupplier = supplierName;
                        }
                        if (dateRange !== previousDateRange) {
                            let [start, end] = dateRange.split(' - ');
                            d.start_date = start;
                            d.end_date = end;
                            previousDateRange = dateRange;
                        }
                        return d;
                    }
                },
                columns: [{
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'supplier',
                        name: 'Supplier'
                    },
                    {
                        data: 'form_no',
                        name: 'form_no'
                    },
                    {
                        data: 'invoice_no',
                        name: 'Invoice No'
                    },
                    {
                        data: 'this_form_total',
                        name: 'this_form_total'
                    },
                    {
                        data: 'last_form_total',
                        name: 'last_form_total'
                    },
                    {
                        data: 'grand_total',
                        name: 'grand_total'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    // Call the function to update previous totals after table draw.
                    get_previous_value_16a();
                }
            });

            $(document).ready(function() {
                // 1) Initialize datepickers on the modal fields


                $('#form_16a_date').daterangepicker({
                    singleDatePicker: true, // For selecting a single date
                    showDropdowns: true, // To show the dropdown for predefined date ranges
                    locale: {
                        format: 'YYYY-MM-DD', // Adjust the date format according to your needs
                    },
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Custom Date Range': [moment().startOf('month'), moment().endOf(
                            'month')], // Default custom date range (this can be modified)
                    }
                }, function(start, end, label) {
                    if (label === 'Custom Date Range') {

                        // Show the modal for manual input
                        $('.custom_date_typing_modal').modal('show');
                        // $('.custom_date_typing_modal').modal('show'); // Uncomment if needed
                    } else {
                        // Set the selected date in the input
                        $('#form_16a_date').val(start.format('YYYY-MM-DD'));

                    }

                    // Refresh DataTable with new date
                    form_16a_table.ajax.reload();
                });

                // Reset the field when the cancel button is clicked
                $('#form_16a_date').on('cancel.daterangepicker', function(ev, picker) {
                    $('#form_16a_date').val('');
                });

                // Set the default selected date range when initializing the date picker
                $('#form_16a_date').data('daterangepicker').setStartDate(moment().startOf('day'));
                $(
                    '#form_16a_date').data('daterangepicker').setEndDate(moment().endOf('day'));

                // Display the selected date range on the page
                let date = $('#form_16a_date').val().split(' - ');

                $('.to_date').text(date[1]);
            });


            $('#print_form_16a_btn').on('click', function(e) {
                e.preventDefault();
                let printContent = document.getElementById('printarea').innerHTML;
                let originalContent = document.body.innerHTML;

                document.body.innerHTML = printContent;
                window.print();
                document.body.innerHTML = originalContent;
                // Ensure form ID is correctly fetched
                let formId = $('#form_id').val();

                if (!formId) {
                    // alert('Form ID not found.');
                    //return;
                    //   function printForm() {
                    //     window.print();
                    //}
                }

                $.ajax({
                    url: 'mcps/print-form-f16',
                    type: 'GET',
                    data: {
                        formId: formId
                    },
                    success: function(response) {
                        if (!response || !response.html) {
                            alert('Error: No response received.');
                            return;
                        }

                        let printWindow = window.open('', '_blank');
                        printWindow.document.write(response.html);
                        printWindow.document.close();
                        printWindow.focus();
                        printWindow.print();
                    },

                    error: function(xhr, status, error) {
                        // alert('Print failed: ' + error);
                        //console.log('Print failed:', xhr.responseText);
                        // function printForm() {
                        //   window.print();
                        //}
                    }
                });
            });

            $('#form_no').on('change', function() {
                form_f22_list_table.ajax.reload();
            });

            $('#invoice_no').on('change', function() {
                form_f22_list_table.ajax.reload();
            });

            $('#supplier').on('change', function() {
                form_f22_list_table.ajax.reload();
            });

            $('#form_16a_date_range, #16a_location_id').change(function() {
                if ($('#16a_location_id').val() !== '' && $('#16a_location_id').val() !== undefined) {
                    $('.f16a_location_name').text($('#16a_location_id :selected').text());
                    form_16a_table.ajax.reload();
                } else {
                    $('.f16a_location_name').text('All');
                    form_16a_table.ajax.reload();
                }
            });

            $('#f16_save').click(function(e) {
                e.preventDefault();
                var transactionid = $('#form-index').text();
                var formNumber = $('#form-number').text();
                var formInvoice = $('#form-invoice').text();
                var formSupplier = $('#form-supplier').text();
                var stockNo = $('#new_price_value').val();
                var stockBook = $('#new_price_value2').val();
                var thisbook = $('#this_book').val();
                var prev_book = $('#prev_book').val();
                var grand_book = $('#grand_book').val();
                var thisformtotals = $('#this_form_input').val();
                var prev_formtotals = parseFloat($('#this_form_prevs').val()).toFixed(2);
                var grand_formtotals = parseFloat($('#this_form_grands').val()).toFixed(2);
                console.log(prev_formtotals);
                console.log(grand_formtotals);

                $.ajax({
                    method: 'POST',
                    url: '/mpcs/save-form-f16',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        transaction_id: transactionid,
                        form_number: formNumber,
                        form_invoice: formInvoice,
                        formSupplier: formSupplier,
                        prevformtotal: prev_formtotals,
                        grandformtotal: grand_formtotals,
                        thisformtotal: thisformtotals,
                        stockNo: stockNo,
                        stockBook: stockBook,
                        thisbook: thisbook,
                        prevbook: prev_book,
                        grandbook: grand_book
                        // Include any other data you want to send
                    },
                    success: function(result) {
                        if (result.success == 0) {
                            toastr.error(result.msg);
                            return false;
                        }
                        toastr.success('Record Saved');
                    },
                });
            });
        }); // End of document ready

        // Functions outside document ready are fine
        function get_previous_value_16a() {
            var dateRange = $('#form_16a_date').data('daterangepicker');
            var formattedDate = dateRange ? dateRange.startDate.format('YYYY-MM-DD') : null;
            var location_id = $('#16a_location_id').val();

            if (!formattedDate) return;

            $.ajax({
                method: 'GET',
                url: '/mpcs/get_previous_value_16a',
                data: {
                    start_date: formattedDate,
                    end_date: formattedDate,
                    location_id: location_id
                },
                success: function(result) {
                    $('#pre_F16A_total_purchase_price').text(
                        __number_f(result.pre_total_purchase_price, false, false, __currency_precision)
                    );
                    $('#pre_F16A_total_sale_price').text(
                        __number_f(result.pre_total_sale_price, false, false, __currency_precision)
                    );

                    let footer_total_purchase_price = __read_number($('#total_this_p'));
                    let footer_total_sale_price = __read_number($('#total_this_s'));
                    let grand_total_purchase_price = footer_total_purchase_price + parseFloat(result
                        .pre_total_purchase_price);
                    let grand_total_sale_price = footer_total_sale_price + parseFloat(result
                        .pre_total_sale_price);

                    $('#grand_F16A_total_purchase_price').text(
                        __number_f(grand_total_purchase_price, false, false, __currency_precision)
                    );
                    $('#grand_F16A_total_sale_price').text(
                        __number_f(grand_total_sale_price, false, false, __currency_precision)
                    );
                }
            });
        }

        var form16aInitAttempts = 0;

        function initializeForm16ATable() {
            var $table = $('#form_16a_live_table');

            if (!$table.length) {
                if (form16aInitAttempts < 20) {
                    form16aInitAttempts++;
                    setTimeout(initializeForm16ATable, 250);
                }
                return;
            }

            if ($.fn.DataTable.isDataTable($table[0])) {
                form_16a_table = $table.DataTable();
                return;
            }

            form_16a_table = $table.DataTable({
            processing: true,
            serverSide: true,
            paging: true, // enable pagination
            pageLength: 20, // adjust per requirement
            ajax: {
                url: '/mpcs/get-form-16a',
                data: function(d) {
                    let selectedDate = $('#form_16a_date').val();
                    if (selectedDate) {
                        d.start_date = selectedDate;
                        d.end_date = selectedDate;
                    }
                    d.location_id = $('#16a_location_id').val();
                    d.product_id = $('#16a_product_filter').val(); // Add product filter
                }
            },
            columns: [{
                    data: 'index_no',
                    name: 'index_no'
                },
                {
                    data: 'invoice_no',
                    name: 'invoice_no',
                    defaultContent: '-'
                },
                {
                    data: 'product',
                    name: 'product'
                },
                {
                    data: 'location',
                    name: 'location'
                },
                {
                    data: 'received_qty',
                    name: 'received_qty'
                },
                {
                    data: 'unit_purchase_price',
                    name: 'unit_purchase_price'
                },
                {
                    data: 'total_purchase_price',
                    name: 'total_purchase_price'
                },
                {
                    data: 'unit_sale_price',
                    name: 'unit_sale_price'
                },
                {
                    data: 'total_sale_price',
                    name: 'total_sale_price'
                },
                {
                    data: 'stock_book_no',
                    name: 'stock_book_no'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    defaultContent: ''
                },
            ],
            fnDrawCallback: function(oSettings) {
                // Update totals
                caculateF16AFromTotal();
                get_previous_value_16a();

                // Update form number in header once per page
                let pageInfo = form_16a_table.page.info();
                let currentPage = pageInfo.page + 1; // 1-based
                let displayForm = oSettings.json?.display_form ?? $('#F16a_from_no').val();

                $('#form_no1').text(displayForm);

                // Append page suffix if multiple pages
                if (pageInfo.pages > 1) {
                    $('#form_no1').text(displayForm + '-' + currentPage);
                } else {
                    $('#form_no1').text(displayForm);
                }

                // Optional: update stock_book_no column to match form number for all rows
                $('#form_16a_live_table tbody tr').each(function() {
                    $(this).find('td').eq(9).text($('#form_no1')
                        .text()); // 9 = stock_book_no column index
                });
            }
        });

        function caculateF16AFromTotal() {
            var total_purchase_price =
                @if (optional($setting)->F16A_first_day_after_stock_taking == 1)
                    0
                @else
                    sum_table_col($('#form_16a_live_table'), 'total_purchase_price')
                @endif ;
            $('#footer_F16A_total_purchase_price').text(__number_f(total_purchase_price, false, false,
                __currency_precision));
            var total_sale_price =
                @if (optional($setting)->F16A_first_day_after_stock_taking == 1)
                    0
                @else
                    sum_table_col($('#form_16a_live_table'), 'total_sale_price')
                @endif ;
            $('#footer_F16A_total_sale_price').text(__number_f(total_sale_price, false, false, __currency_precision));
            $('#total_this_p').val(total_purchase_price);
            $('#total_this_s').val(total_sale_price);
        }

        }

        $(document).ready(function() {
            initializeForm16ATable();

            $(document).on('shown.bs.tab', 'a[href="#16a_form_tab"], .16a_form_tab', function() {
                setTimeout(initializeForm16ATable, 50);
            });
        });

    </script>

@endsection
