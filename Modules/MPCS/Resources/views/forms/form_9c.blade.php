@extends('layouts.app')
@section('title', __('mpcs::lang.9c_cash_form'))

@section('content')
    <style>
        .f9c-selected-date-display {
            clear: both;
            width: 100%;
            margin: 8px 0 14px;
            padding: 8px 12px;
            text-align: center;
            color: #ff1f1f;
            font-size: 20px;
            font-weight: 500;
            line-height: 1.35;
        }

        @media (max-width: 768px) {
            .f9c-selected-date-display {
                font-size: 16px;
                padding: 7px 8px;
            }
        }

        @media print {
            .f9c-selected-date-display {
                color: #000 !important;
                font-size: 11pt;
                margin: 3px 0 6px;
                padding: 0;
            }
        }
    </style>
    <!-- Main content -->
    <section class="content-header main-content-inner" style="padding-top:0px">
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs" data-mpcs-tabs data-auto-permission-module="mpcs">
                    <ul class="nav nav-tabs">
                        @if (auth()->user()->can('f9a_form'))
                            <li class="active">
                                <a href="#f9c_cash_form_tab" class="f9c_cash_form_link" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.form_9_c_form_detail')</strong>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->can(abilities: 'f9a_settings_form'))
                            <li class="">
                                <a href="#f9c_cash_settings_tab" class="f9c_cash_settings_link" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.form_9_c_settings')</strong>
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="tab-content">
                        @if (auth()->user()->can('f9a_form'))
                            <div class="tab-pane active" id="f9c_cash_form_tab">
                                @include('mpcs::forms.partials.9c_form')
                            </div>
                        @endif
                        @if (auth()->user()->can('f9a_settings_form'))
                            <div class="tab-pane" id="f9c_cash_settings_tab">
                                @include('mpcs::forms.partials.9c_settings_form')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade form_9_c_settings_modal" id="form_9_c_settings_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade update_form_9_c_settings_modal" id="update_form_9_c_settings_modal" tabindex="-1"
            role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>
    <!-- /.content -->

@endsection
@section('javascript')
    @include('mpcs::partials.safe_tabs')

    <script type="text/javascript">
        $(document).ready(function() {
            function activateReportTab(targetId) {
                if (!targetId || !document.getElementById(targetId)) return;
                $('.settlement_tabs .nav-tabs li').removeClass('active');
                $('.settlement_tabs .nav-tabs a').each(function() {
                    if ($(this).attr('href') === '#' + targetId) $(this).closest('li').addClass('active');
                });
                $('.settlement_tabs .tab-content > .tab-pane').removeClass('active in').hide();
                $('#' + targetId).addClass('active in').show();
            }

            $(document).on('click', '.settlement_tabs .nav-tabs a[data-toggle="tab"]', function(event) {
                var targetId = String($(this).attr('href') || '').replace(/^#/, '');
                if (!targetId || !document.getElementById(targetId)) return;
                event.preventDefault();
                activateReportTab(targetId);
                $(this).trigger('mpcs.tab.shown');
            });
            // Use Business Settings / Currency precision for Rs. column decimals (override after common.js)
            __currency_precision = {{ $currency_precision ?? 2 }};
            __quantity_precision = {{ $quantity_precision ?? 2 }};

            function parseF9CSelectedDatePart(value) {
                const raw = String(value || '').trim();
                if (!raw) return null;

                const parsed = moment(raw, [moment_date_format, 'YYYY-MM-DD', 'DD/MM/YYYY', 'MM/DD/YYYY'], true);
                if (parsed.isValid()) return parsed;

                const fallback = moment(raw);
                return fallback.isValid() ? fallback : null;
            }

            function formatF9CSelectedDate(value) {
                const raw = String(value || '').trim();
                if (!raw) return '';

                let parts = [raw];
                if (raw.indexOf(' ~ ') !== -1) {
                    parts = raw.split(' ~ ');
                } else if (raw.indexOf(' - ') !== -1) {
                    parts = raw.split(' - ');
                }

                const start = parseF9CSelectedDatePart(parts[0]);
                const end = parts.length > 1 ? parseF9CSelectedDatePart(parts[1]) : start;

                if (!start) return 'Date: ' + raw;

                const startText = start.format('D MMMM YYYY');
                if (end && !start.isSame(end, 'day')) {
                    return 'Date: From ' + startText + ' to ' + end.format('D MMMM YYYY');
                }

                return 'Date: ' + startText;
            }

            function updateF9CCashSelectedDateDisplay() {
                const $wrapper = $('#form_9ccash_table_wrapper');
                if (!$wrapper.length) return;

                let $display = $('#f9c_cash_selected_date_display');
                if (!$display.length) {
                    $display = $('<div>', {
                        id: 'f9c_cash_selected_date_display',
                        class: 'f9c-selected-date-display',
                        'aria-live': 'polite'
                    });

                    let $tableTarget = $wrapper.find('.dataTables_scroll').first();
                    if (!$tableTarget.length) {
                        $tableTarget = $wrapper.find('#form_9ccash_table').first();
                    }

                    if ($tableTarget.length) {
                        $display.insertBefore($tableTarget);
                    } else {
                        $wrapper.append($display);
                    }
                }

                const text = formatF9CSelectedDate($('#9c_date_range').val());
                $display.text(text).toggle(Boolean(text));
            }

            // Initialize the date picker
            $('#9c_date_range').daterangepicker({
                singleDatePicker: true, // For selecting a single date
                showDropdowns: true, // To show the dropdown for predefined date ranges
                locale: {
                    format: 'YYYY-MM-DD', // Adjust the date format according to your needs
                },
                ranges: {
                    /*
                     * IS2014: the picker offered only Today, Yesterday and the
                     * two custom entries. The ticket asks for the system standard
                     * selection - the full set shown on the other list screens.
                     *
                     * The labels and definitions below match the standard range
                     * list used elsewhere in the application, so the F9C screens
                     * behave the same way as the rest of the system.
                     *
                     * NOTE: this picker runs with singleDatePicker: true, so a
                     * multi-day range collapses to its START date - the callback
                     * below stores start only. The multi-day entries are still
                     * useful as quick jumps (This Month selects the 1st, This Year
                     * selects 1 January), which is how the single-date screens in
                     * the rest of the app behave too.
                     */
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [
                        moment().subtract(1, 'month').startOf('month'),
                        moment().subtract(1, 'month').endOf('month')
                    ],
                    'This month last year': [
                        moment().subtract(1, 'year').startOf('month'),
                        moment().subtract(1, 'year').endOf('month')
                    ],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                    'Last Year': [
                        moment().subtract(1, 'year').startOf('year'),
                        moment().subtract(1, 'year').endOf('year')
                    ],
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
                    $('#9c_date_range').val(start.format('YYYY-MM-DD'));
                    updateF9CCashSelectedDateDisplay();

                    // Refresh DataTable with new date
                    resetF9CCashReportState();
                    form_9ccash_table.ajax.reload(null, true);
                }
            });

            // Reset the field when the cancel button is clicked
            $('#9c_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#9c_date_range').val('');
                updateF9CCashSelectedDateDisplay();
            });

            // Set the default selected date range when initializing the date picker
            $('#9c_date_range').data('daterangepicker').setStartDate(moment().startOf('day'));
            $(
                '#9c_date_range').data('daterangepicker').setEndDate(moment().endOf('day'));

            // Display the selected date range on the page
            let date = $('#9c_date_range').val().split(' - ');

            $('.to_date').text(date[1]);

            $('#9c_date_range').on('change.f9cSelectedDateDisplay', updateF9CCashSelectedDateDisplay);

            // $('#9c_date_range').change(function() {                
            //       console.log("eccce");
            //     form_9ccash_table.ajax.reload(null, true);

            // });
            $('#custom_date_apply_button').on('click', function() {
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                    '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                    '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                    '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                    '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                    '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                    '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);
                    let fullRange = formattedStartDate + ' ~ ' + formattedEndDate;

                    // === Update #9c_date_range if it exists ===
                    if ($('#9c_date_range').length) {
                        $('#9c_date_range').val(fullRange);
                        $('#9c_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#9c_date_range').data('daterangepicker').setEndDate(moment(endDate));
                        updateF9CCashSelectedDateDisplay();
                        resetF9CCashReportState();
                        form_9ccash_table.ajax.reload(null, true);
                    }
                    // Hide the modal
                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            });

            $("#print_div").click(function() {
                printDiv();
            });

            function printDiv() {
                const table = document.getElementById('form_9ccash_table');
                if (!table) return;

                const businessName = document.getElementById('business_name_print')?.innerText || '';
                const title = document.getElementById('cash_sales_title')?.innerText || '';
                const formNo = document.getElementById('form_no1')?.innerText || '';
                const selectedDate = $('#9c_date_range').val() || '';
                const printableTable = table.cloneNode(true);
                $(printableTable).find('.dataTables_empty').closest('tr').remove();
                const rowCount = printableTable.querySelectorAll('tbody tr').length;
                const printFontSize = rowCount > 20 ? '6.5pt' : (rowCount > 14 ? '7pt' : '8pt');

                const printWindow = window.open('', '_blank', 'width=1200,height=850');
                if (!printWindow) {
                    window.print();
                    return;
                }

                printWindow.document.open();
                printWindow.document.write(`<!doctype html><html><head><title>F9C Cash</title><style>
                    @page { size: A4 landscape; margin: 5mm; }
                    html, body { margin:0; padding:0; font-family:Arial,sans-serif; color:#000; font-size:${printFontSize}; }
                    * { box-sizing:border-box; }
                    .header { position:relative; text-align:center; margin:0 0 4px; line-height:1.15; }
                    .business-name { font-size:13pt; font-weight:700; }
                    .title { font-size:11pt; font-weight:700; }
                    .meta { display:flex; justify-content:space-between; font-weight:700; margin:2px 0 4px; }
                    table { width:100%; border-collapse:collapse; table-layout:fixed; margin:0; }
                    th, td { border:1px solid #000; padding:1.5px 2px; text-align:center; line-height:1.1; overflow-wrap:anywhere; }
                    tfoot { font-weight:700; }
                    .footer { margin-top:8px; page-break-inside:avoid; }
                    .footer td { border:0; padding-top:12px; }
                    .dataTables_empty { display:none; }
                </style></head><body>
                    <div class="header"><div class="business-name">${businessName}</div><div class="title">${title}</div></div>
                    <div class="meta"><span>Date: ${selectedDate}</span><span>Form No: ${formNo}</span></div>
                    ${printableTable.outerHTML}
                    <table class="footer"><tr><td>Entered in the Book</td><td>..............................<br>Checked By</td><td>..............................<br>Manager</td></tr></table>
                </body></html>`);
                printWindow.document.close();
                printWindow.focus();
                printWindow.onload = function() { printWindow.print(); };
                printWindow.onafterprint = function() { printWindow.close(); };
            }
            //form 9c cash list
            // Monetary totals are authoritative server values. The browser
            // retains only the latest data version and report criteria.
            var f9cCashReportVersion = '';
            var f9cCashReportCriteria = '';
            var f9cCashResettingToFirstPage = false;

            function resetF9CCashReportState() {
                f9cCashReportVersion = '';
                f9cCashReportCriteria = '';

                if (typeof form_9ccash_table !== 'undefined' &&
                    $.fn.DataTable.isDataTable('#form_9ccash_table')) {
                    form_9ccash_table.page('first');
                }
            }

            // Footer: Total this page = sum of current page rows; Total Previous Page = previous page Grand Total; Grand Total = cumulative.
            form_9ccash_table = $('#form_9ccash_table').DataTable({
                autoWidth: false,
                processing: true,
                serverSide: true,
                deferRender: true,
                searchDelay: 350,
                paging: true,
                pageLength: 25,
                order: [[0, 'asc']],
                lengthMenu: [
                    [25, 50, 100],
                    [25, 50, 100]
                ],
                ajax: {
                    "type": "get",
                    "url": "/mpcs/get-9ccash-form",
                    data: function(d) {
                        const dateRange = $('#9c_date_range').val();
                        let start_date = '';
                        let end_date = '';

                        if (dateRange.includes(' ~ ')) {
                            let dates = dateRange.split(' ~ ');
                            start_date = moment(dates[0], moment_date_format).format('YYYY-MM-DD');
                            end_date = moment(dates[1], moment_date_format).format('YYYY-MM-DD');
                        } else {
                            start_date = moment(dateRange, 'YYYY-MM-DD').format('YYYY-MM-DD');
                            end_date = start_date;
                        }

                        d.start_date = start_date;
                        d.end_date = end_date;

                        // Task 7788: Add filter parameters
                        d.product_category_id = $('#9c_product_category').val();
                        d.product_sub_category_id = $('#9c_product_sub_category').val();
                        d.product_id = $('#9c_product').val();
                        d.report_version = f9cCashReportVersion;
                        d.report_criteria = f9cCashReportCriteria;
                    }
                },

                columns: [{
                        data: "billno", className: "text-center",
                        name: 'billno'
                    },
                    {
                        data: "product", className: "text-center",
                        name: 'product'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        className: 'text-right',
                        render: function(data, type, row) {
                            let precision = (row.category_name && row.category_name.toLowerCase().includes('fuel')) ? 3 : __quantity_precision;
                            return data ? __number_f(data, false, false, precision) : '';
                        }
                    },
                    {
                        data: 'unit_price',
                        name: 'unit_price',
                        className: 'text-right',
                        render: function(data) {
                            return data ? __number_f(data, false, false, __currency_precision) : '';
                        }
                    },
                    {
                        data: "page", className: "text-center",
                        name: 'page',
                        className: 'text-center'
                    },
                    {
                        data: 'final_total_rs',
                        name: 'final_total_rs',
                        className: 'text-right',
                        render: function(data) {
                            return data ? __number_f(data, false, false, __currency_precision) : '';
                        }
                    },
                    {
                        data: 'goods_rs',
                        name: 'goods_rs',
                        className: 'text-right',
                        render: function(data) {
                            return data ? __number_f(data, false, false, __currency_precision) : '';
                        }
                    },
                    {
                        data: 'loading_rs',
                        name: 'loading_rs',
                        className: 'text-right',
                        render: function(data) {
                            return data ? __number_f(data, false, false, __currency_precision) : '';
                        }
                    },
                    {
                        data: 'empty_rs',
                        name: 'empty_rs',
                        className: 'text-right',
                        render: function(data) {
                            return data ? __number_f(data, false, false, __currency_precision) : '';
                        }
                    },
                    {
                        data: 'transport_rs',
                        name: 'transport_rs',
                        className: 'text-right',
                        render: function(data) {
                            return data ? __number_f(data, false, false, __currency_precision) : '';
                        }
                    },
                    {
                        data: 'other_rs',
                        name: 'other_rs',
                        className: 'text-right',
                        render: function(data) {
                            return data ? __number_f(data, false, false, __currency_precision) : '';
                        }
                    },
                ],
                initComplete: function() {
                    updateF9CCashSelectedDateDisplay();
                },
                fnDrawCallback: function(oSettings) {
                    updateF9CCashSelectedDateDisplay();
                    var api = this.api();
                    var response = api.ajax.json() || oSettings.json || {};
                    var browserPage = api.page.info().page;

                    f9cCashReportVersion = String(response.report_version || f9cCashReportVersion || '');
                    f9cCashReportCriteria = String(response.report_criteria || f9cCashReportCriteria || '');

                    if (response.reset_to_first_page && browserPage > 0) {
                        if (!f9cCashResettingToFirstPage) {
                            f9cCashResettingToFirstPage = true;
                            window.setTimeout(function() {
                                api.page('first').draw('page');
                            }, 0);
                        }
                        return;
                    }
                    f9cCashResettingToFirstPage = false;

                    var currentPage = parseInt(response.page_index, 10);
                    if (!Number.isFinite(currentPage)) {
                        currentPage = browserPage;
                    }

                    // Never derive accounting totals from browser rows or prior pages.
                    var totalRs = Number(response.page_total);
                    var previousDayTotal = Number(response.total_previous_day);
                    var previousPageTotal = Number(response.previous_page_total);
                    var grandRs = Number(response.grand_total_for_page);

                    totalRs = Number.isFinite(totalRs) ? totalRs : 0;
                    previousDayTotal = Number.isFinite(previousDayTotal) ? previousDayTotal : 0;
                    previousPageTotal = Number.isFinite(previousPageTotal) ? previousPageTotal : previousDayTotal;
                    grandRs = Number.isFinite(grandRs) ? grandRs : 0;

                    $('#footer_9c_total').html(__number_f(totalRs, false, false, __currency_precision));
                    $('#previous_day_9c_total').html(__number_f(previousDayTotal, false, false, __currency_precision));
                    $('#pre_9c_total').html(__number_f(previousPageTotal, false, false, __currency_precision));
                    $('#grand_9c_total').html(__number_f(grandRs, false, false, __currency_precision));

                    $('.f9c-total-previous-day-row').toggle(currentPage === 0);
                    $('#pre_9c_total').closest('tr').toggle(currentPage > 0);

                    if (response.form_9c_no !== undefined) {
                        $('#form_no1').html(response.form_9c_no || 0);
                    }
                    $('#custom_message').html(response.custom_message ?? '');
                }
            });

            // Task 7788: Initialize cascading filters for Product Category, Sub Category, and Product
            // Load subcategories lazily and cache them for subsequent filter changes

            var allSubCategories = []; // Cache for all subcategories
            var allSubCategoriesLoaded = false;
            var allSubCategoriesRequest = null;

            // Initialize Select2 for Product Category with search
            $('#9c_product_category').select2({
                placeholder: 'Type or select category...',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });

            // Initialize Select2 for Product Sub Category with search
            $('#9c_product_sub_category').select2({
                placeholder: 'Type or select sub category...',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });

            // Load all subcategories only when needed, then reuse the cached list
            function loadAllSubCategories() {
                if (allSubCategoriesLoaded) {
                    populateSubCategories(allSubCategories);
                    return $.Deferred().resolve(allSubCategories).promise();
                }
                if (allSubCategoriesRequest) {
                    return allSubCategoriesRequest;
                }

                var $subCategory = $('#9c_product_sub_category');
                $subCategory.empty().append('<option value="" selected>All</option>');

                allSubCategoriesRequest = $.ajax({
                    method: 'GET',
                    url: '/mpcs/F17/get-sub-categories',
                    data: { category_id: '' },
                    cache: true
                }).done(function(data) {
                    allSubCategories = data || [];
                    allSubCategoriesLoaded = true;
                    populateSubCategories(allSubCategories);
                }).fail(function() {
                    $subCategory.empty().append('<option value="" selected>All</option>');
                }).always(function() {
                    allSubCategoriesRequest = null;
                });

                return allSubCategoriesRequest;
            }

            // OPTIMIZED: Populate subcategories from cached data
            function populateSubCategories(subcategories) {
                var $subCategorySelect = $('#9c_product_sub_category');

                // Destroy existing Select2 if it exists
                if ($subCategorySelect.data('select2')) {
                    $subCategorySelect.select2('destroy');
                }

                // Clear and add default option
                $subCategorySelect.empty().append('<option value="" selected>All</option>');

                // Add subcategories
                if (subcategories && subcategories.length > 0) {
                    $.each(subcategories, function(index, item) {
                        $subCategorySelect.append(
                            '<option value="' + item.id + '">' + item.name + '</option>'
                        );
                    });
                }

                // Re-initialize Select2
                $subCategorySelect.select2({
                    placeholder: 'Type or select sub category...',
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0
                });
            }

            // OPTIMIZED: Filter cached subcategories by category
            function filterSubCategoriesByCategory(categoryId) {
                if (!categoryId) {
                    // Load the complete list only when the user actually needs it.
                    loadAllSubCategories();
                    return;
                }

                // Make AJAX call only for specific category (this should be fast)
                $.ajax({
                    method: 'GET',
                    url: '/mpcs/F17/get-sub-categories',
                    data: {
                        category_id: categoryId
                    },
                    cache: true, // Enable caching
                    success: function(data) {
                        populateSubCategories(data || []);
                    },
                    error: function() {
                        $('#9c_product_sub_category').empty().append(
                            '<option value="">Error loading subcategories</option>');
                    }
                });
            }

            // Keep the default "All" option instant. Fetch the full sub-category list lazily.
            $('#9c_product_sub_category').one('select2:opening', function() {
                if (!$('#9c_product_category').val()) {
                    loadAllSubCategories();
                }
            });

            // OPTIMIZED: Product Category change handler
            $('#9c_product_category').on('change', function() {
                var cat_id = $(this).val();

                // Reset product dropdown
                if ($('#9c_product').data('select2')) {
                    $('#9c_product').select2('destroy');
                }
                $('#9c_product').empty().append('<option value="">All Products</option>');

                // Filter subcategories based on selected category
                filterSubCategoriesByCategory(cat_id);

                // Re-initialize Select2 for product
                $('#9c_product').select2({
                    ajax: {
                        url: '/products/list',
                        dataType: 'json',
                        delay: 250,
                        cache: true, // Enable caching
                        data: function(params) {
                            return {
                                term: params.term || '',
                                category_id: $('#9c_product_category').val(),
                                sub_category_id: $('#9c_product_sub_category').val()
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: $.map(data, function(item) {
                                    return {
                                        id: item.product_id,
                                        text: item.text || item.name
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    placeholder: 'Type or select product...',
                    allowClear: true,
                    minimumInputLength: 0,
                    minimumResultsForSearch: 0,
                    width: '100%'
                });

                // Reload table
                resetF9CCashReportState();
                form_9ccash_table.ajax.reload(null, true);
            });

            // Product Sub Category change handler
            $('#9c_product_sub_category').on('change', function() {
                // Reset product dropdown
                if ($('#9c_product').data('select2')) {
                    $('#9c_product').select2('destroy');
                }
                $('#9c_product').empty().append('<option value="">All Products</option>');

                // Re-initialize Select2 for product
                $('#9c_product').select2({
                    ajax: {
                        url: '/products/list',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term || '',
                                category_id: $('#9c_product_category').val(),
                                sub_category_id: $('#9c_product_sub_category').val()
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: $.map(data, function(item) {
                                    return {
                                        id: item.product_id,
                                        text: item.text || item.name
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    placeholder: 'Type or select product...',
                    allowClear: true,
                    minimumInputLength: 0,
                    minimumResultsForSearch: 0,
                    width: '100%'
                });

                // Reload table
                resetF9CCashReportState();
                form_9ccash_table.ajax.reload(null, true);
            });

            // Product filter with Select2 AJAX
            $('#9c_product').select2({
                ajax: {
                    url: '/products/list',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term || '',
                            category_id: $('#9c_product_category').val(),
                            sub_category_id: $('#9c_product_sub_category').val()
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    id: item.product_id,
                                    text: item.text || item.name
                                };
                            })
                        };
                    },
                    cache: true
                },
                placeholder: 'Type or select product...',
                allowClear: true,
                minimumInputLength: 0,
                minimumResultsForSearch: 0,
                width: '100%'
            });

            // Product change handler
            $('#9c_product').on('change', function() {
                // Reload table
                resetF9CCashReportState();
                form_9ccash_table.ajax.reload(null, true);
            });

            //form 9a list
            form_9a_settings_table = $('#form_9a_settings_table').DataTable({
                processing: true,
                serverSide: true,
                paging: false,
                deferLoading: 0,
                ajax: {
                    "type": "get",
                    "url": "/mpcs/get-form-9c-settings",
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'date_time',
                        name: 'date_time'
                    },
                    {
                        data: 'starting_number',
                        name: 'starting_number'
                    },
                    {
                        data: 'ref_pre_form_number',
                        name: 'ref_pre_form_number'
                    },
                    {
                        data: 'added_user',
                        name: 'added_user'
                    },
                ]
            });

            $('a[href="#f9c_cash_settings_tab"]').one('shown.bs.tab mpcs.tab.shown', function() {
                form_9a_settings_table.ajax.reload(function() {
                    form_9a_settings_table.columns.adjust();
                }, false);
            });

            // form 9c settings add
            $(document).on('submit', 'form#add_9c_form_settings', function(e) {
                e.preventDefault();
                $(this).find('button[type="submit"]').attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: $(this).attr('method'),
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            form_9a_settings_table.ajax.reload();
                            $('div#form_9_c_settings_modal').modal('hide');

                            if ($('#form_9a_settings_table').length > 0) {
                                $(this).find('button[type="submit"]').attr('disabled', false);
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            // form 9c settings update
            $(document).on('submit', 'form#update_9c_form_settings', function(e) {
                e.preventDefault();
                $(this).find('button[type="submit"]').attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: $(this).attr('method'),
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            form_9a_settings_table.ajax.reload();
                            $('div#update_form_9_c_settings_modal').modal('hide');

                            if ($('#form_9a_settings_table').length > 0) {
                                $(this).find('button[type="submit"]').attr('disabled', false);
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

        });
    </script>
@endsection
