@extends('layouts.app')
@section('title', __('mpcs::lang.f21_form'))
@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="page-title-area">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div class="breadcrumbs-area clearfix">
                        <h4 class="page-title pull-left">@lang('mpcs::lang.f21_form')</h4>
                        <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                            <li><a href="#">21 Form</a></li>
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
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.f21_form')</strong>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->can('f16a_form'))
                            <!-- <li class="">
                                                                                                                                <a href="#16a_form_list_tab" class="16a_form_tab" data-toggle="tab">
                                                                                                                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.16A_form_settings')</strong>
                                                                                                                                </a>
                                                                                                                            </li> -->
                        @endif
                    </ul>
                    <div class="tab-content">

                        @if (auth()->user()->can('f16a_form'))
                            <div class="tab-pane active" id="16A_form_tab">
                                @include('mpcs::forms.21Form.21_form')
                            </div>
                        @endif
                        @if (auth()->user()->can('16a_form'))
                            <div class="tab-pane" id="16a_form_list_tab">

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
        const f21SearchableFilterSelector = [
            '#form_21_location_id',
            '#form_21_category_id',
            '#form_21_sub_category_id',
            '#form_21_product_id',
            '#f21_transaction_type'
        ].join(', ');

        function initializeF21SearchableFilters() {
            $(f21SearchableFilterSelector).each(function() {
                const $filter = $(this);
                const selectedValue = $filter.val();

                if ($filter.hasClass('select2-hidden-accessible')) {
                    $filter.select2('destroy');
                }

                $filter.select2({
                    width: '100%',
                    minimumResultsForSearch: 0,
                    allowClear: $filter.find('option[value=""]').length > 0
                });

                if (selectedValue !== null && selectedValue !== undefined) {
                    $filter.val(selectedValue).trigger('change.select2');
                }
            });
        }

        $(document)
            .off('select2:open.f21SearchableFilters')
            .on('select2:open.f21SearchableFilters', function() {
                window.setTimeout(function() {
                    $('.select2-container--open .select2-search__field').last().trigger('focus');
                }, 0);
            });

        $(document).ready(function() {
            initializeF21SearchableFilters();

            if ($('#form_21_location_id option').length === 1 ||
                ($('#form_21_location_id option').length === 2 && $('#form_21_location_id').val() !== '')) {
                $('#form_21_location_id').trigger('change');
            }
        });
        
        const f21FormNumberUrl = @json(action('\Modules\MPCS\Http\Controllers\F21FormController@getF21FormNumber'));
        let lastKnownFormNumber = $('#formnumber').val() || '';
        let pendingFormNumber = lastKnownFormNumber;

        function setFormNumberDisplay(value) {
            const hasValue = value !== null && value !== undefined && value !== '';
            const displayValue = hasValue ? value : '-';
            $('#form_no').text(displayValue);
            $('#formnumber').val(hasValue ? value : '');
        }

        function refreshFormNumber(startMoment) {
            if (!f21FormNumberUrl || !startMoment) {
                return;
            }

            $.get(f21FormNumberUrl, {
                    date: startMoment.format('YYYY-MM-DD'),
                    location_id: $('#form_21_location_id').val()
                })
                .done(function(response) {
                    const formNumber = response.form_number;
                    const hasValue = formNumber !== null && formNumber !== undefined && formNumber !== '';
                    pendingFormNumber = hasValue ? formNumber : '';
                    setFormNumberDisplay(formNumber);
                })
                .fail(function() {
                    // Preserve existing number if the request fails
                });
        }

        // Fixed setF21DateDisplay function
        function setF21DateDisplay(date) {
            if (date && date.isValid && date.isValid()) {
                const formatted = date.format('YYYY-MM-DD');
                $('#form_21_date_range').val(formatted);
                $('#f21_date_from').text(formatted);
                // $('#f21_date_to').text(formatted);
                refreshFormNumber(date);
            } else {
                $('#form_21_date_range').val('');
                $('#f21_date_from').text('');
                // $('#f21_date_to').text('');
            }
            
            // Always reload table when date changes
            if (typeof form_f21_list_table !== 'undefined' && form_f21_list_table) {
                form_f21_list_table.ajax.reload();
            }
        }

        // Initialize date picker like F16 version
        if ($('#form_21_date_range').length === 1) {
            var form21DatePicker = $('#form_21_date_range').daterangepicker({
                singleDatePicker: false,
                showDropdowns: true,
                showCustomRangeLabel: true,
                alwaysShowCalendars: true,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    customRangeLabel: 'Custom Range'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Custom Date Range': [moment().startOf('month'), moment().endOf('month')],
                }
            }, function(start, end, label) {
                // if (label === 'Custom Range' || label === 'Custom Date Range') {
                //     $('.custom_date_typing_modal').modal('show');
                //     return;
                // }

                var formattedStartDate = start.format('YYYY-MM-DD');
                var formattedEndDate = end.format('YYYY-MM-DD');
                var displayValue = formattedStartDate === formattedEndDate
                    ? formattedStartDate
                    : formattedStartDate + ' - ' + formattedEndDate;
                $('#form_21_date_range').val(displayValue);
                $('#form_21_date_range').data('f21-prev-date', formattedStartDate);
                $('#f21_date_from').text(formattedStartDate);
                $('#f21_date_to').text(formattedEndDate);
                refreshFormNumber(start);

                if (typeof form_f21_list_table !== 'undefined' && form_f21_list_table) {
                    form_f21_list_table.ajax.reload();
                }
            });

            // Handle apply event
            $('#form_21_date_range').on('apply.daterangepicker', function(ev, picker) {
                // if (picker.chosenLabel === 'Custom Range' || picker.chosenLabel === 'Custom Date Range') {
                //     $('.custom_date_typing_modal').modal('show');
                //     return;
                // }

                var formattedStartDate = picker.startDate.format('YYYY-MM-DD');
                var formattedEndDate = picker.endDate.format('YYYY-MM-DD');
                var displayValue = formattedStartDate === formattedEndDate
                    ? formattedStartDate
                    : formattedStartDate + ' - ' + formattedEndDate;
                $('#form_21_date_range').val(displayValue);
                $('#form_21_date_range').data('f21-prev-date', formattedStartDate);

                // Update displays
                $('#f21_date_from').text(formattedStartDate);
                $('#f21_date_to').text(formattedEndDate);
                
                // Refresh form number
                refreshFormNumber(picker.startDate);
                
                // Reload the DataTable
                if (typeof form_f21_list_table !== 'undefined' && form_f21_list_table) {
                    form_f21_list_table.ajax.reload();
                }
            });

            // Handle cancel/clear event
            $('#form_21_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#form_21_date_range').val('');
                $('#form_21_date_range').removeData('f21-prev-date');
                $('#f21_date_from').text('');
                $('#f21_date_to').text('');
                
                // Reload table with cleared date filter
                if (typeof form_f21_list_table !== 'undefined' && form_f21_list_table) {
                    form_f21_list_table.ajax.reload();
                }
            });

            // Handle custom date modal apply
            $('#custom_date_apply_button').on('click', function() {
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + 
                                $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + 
                                $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + 
                                $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + 
                              $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + 
                              $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + 
                              $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate, 'YYYY-MM-DD');
                    let formattedEndDate = moment(endDate, 'YYYY-MM-DD');

                    if (formattedStartDate.isValid() && formattedEndDate.isValid()) {
                        let displayValue = formattedStartDate.format('YYYY-MM-DD') === formattedEndDate.format('YYYY-MM-DD')
                            ? formattedStartDate.format('YYYY-MM-DD')
                            : formattedStartDate.format('YYYY-MM-DD') + ' - ' + formattedEndDate.format('YYYY-MM-DD');

                        $('#form_21_date_range').val(displayValue);
                        $('#form_21_date_range').data('f21-prev-date', formattedStartDate.format('YYYY-MM-DD'));
                        $('#f21_date_from').text(formattedStartDate.format('YYYY-MM-DD'));
                        $('#f21_date_to').text(formattedEndDate.format('YYYY-MM-DD'));

                        if (form21DatePicker.data('daterangepicker')) {
                            form21DatePicker.data('daterangepicker').setStartDate(formattedStartDate);
                            form21DatePicker.data('daterangepicker').setEndDate(formattedEndDate);
                        }

                        refreshFormNumber(formattedStartDate);
                        
                        if (form_f21_list_table) {
                            form_f21_list_table.ajax.reload();
                        }
                    }
                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select a valid date.");
                }
            });

            // Set default date to today
            const today21 = moment();
            const todayFormatted = today21.format('YYYY-MM-DD');
            $('#form_21_date_range').val(todayFormatted);
            $('#form_21_date_range').data('f21-prev-date', todayFormatted);
            $('#f21_date_from').text(todayFormatted);
            $('#f21_date_to').text(todayFormatted);
            
            // Initialize the date picker with today's date
            if (form21DatePicker.data('daterangepicker')) {
                form21DatePicker.data('daterangepicker').setStartDate(today21);
            }
            
            // Trigger initial form number refresh
            refreshFormNumber(today21);
        }

        /*
         * IS2200 #3: keep the F21 Date Range usable even when a later global
         * layout/date-default script touches generic date-range inputs.
         *
         * - Recreate the picker only if another script removed its instance.
         * - Restore today's value only when the field was cleared.
         * - Explicitly show the picker on click/focus so a readonly input can
         *   always be selected with the mouse.
         * - Run once after window load, after all shared layout scripts.
         */
        function ensureF21DateRangePicker() {
            var $input = $('#form_21_date_range');
            if (!$input.length || !$.fn.daterangepicker) {
                return;
            }

            if (!$input.data('daterangepicker')) {
                $input.daterangepicker({
                    singleDatePicker: false,
                    showDropdowns: true,
                    showCustomRangeLabel: true,
                    alwaysShowCalendars: true,
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        customRangeLabel: 'Custom Range'
                    },
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Custom Date Range': [moment().startOf('month'), moment().endOf('month')]
                    }
                });
            }

            var picker = $input.data('daterangepicker');
            if (!$input.val()) {
                var today = moment();
                var todayValue = today.format('YYYY-MM-DD');
                $input.val(todayValue).data('f21-prev-date', todayValue);
                $('#f21_date_from, #f21_date_to').text(todayValue);
                if (picker) {
                    picker.setStartDate(today);
                    picker.setEndDate(today);
                }
            }
        }

        $(document)
            .off('click.f21DateRange focus.f21DateRange', '#form_21_date_range')
            .on('click.f21DateRange focus.f21DateRange', '#form_21_date_range', function() {
                ensureF21DateRangePicker();
                var picker = $(this).data('daterangepicker');
                if (picker) {
                    picker.show();
                }
            });

        $(window).off('load.f21DateRange').on('load.f21DateRange', function() {
            window.setTimeout(ensureF21DateRangePicker, 500);
        });

        var form_f21_list_table;
        const initialProducts = @json($products);
        const initialSubCategories = @json($sub_categories);

        function loadDataTable(transactionType) {
            const pageLength = {{ !empty($settings->F21_no_of_product_per_page) ? $settings->F21_no_of_product_per_page : 25 }};
            const limits = [5, 10, 25, 50, 100, 200, 500, 1000, pageLength];
            let lengthMenu = [...new Set(limits)];
            lengthMenu = lengthMenu.sort((a, b) => a - b);
            pendingFormNumber = lastKnownFormNumber;

            form_f21_list_table = $('#form_f21_list_table').DataTable({
                processing: true,
                serverSide: true,
                destroy: true,
                pageLength: pageLength,
                lengthMenu: [...lengthMenu, 'All'],
                ajax: {
                    url: getAjaxUrl(transactionType),
                    dataSrc: function(json) {
                        return json && Array.isArray(json.data) ? json.data : [];
                    },
                    data: function(d) {
                        // Get date from input value (may be single date or range)
                        var dateValue = $('#form_21_date_range').val();

                        if (dateValue && dateValue !== '') {
                            var parts = dateValue.split(' - ');
                            if (parts.length === 2) {
                                var startDate = moment(parts[0].trim(), 'YYYY-MM-DD');
                                var endDate = moment(parts[1].trim(), 'YYYY-MM-DD');
                                if (startDate.isValid() && endDate.isValid()) {
                                    d.start_date = startDate.format('YYYY-MM-DD');
                                    d.end_date = endDate.format('YYYY-MM-DD');
                                }
                            } else {
                                var selectedDate = moment(dateValue, 'YYYY-MM-DD');
                                if (selectedDate.isValid()) {
                                    d.start_date = selectedDate.format('YYYY-MM-DD');
                                    d.end_date = selectedDate.format('YYYY-MM-DD');
                                }
                            }
                        }
                        
                        d.is_direct_sale = 0;
                        d.location_id = $('#form_21_location_id').val();
                        d.category_id = $('#form_21_category_id').val();
                        d.sub_category_id = $('#form_21_sub_category_id').val();
                        d.product_id = $('#form_21_product_id').val();
                    },
                    error: function(xhr) {
                        console.error('F21 details load failed:', xhr.responseText || xhr.statusText);
                        toastr.error('Unable to load F21 related details. Please retry.');
                    }
                },
                columns: [
                    { data: 'transaction_date', name: 'date' },
                    { data: 'book_no', name: 'book_no' },
                    { data: 'transaction_type', name: 'transaction_type' },
                    { data: 'product_code', name: 'product_code' },
                    { data: 'product_name', name: 'product_name' },
                    { data: 'starting_qty', name: 'starting_qty' },
                    { data: 'received_qty', name: 'received_qty' },
                    { data: 'sold_qty', name: 'sold_qty' },
                    { data: 'balance_qty', name: 'balance_qty' }
                ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();

                    var intVal = function(i) {
                        return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : 
                               typeof i === 'number' ? i : 0;
                    };

                    var totalReceived = api.column(6).data().reduce(function(a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    var totalSold = api.column(7).data().reduce(function(a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    $(api.column(6).footer()).html(__number_f(totalReceived));
                    $(api.column(7).footer()).html(__number_f(totalSold));
                }
            });

            $('#form_f21_list_table').off('xhr.dt').on('xhr.dt', function(e, settings, json) {
                json = json || {};
                if (json.calculated_form_number) {
                    $('#form_no').text(json.calculated_form_number);
                }
                const rows = (json && json.data) ? json.data : [];
                if (rows.length > 0) {
                    let resolvedNumber = rows[0].form_number;
                    if (resolvedNumber === null || resolvedNumber === undefined || resolvedNumber === '') {
                        resolvedNumber = pendingFormNumber || lastKnownFormNumber;
                    }
                    if (resolvedNumber !== null && resolvedNumber !== undefined && resolvedNumber !== '') {
                        lastKnownFormNumber = resolvedNumber;
                    }
                    setFormNumberDisplay(resolvedNumber !== null && resolvedNumber !== undefined &&
                        resolvedNumber !== '' ? resolvedNumber : lastKnownFormNumber);
                } else {
                    setFormNumberDisplay(lastKnownFormNumber);
                }
            });
        }

        $(document).on('change', '#form_21_location_id', function() {
            var dateValue = $('#form_21_date_range').val();
            if (dateValue && dateValue !== '') {
                var selectedDate = moment(dateValue, 'YYYY-MM-DD');
                if (selectedDate.isValid()) {
                    refreshFormNumber(selectedDate);
                }
            }
            if (form_f21_list_table) {
                form_f21_list_table.ajax.reload();
            }
        });

        function getAjaxUrl(transactionType) {
            if (transactionType === '' || transactionType === null || transactionType === undefined) {
                return "/mpcs/get-all-f21-transactions";
            }
            switch (parseInt(transactionType)) {
                case 0:
                    return "/mpcs/getpos";
                case 1:
                    return "/mpcs/get-settlement";
                case 2:
                    return "/mpcs/get-purchase-order";
                case 3:
                    return "/mpcs/get-sales-return";
                case 4:
                    return "/mpcs/get-purchase-return";
                default:
                    return "/mpcs/get-all-f21-transactions";
            }
        }

        $(document).off('change',
            '#f21_transaction_type, #form_21_date_range, #form_21_location_id, #form_21_category_id, #form_21_sub_category_id, #form_21_product_id'
        );

        $(document).on('change',
            '#f21_transaction_type, #form_21_date_range, #form_21_location_id, #form_21_category_id, #form_21_sub_category_id, #form_21_product_id',
            function() {
                var transactionType = $('#f21_transaction_type').val();

                if ($(this).attr('id') === 'f21_transaction_type') {
                    loadDataTable(transactionType);
                } else {
                    if (form_f21_list_table) {
                        form_f21_list_table.ajax.reload();
                    }
                }
            });

        $(document).ready(function() {
            const initialTransactionType = $('#f21_transaction_type').val() || '';
            loadDataTable(initialTransactionType);

            function populateProducts(productMap) {
                const productSelect = $('#form_21_product_id');
                productSelect.empty().append(new Option("@lang('lang_v1.all')", ''));
                if (productMap && Object.keys(productMap).length > 0) {
                    Object.keys(productMap).forEach(function(id) {
                        productSelect.append(new Option(productMap[id], id));
                    });
                }
                productSelect.trigger('change.select2');
            }

            function fetchProductsBySubcategories(subcategoryIds) {
                if (!subcategoryIds || subcategoryIds.length === 0) {
                    $.ajax({
                        url: '/mpcs/get-products-by-category',
                        data: {},
                        success: function(response) {
                            const productMap = {};
                            if (response.products && response.products.length) {
                                response.products.forEach(function(prod) {
                                    productMap[prod.id] = prod.name;
                                });
                            }
                            populateProducts(productMap);
                        },
                        error: function() {
                            populateProducts(initialProducts);
                        }
                    });
                    return;
                }
                $.ajax({
                    url: '/mpcs/get-products-by-category',
                    data: {
                        category_ids: subcategoryIds
                    },
                    success: function(response) {
                        const productMap = {};
                        if (response.products && response.products.length) {
                            response.products.forEach(function(prod) {
                                productMap[prod.id] = prod.name;
                            });
                        }
                        populateProducts(productMap);
                    },
                    error: function() {
                        populateProducts(initialProducts);
                    }
                });
            }

            function populateSubCategories(categoryId, callback) {
                const subCategorySelect = $('#form_21_sub_category_id');
                subCategorySelect.empty().append(new Option("@lang('lang_v1.all')", ''));
                $.ajax({
                    url: '/mpcs/get-sub-categories',
                    data: {
                        category_id: categoryId
                    },
                    success: function(response) {
                        const ids = [];
                        if (response.sub_categories) {
                            Object.keys(response.sub_categories).forEach(function(id) {
                                subCategorySelect.append(new Option(response.sub_categories[id], id));
                                ids.push(id);
                            });
                        }
                        subCategorySelect.trigger('change.select2');
                        if (typeof callback === 'function') {
                            callback(ids);
                        }
                    },
                    error: function() {
                        if (typeof callback === 'function') {
                            callback([]);
                        }
                    }
                });
            }

            $('#form_21_category_id').on('change', function() {
                const categoryId = $(this).val();
                $('#form_21_sub_category_id').val(null).trigger('change');
                if (categoryId) {
                    populateSubCategories(categoryId, function(subcategoryIds) {
                        fetchProductsBySubcategories(subcategoryIds);
                    });
                } else {
                    populateSubCategories(null);
                    populateProducts(initialProducts);
                }
                if (form_f21_list_table) {
                    form_f21_list_table.ajax.reload();
                }
            });

            $('#form_21_sub_category_id').on('change', function() {
                const subCategoryId = $(this).val();
                if (subCategoryId) {
                    fetchProductsBySubcategories([subCategoryId]);
                } else {
                    const categoryId = $('#form_21_category_id').val();
                    if (categoryId) {
                        populateSubCategories(categoryId, function(subcategoryIds) {
                            fetchProductsBySubcategories(subcategoryIds);
                        });
                    } else {
                        populateProducts(initialProducts);
                    }
                }
                if (form_f21_list_table) {
                    form_f21_list_table.ajax.reload();
                }
            });

            // ensure initial product list is populated
            if (initialProducts && Object.keys(initialProducts).length > 0) {
                populateProducts(initialProducts);
            } else {
                fetchProductsBySubcategories([]);
            }
            
            // prepopulate subcategories if available from server
            if (initialSubCategories && Object.keys(initialSubCategories).length > 0) {
                const subCategorySelect = $('#form_21_sub_category_id');
                Object.keys(initialSubCategories).forEach(function(id) {
                    subCategorySelect.append(new Option(initialSubCategories[id], id));
                });
                subCategorySelect.trigger('change.select2');
            } else {
                populateSubCategories(null);
            }
        });

        $(document).on('click', '#f21_print', function() {
            var selectedDate = $('#form_21_date_range').val();
            var location = $('#form_21_location_id option:selected').text();
            var product = $('#form_21_product_id option:selected').text();
            var transactionType = $('#f21_transaction_type option:selected').text();
            var formNumber = $('#formnumber').val();

            var tableData = form_f21_list_table.rows({
                search: "applied"
            }).data();

            var printWindow = window.open("", "_blank");
            var printContent = `
        <html>
        <head>
            <title></title>
            <style>
                @page {
                    margin: 0;
                }
                html, body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #fff;
                }
                body { font-family: Arial, sans-serif; }
                .print-wrap { margin: 12mm; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                h2 { text-align: center; }
                .print-header { width: 100%; margin-bottom: 15px; }
                .print-header h5 { margin: 5px 0; font-weight: bold; }
                .text-red { color: red; }
                .text-center { text-align: center; }
            </style>
        </head>
        <body>
            <div class="print-wrap">
            <h2>Transaction Report : ${transactionType}</h2>
            <table width="100%" style="margin-bottom: 15px;">
                <tr class="header-row">
                    <td><strong>Date:</strong> ${selectedDate}</td>
                    <td><strong>Location:</strong> ${location}</td>
                    <td><strong>Product:</strong> ${product}</td>
                    <td><strong>Transaction Type:</strong> ${transactionType}</td>
                </tr>
            </table>

            <div class="print-header">
                <table width="100%">
                    <tr>
                        <td class="text-center"><h5>Filling Station: _________________</h5></td>
                        <td class="text-center"><h5>Date: ${selectedDate}</h5></td>
                        <td class="text-center"><h5>Form No: ${formNumber}</h5></td>
                    </tr>
                </table>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Book No</th>
                        <th>Transaction Type</th>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Starting Qty</th>
                        <th>Received Qty</th>
                        <th>Sold Qty</th>
                        <th>Balance Qty</th>
                    </tr>
                </thead>
                <tbody>`;

            tableData.each(function(row) {
                printContent += `
                    <tr>
                        <td>${row.transaction_date}</td>
                        <td>${row.book_no}</td>
                        <td>${row.transaction_type}</td>
                        <td>${row.product_code}</td>
                        <td>${row.product_name}</td>
                        <td>${row.starting_qty}</td>
                        <td>${row.received_qty}</td>
                        <td>${row.sold_qty}</td>
                        <td>${row.balance_qty}</td>
                    </tr>`;
            });

            printContent += `
                </tbody>
            </table>
            </div>

        <script>    
            window.print();
            window.onafterprint = function() { window.close(); }
        <\/script>    
        </body>
        </html>`;

            printWindow.document.open();
            printWindow.document.write(printContent);
            printWindow.document.close();
        });
    </script>
@endsection
