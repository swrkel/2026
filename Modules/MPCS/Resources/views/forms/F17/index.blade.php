@extends('layouts.app')
@section('title', __('mpcs::lang.F17_form'))

@section('content')
    <!-- Main content -->
    <section class="content" id="mpcs-page" data-mpcs-page="f17">
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs" id="mpcs_f17_tabs" data-mpcs-tabs>
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#f17_from_tab" class="f17_from_tab" data-toggle="tab">
                                <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.f17_from')</strong>
                            </a>
                        </li>

                        <li>
                            <a href="#list_f17_from_tab" class="list_f17_from_tab" style="" data-toggle="tab">
                                <i class="fa fa-list"></i> <strong>
                                    @lang('mpcs::lang.list_f17_from') </strong>
                            </a>
                        </li>

                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="f17_from_tab">
                            @include('mpcs::forms.F17.partials.f17_from')
                        </div>

                        <div class="tab-pane" id="list_f17_from_tab">
                            @include('mpcs::forms.F17.partials.list_f17_from')
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection
@section('javascript')
    @include('mpcs::partials.safe_tabs')
    <script type="text/javascript">
        $(document).ready(function() {
            // Use a page-scoped tab switcher so List F17 is not blocked by
            // unrelated global tab handlers or stale Bootstrap active classes.
            var $f17Tabs = $('#mpcs_f17_tabs');

            function activateF17Tab(target, updateHash) {
                var $link = $f17Tabs.children('.nav-tabs').find('a[href="' + target + '"]').first();
                var $pane = $f17Tabs.children('.tab-content').children(target);

                if (!$link.length || !$pane.length) {
                    return false;
                }

                $f17Tabs.children('.nav-tabs').find('li').removeClass('active');
                $link.closest('li').addClass('active');
                $f17Tabs.children('.tab-content').children('.tab-pane').removeClass('active in').hide();
                $pane.addClass('active in').show();

                $f17Tabs.children('.nav-tabs').find('a').attr('aria-expanded', 'false');
                $link.attr('aria-expanded', 'true');

                if ($.fn.dataTable) {
                    if (target === '#f17_from_tab' && $.fn.dataTable.isDataTable('#form_17_table')) {
                        $('#form_17_table').DataTable().columns.adjust();
                    }
                    if (target === '#list_f17_from_tab' && $.fn.dataTable.isDataTable('#list_form_f17_table')) {
                        $('#list_form_f17_table').DataTable().columns.adjust();
                    }
                }

                $link.trigger('shown.bs.tab');

                if (updateHash && window.history && window.history.replaceState) {
                    window.history.replaceState(null, document.title, window.location.pathname + window.location.search + target);
                }

                return true;
            }

            $f17Tabs.children('.nav-tabs').find('a[data-toggle="tab"]')
                .off('click.f17SafeTabs')
                .on('click.f17SafeTabs', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    activateF17Tab($(this).attr('href'), true);
                    return false;
                });

            var initialF17Tab = window.location.hash || '#f17_from_tab';
            if (!activateF17Tab(initialF17Tab, false)) {
                activateF17Tab('#f17_from_tab', false);
            }

            // Debounced reload helper for F17 table to avoid reloading on every tiny filter change
            var form17ReloadTimer = null;
            function scheduleForm17Reload(delayMs) {
                if (typeof form_17_table === 'undefined') {
                    return;
                }
                if (form17ReloadTimer) {
                    clearTimeout(form17ReloadTimer);
                }
                form17ReloadTimer = setTimeout(function() {
                    form_17_table.ajax.reload();
                }, delayMs || 300); // default 300ms debounce
            }

            // Initialize system-standard single date picker for F17 date with custom date range support
            var f17DatePicker = $('#f17_date').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Custom Date Range': [moment().startOf('month'), moment().endOf('month')]
                }
            }, function(start, end, label) {
                if (label === 'Custom Date Range') {
                    // Revert to previous date (or today) and open custom typing modal
                    var prevDate = $('#f17_date').data('f17-prev-date') || moment().format('YYYY-MM-DD');
                    $('#f17_date').val(prevDate);
                    $('#f17_date').data('daterangepicker').setStartDate(moment(prevDate));
                    $('#f17_date').data('f17-custom-range', true);
                    $('.custom_date_typing_modal').modal('show');
                } else {
                    var formattedDate = start.format('YYYY-MM-DD');
                    $('#f17_date').val(formattedDate);
                    $('#f17_date').data('f17-prev-date', formattedDate);
                    if (typeof form_17_table !== 'undefined') {
                        form_17_table.ajax.reload();
                    }
                }
            });

            // The server preselects the first-created business location.
            // Do not reset the dropdown here; users must remain free to select another location.

            // Ensure Store dropdown shows "All" by default
            $('#store_id').val('').trigger('change');
            $('#list_store_id').val('').trigger('change');

            // Initialize Select2 for type and auto-filter functionality
            // minimumResultsForSearch: 0 ensures search box is always visible
            $('#product_list_filter_category_id').select2({
                placeholder: '@lang('lang_v1.all')',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0 // Always show search box
            });

            // Initialize Product Sub Category dropdown with "All" selected by default
            $('#product_list_filter_sub_category_id').select2({
                placeholder: '@lang('lang_v1.all')',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0  // Always show search box for type & auto filter
            });
            
            // Ensure Product Sub Category dropdown shows "All" by default after select2 initialization
            $('#product_list_filter_sub_category_id').val('').trigger('change');

            // Helper to (re)load sub-categories based on selected category.
            // If no category is selected, this will load sub-categories for ALL categories.
            function loadSubCategories(category_id) {
                var $sub = $('#product_list_filter_sub_category_id');
                $sub.empty().append('<option value="" selected>@lang("lang_v1.all")</option>');

                $.ajax({
                    url: '/mpcs/F17/get-sub-categories',
                    method: 'GET',
                    data: { category_id: category_id || '' },
                    success: function(data) {
                        $.each(data, function(index, item) {
                            $sub.append(
                                '<option value="' + item.id + '">' + item.name + '</option>'
                            );
                        });
                        // Keep "All" selected by default
                        $sub.val('').trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading sub-categories:', error);
                        // Ensure "All" is selected even on error
                        $sub.val('').trigger('change');
                    }
                });
            }

            $('#product_list_filter_brand_id').select2({
                placeholder: '@lang('lang_v1.all')',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0 // Always show search box
            });
            
            // Load brands on page load based on category and sub-category selections
            // If BOTH category and sub-category are already selected, show only linked brands
            // Otherwise, show all brands
            var initialCategoryId = $('#product_list_filter_category_id').val();
            var initialSubCategoryId = $('#product_list_filter_sub_category_id').val();
            loadBrands();

            $('#product_list_filter_product_id').select2({
                placeholder: '@lang('lang_v1.all')',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0  // Always show search box for type & auto filter
            });
            
            // Ensure Product dropdown shows "All" by default after select2 initialization
            $('#product_list_filter_product_id').val('').trigger('change');

            $('#product_list_filter_unit_id').select2({
                placeholder: '@lang('lang_v1.all')',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });
            
            // Disable Unit dropdown initially until a product is selected
            $('#product_list_filter_unit_id').prop('disabled', true).trigger('change.select2');

            // Cascading filter: Category -> Sub Category
            $('#product_list_filter_category_id').on('change', function() {
                var category_id = $(this).val();
                // Reload sub-categories for the selected category
                // If category_id is empty, this loads sub-categories for ALL categories.
                loadSubCategories(category_id);
                
                // Reset dependent dropdowns
                // Note: Resetting brand_id will trigger its change handler which calls loadProducts()
                $('#product_list_filter_brand_id').val('').trigger('change');
                
                // Reset Unit dropdown when category changes
                $('#product_list_filter_unit_id').empty().append('<option value="">@lang("lang_v1.all")</option>').trigger('change');
                $('#product_list_filter_unit_id').prop('disabled', true).trigger('change.select2');
                
                // Load brands based on category and sub-category (only filters if both are selected)
                loadBrands();
            });

            // Cascading filter: Sub Category -> Brand
            $('#product_list_filter_sub_category_id').on('change', function() {
                // Reset dependent dropdowns
                // Note: Resetting brand_id will trigger its change handler which calls loadProducts()
                $('#product_list_filter_brand_id').val('').trigger('change');
                
                // Reset Unit dropdown when sub-category changes
                $('#product_list_filter_unit_id').empty().append('<option value="">@lang("lang_v1.all")</option>').trigger('change');
                $('#product_list_filter_unit_id').prop('disabled', true).trigger('change.select2');
                
                // Load brands based on category and sub-category (only filters if both are selected)
                loadBrands();
            });

            // Initial load of sub-categories when page first opens.
            // If no category is selected, this will load sub-categories for ALL categories.
            loadSubCategories($('#product_list_filter_category_id').val());

            // Cascading filter: Brand -> Product
            $('#product_list_filter_brand_id').on('change', function() {
                $('#product_list_filter_product_id').empty().append('<option value="" selected>@lang("lang_v1.all")</option>').trigger('change');
                
                // Reset Unit dropdown when brand changes
                $('#product_list_filter_unit_id').empty().append('<option value="">@lang("lang_v1.all")</option>').trigger('change');
                $('#product_list_filter_unit_id').prop('disabled', true).trigger('change.select2');
                
                loadProducts();
            });

            // When a product is selected, optionally filter/update brand and load units
            $('#product_list_filter_product_id').on('change', function() {
                var product_id = $(this).val();

                if (product_id) {
                    $.ajax({
                        url: '/mpcs/F17/get-product-brand',
                        method: 'GET',
                        data: {
                            product_id: product_id
                        },
                        success: function(data) {
                            if (data.brand_id) {
                                $('#product_list_filter_brand_id').val(data.brand_id).trigger(
                                    'change.select2');
                            }
                        }
                    });

                    // Enable Unit dropdown and load units for the selected product
                    $('#product_list_filter_unit_id').prop('disabled', false).trigger('change.select2');
                    loadUnitsForProduct(product_id);
                } else {
                    // Reset Unit dropdown when no product is selected
                    $('#product_list_filter_unit_id').empty().append('<option value="">@lang("lang_v1.all")</option>').trigger('change');
                    $('#product_list_filter_unit_id').prop('disabled', true).trigger('change.select2');
                }
            });

            // Function to load brands based on category and sub-category
            function loadBrands() {
                var category_id = $('#product_list_filter_category_id').val();
                var sub_category_id = $('#product_list_filter_sub_category_id').val();

                $('#product_list_filter_brand_id').empty().append('<option value="">@lang('lang_v1.all')</option>');

                $.ajax({
                    url: '/mpcs/F17/get-brands',
                    method: 'GET',
                    data: {
                        category_id: category_id,
                        sub_category_id: sub_category_id
                    },
                    success: function(data) {
                        $.each(data, function(index, item) {
                            $('#product_list_filter_brand_id').append(
                                '<option value="' + item.id + '">' + item.name + '</option>'
                            );
                        });
                    }
                });
            }

            // Function to load products based on filters
            function loadProducts() {
                var category_id = $('#product_list_filter_category_id').val();
                var sub_category_id = $('#product_list_filter_sub_category_id').val();
                var brand_id = $('#product_list_filter_brand_id').val();
                
                // Always ensure "All" option is present and selected by default
                var $productSelect = $('#product_list_filter_product_id');
                $productSelect.empty().append('<option value="" selected>@lang("lang_v1.all")</option>');
                
                $.ajax({
                    url: '/mpcs/F17/get-products',
                    method: 'GET',
                    data: { 
                        category_id: category_id || '',
                        sub_category_id: sub_category_id || '',
                        brand_id: brand_id || ''
                    },
                    success: function(data) {
                        if (data && data.length > 0) {
                            $.each(data, function(index, item) {
                                $productSelect.append(
                                    '<option value="' + item.id + '">' + item.name + '</option>'
                                );
                            });
                        }
                        // Ensure "All" remains selected after loading products
                        // Trigger both Select2 and jQuery change events to ensure handlers fire
                        $productSelect.val('').trigger('change.select2').trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading products:', error);
                        // Ensure "All" is selected even on error
                        // Trigger both Select2 and jQuery change events to ensure handlers fire
                        $productSelect.val('').trigger('change.select2').trigger('change');
                    }
                });
            }

            function loadUnitsForProduct(product_id) {
                $('#product_list_filter_unit_id').empty().append('<option value="">@lang('lang_v1.all')</option>');

                if (!product_id) {
                    $('#product_list_filter_unit_id').prop('disabled', true).trigger('change.select2');
                    $('#product_list_filter_unit_id').trigger('change');
                    return;
                }

                // Enable Unit dropdown when loading units
                $('#product_list_filter_unit_id').prop('disabled', false).trigger('change.select2');

                $.ajax({
                    url: '/mpcs/F17/get-units',
                    method: 'GET',
                    data: {
                        product_id: product_id
                    },
                    success: function(data) {
                        if (data && data.length > 0) {
                            $.each(data, function(index, item) {
                                $('#product_list_filter_unit_id').append(
                                    '<option value="' + item.id + '">' + item.name + '</option>'
                                );
                            });
                        }
                        $('#product_list_filter_unit_id').trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading units:', error);
                        $('#product_list_filter_unit_id').empty().append('<option value="">@lang("lang_v1.all")</option>').trigger('change');
                    }
                });
            }

            //form_17_table 
            form_17_table = $('#form_17_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/mpcs/F17/create',
                    data: function(d) {
                        d.start_date = $('#f17_date').val();
                        d.category_id = $('#product_list_filter_category_id').val();
                        d.sub_category_id = $('#product_list_filter_sub_category_id').val();
                        d.brand_id = $('#product_list_filter_brand_id').val();
                        d.product_id = $('#product_list_filter_product_id').val();
                        d.unit_id = $('#product_list_filter_unit_id').val();
                        d.location_id = $('#location_id').val();
                        d.store_id = $('#store_id').val();
                    }
                },
                columns: [{
                        data: 'DT_Row_Index',
                        name: 'DT_Row_Index',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'sku',
                        name: 'products.sku'
                    },
                    {
                        data: 'product',
                        name: 'products.name'
                    },
                    {
                        data: 'current_stock',
                        name: 'vld.qty_available',
                        className: 'text-right', // right-align
                    },
                    {
                        data: 'unit_price',
                        name: 'variations.default_sell_price'
                    },
                    {
                        data: 'select_mode',
                        name: 'select_mode'
                    },
                    {
                        data: 'new_price',
                        name: 'new_price'
                    },
                    {
                        data: 'unit_price_difference',
                        name: 'unit_price_difference'
                    },
                    {
                        data: 'price_changed_loss',
                        name: 'price_changed_loss'
                    },
                    {
                        data: 'price_changed_gain',
                        name: 'price_changed_gain'
                    },
                    {
                        data: 'signature',
                        name: 'signature'
                    },
                    {
                        data: 'page_no',
                        name: 'page_no'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    // Handle the pagination update and update form numbers
                    let pageInfo = form_17_table.page.info();
                    let pageNumber = pageInfo.page + 1;

                    let formNumber = "{{ $F17_from_no }}";
                    if (pageInfo.pages > 1) {
                        let newFormNumber = formNumber + '-' + pageNumber;
                        $('#F17_from_no').val(newFormNumber);
                    } else {
                        $('#F17_from_no').val(formNumber);
                    }
                    
                    // Recalculate price changes for rows that have new_price values
                    // This ensures calculations use the updated current_stock values after table reload
                    form_17_table.$('.new_price_value').each(function() {
                        if ($(this).val()) {
                            $(this).trigger('keyup');
                        }
                    });
                },
                columnDefs: [{
                    width: 20,
                    targets: 6
                }],
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                $.fn.dataTable
                    .tables({
                        visible: true,
                        api: true
                    })
                    .columns.adjust()
                    .responsive.recalc();
            });

            $(document).on('keyup', '.new_price_value', function() {
                let tr = $(this).parent().parent();
                let unit_price = parseFloat(tr.find('.unit_price').data('orig-value')) || 0;
                let select_mode = tr.find('.select_mode').val();
                let current_stock = parseFloat(tr.find('.current_stock').data('orig-value')) || 0;
                let new_price = parseFloat($(this).val()) || 0;

                price_gain = 0;
                price_loss = 0;
                difference = 0;

                if (new_price && unit_price) {
                    difference = new_price - unit_price;
                    
                    if (select_mode == 'increase') {
                        // In increase mode: positive difference = gain, negative = loss
                        if (difference > 0) {
                            price_gain = current_stock * difference;
                        } else if (difference < 0) {
                            price_loss = Math.abs(current_stock * difference);
                        }
                    } else if (select_mode == 'decrease') {
                        // In decrease mode: negative difference = loss, positive = gain
                        if (difference < 0) {
                            price_loss = Math.abs(current_stock * difference);
                        } else if (difference > 0) {
                            price_gain = current_stock * difference;
                        }
                    }
                }

                tr.find('.price_changed_loss').text(__number_f(price_loss, false, false,
                    __currency_precision));
                tr.find('.price_changed_gain').text(__number_f(price_gain, false, false,
                    __currency_precision));
                tr.find('.unit_price_difference').text(__number_f(difference, false, false,
                    __currency_precision));
                tr.find('.price_changed_gain_value').val(price_gain);
                tr.find('.price_changed_loss_value').val(price_loss);
                tr.find('.unit_price_difference_value').val(difference);
            });

            $(document).on('change', '.select_mode', function() {
                let tr = $(this).parent().parent();
                tr.find('.new_price_value').trigger('keyup');
            });

            // Auto-filter when any filter changes
            $('.f17_filter').on('change', function() {
                // Use debounced reload so applying multiple filters feels instant
                scheduleForm17Reload(300);
            });
            
            // Handle date field changes (daterangepicker apply + manual input)
            $('#f17_date')
                .on('apply.daterangepicker', function(ev, picker) {
                    // If this apply came from "Custom Date Range" flow, skip; modal handler will update
                    if ($('#f17_date').data('f17-custom-range')) {
                        $('#f17_date').removeData('f17-custom-range');
                        return;
                    }
                    // Ensure value is in system standard format before reload
                    $(this).val(picker.startDate.format('YYYY-MM-DD'));
                    // Debounce reload to avoid feeling sluggish on rapid date changes
                    scheduleForm17Reload(200);
                })
                .on('change blur', function() {
                    // Fallback for manual typing or other changes
                    scheduleForm17Reload(300);
                });

            // F17 Save is handled by the MPCS module entry file: public/modules/mpcs/js/mpcs.js

            $('#location_id').change(function() {
                // Location changed - stores remain showing all stores
                // Optionally filter stores by location if needed in future
            });

            $('#list_form_f17_location_id').change(function() {
                // Location changed - stores remain showing all stores
                // Optionally filter stores by location if needed in future
            });

            // List view: Category -> Sub Category filter
            $('#list_f17_category_id').on('change', function() {
                var category_id = $(this).val();
                $('#list_f17_sub_category_id').empty().append(
                    '<option value="">@lang('lang_v1.all')</option>');

                if (category_id) {
                    $.ajax({
                        url: '/mpcs/F17/get-sub-categories',
                        method: 'GET',
                        data: {
                            category_id: category_id
                        },
                        success: function(data) {
                            $.each(data, function(index, item) {
                                $('#list_f17_sub_category_id').append(
                                    '<option value="' + item.id + '">' + item.name +
                                    '</option>'
                                );
                            });
                        }
                    });
                }
            });

            $('#from_no_filter').select2();

            if ($('#list_f17_date_range').length === 1) {
                $('#list_f17_date_range').daterangepicker(dateRangeSettings, function(start, end, label) {
                    console.log("Selected label:", label);

                    if (label === 'Custom Date Range') {
                        $('.custom_date_typing_modal').modal('show');
                    } else {
                        $('#list_f17_date_range').val(
                            start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                        );
                        list_form_f17_table.ajax.reload();
                    }
                });

                // Optional: handle cancel action (clear input)
                $('#list_f17_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#list_f17_date_range').val('');
                });

                // Open modal if user clicks "Custom range" *before* selecting any date
                $('#list_f17_date_range').on('show.daterangepicker', function() {
                    setTimeout(() => {
                        $('.ranges li').off('click').on('click', function() {
                            const label = $(this).text();
                            if (label === 'Custom Date Range') {
                                $('.custom_date_typing_modal').modal('show');
                            }
                        });
                    }, 0);
                });

                // Set default start/end date
                $('#list_f17_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
                $('#list_f17_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));

                $('#custom_date_apply_button').on('click', function() {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2')
                    .val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" +
                        $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
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
                        if ($('#list_f17_date_range').length) {
                            $('#list_f17_date_range').val(fullRange);
                            $('#list_f17_date_range').data('daterangepicker').setStartDate(moment(
                                startDate));
                            $('#list_f17_date_range').data('daterangepicker').setEndDate(moment(endDate));
                            $("#report_date_range").text("Date Range: " + fullRange);
                            list_form_f17_table.ajax.reload();
                        }
                        // Also update F17 single-date field to use the custom start date, if present
                        if ($('#f17_date').length) {
                            let singleFormatted = moment(startDate).format('YYYY-MM-DD');
                            $('#f17_date').val(singleFormatted);
                            if ($('#f17_date').data('daterangepicker')) {
                                $('#f17_date').data('daterangepicker').setStartDate(moment(startDate));
                            }
                            $('#f17_date').data('f17-prev-date', singleFormatted);
                            if (typeof form_17_table !== 'undefined') {
                                form_17_table.ajax.reload();
                            }
                        }
                        // Hide the modal
                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                });
            }



            //list_form_f17_table 
            list_form_f17_table = $('#list_form_f17_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: false,
                ajax: {
                    url: '/mpcs/list-F17',
                    data: function(d) {
                        var start_date = $('input#list_f17_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        var end_date = $('input#list_f17_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        d.start_date = start_date;
                        d.end_date = end_date;
                        d.from_no = $('#from_no_filter').val();
                        d.category_id = $('#list_f17_category_id').val();
                        d.sub_category_id = $('#list_f17_sub_category_id').val();
                        d.brand_id = $('#list_f17_brand_id').val();
                        d.unit_id = $('#list_f17_unit_id').val();
                        d.location_id = $('#list_form_f17_location_id').val();
                        d.store_id = $('#list_store_id').val();
                    }
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
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
                        data: 'location',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'category',
                        name: 'categories.name'
                    },
                    {
                        data: 'sub_category',
                        name: 'sub_cat.name'
                    },
                    {
                        data: 'store',
                        name: 'stores.name'
                    },
                    {
                        data: 'select_mode',
                        name: 'select_mode',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_price_change_loss',
                        name: 'total_price_change_loss'
                    },
                    {
                        data: 'total_price_change_gain',
                        name: 'total_price_change_gain'
                    },
                    {
                        data: 'username',
                        name: 'users.username'
                    },
                    {
                        data: 'page_no',
                        name: 'form_f17_headers.page_no'
                    },

                ],
                fnDrawCallback: function(oSettings) {

                },
            });

            $('.list_f17_filter').change(function() {
                list_form_f17_table.ajax.reload();
            })

            // Load all products on initial page load (when no filters are selected)
            // This ensures the Product dropdown has products available for selection
            // Use a small delay to ensure all Select2 initializations are complete
            setTimeout(function() {
                var category_id = $('#product_list_filter_category_id').val();
                var sub_category_id = $('#product_list_filter_sub_category_id').val();
                var brand_id = $('#product_list_filter_brand_id').val();
                
                // If no filters are selected, load all products
                // If filters are selected, loadProducts() will be called by the change handlers
                if (!category_id && !sub_category_id && !brand_id) {
                    loadProducts();
                }
            }, 200);

        });
    </script>

    @include('mpcs::partials.module_script', [
        'mpcs_page' => 'f17',
        'mpcs_config' => [
            'csrfToken' => csrf_token(),
            'saveUrl' => url('/mpcs/F17'),
            'redirectUrl' => url('/mpcs/F17'),
            'messages' => [
                'saving' => __('messages.saving'),
                'saved' => __('mpcs::lang.success'),
                'error' => __('messages.something_went_wrong'),
                'dateRequired' => __('validation.required', ['attribute' => __('mpcs::lang.date')]),
                'locationRequired' => __('validation.required', ['attribute' => __('purchase.business_location')]),
                'priceRequired' => 'Please enter at least one valid new price before saving.',
            ],
        ],
    ])
@endsection
