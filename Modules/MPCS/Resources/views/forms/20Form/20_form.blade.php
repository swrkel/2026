<style>
    .rows {
        padding: 0 !important;
        margin: 0 !important;
    }

    .full-width-input {
        width: 100% !important;
        box-sizing: border-box;
        padding: 5px;
        margin: 0;
        border: 1px solid #ccc;
        height: 100%;
    }

    .table tbody tr td.rows {
        padding: 0 !important;
        vertical-align: middle !important;
    }

    .text-center {
        text-align: center;
    }

    .text-red {
        color: red;
    }

    .f20_location_name {
        font-size: 20px;
    }

    .table th,
    .table td {
        text-align: center;
        vertical-align: middle;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .bg-gray {
        background-color: #f7f7f7;
    }

    .text-bold {
        font-weight: bold;
    }

    .page-button {
        padding: 5px 10px;
        margin: 0 5px;
        border: 1px solid #ddd;
        cursor: pointer;
    }

    .page-button:hover {
        background-color: #f1f1f1;
    }

    .page-button.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    #pagination-controls {
        margin-top: 10px;
        text-align: center;
    }

    /* IS2316 #1: standard report controls for F20. */
    .f20-standard-tools {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin: 4px 0 12px;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
    }

    .f20-standard-tools .dt-buttons,
    .f20-standard-tools .dataTables_filter,
    .f20-standard-tools .dataTables_length {
        margin: 0 !important;
        float: none !important;
    }

    .f20-standard-tools .dt-buttons .btn {
        margin-right: 5px;
        margin-bottom: 3px;
    }

    .f20-standard-tools label {
        margin: 0;
        font-weight: 600;
    }

    .f20-standard-tools input[type=search],
    .f20-standard-tools select {
        height: 34px;
        border: 1px solid #d2d6de;
        border-radius: 4px;
        padding: 5px 8px;
        background: #fff;
    }

    /* Wide F20 report: one native horizontal scrollbar only. */
    .f20-table-scroll {
        width: 100%;
        overflow-x: scroll !important;
        overflow-y: visible;
        scrollbar-gutter: stable both-edges;
        scroll-behavior: auto;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x pan-y;
    }

    #form_20_table_data {
        width: max-content !important;
        min-width: 100% !important;
        table-layout: auto !important;
    }

    #form_20_table_data th,
    #form_20_table_data td {
        min-width: 110px;
        white-space: nowrap;
    }

    #form_20_table_data th:first-child,
    #form_20_table_data td:first-child,
    #form_20_table_data th:nth-child(2),
    #form_20_table_data td:nth-child(2) {
        min-width: 125px;
    }

    /* IS2349 #4: use only the table's native horizontal scrollbar. */

    .f20-manager-signature {
        display: flex;
        justify-content: flex-end;
        margin: 26px 4px 8px;
        page-break-inside: avoid;
    }

    .f20-manager-signature-box {
        width: 260px;
        text-align: center;
        font-weight: 700;
        color: #222;
    }

    .f20-manager-signature-line {
        border-top: 1px solid #333;
        margin-bottom: 7px;
        height: 1px;
    }

    #table-container {
        min-width: 100%;
        width: max-content;
    }

    @media print {
        .f20-table-scroll {
            overflow: visible !important;
        }

        /*
         * IS2110 #2: the printed sheet was carrying the whole application
         * around the report.
         *
         * The preview showed the three tab buttons WITH their raw hrefs -
         * "F 20 Form - CDS (/mpcs/F20-CDS#f20_cds_form_tab)" and the other two -
         * plus the sidebar toggle sitting over the table. This block only ever
         * hid a slider, so everything else printed.
         *
         * Two separate causes:
         *
         *   - the page furniture was never suppressed for print;
         *   - the theme's print CSS appends attr(href) to anchors, which is what
         *     turned the tab captions into URLs.
         *
         * Both are handled below. This is the same set of rules the F15 daily
         * report needed under IS2029.
         */
        .main-header,
        .main-sidebar,
        .left-side,
        .content-header,
        .navbar,
        .sidebar,
        .sidebar-menu,
        .sidebar-toggle,
        .control-sidebar,
        .control-sidebar-bg,
        .main-footer,
        footer,
        .no-print,
        .modal,
        .modal-backdrop,
        .breadcrumb,
        .nav-tabs,
        ul.nav-tabs,
        .btn,
        .dataTables_filter,
        .dataTables_length,
        .dataTables_paginate,
        .dataTables_info {
            display: none !important;
        }

        /* Never print link targets - this is what produced
           "(/mpcs/F20-CDS#f20_cds_form_tab)" beside each tab caption. */
        a[href]:after,
        abbr[title]:after {
            content: "" !important;
        }

        /* Reclaim the sidebar gutter so the report starts at the page margin. */
        html, body,
        .wrapper,
        .content-wrapper,
        .right-side,
        .content {
            margin: 0 !important;
            padding: 0 !important;
            width: auto !important;
            background: #fff !important;
        }

        /* IS2295 #4 fallback for Ctrl+P / popup-blocked browsers. */
        .f20-print-exclude {
            display: none !important;
        }

        #form_20_table_data {
            width: 100% !important;
            min-width: 0 !important;
            table-layout: fixed !important;
        }

        #form_20_table_data th,
        #form_20_table_data td {
            min-width: 0 !important;
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            font-size: 8px !important;
            padding: 2px !important;
        }

        /* Keep rows whole and repeat the header on each page. */
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
    }

</style>

<!-- Main content -->
<section class="content" style="padding-left: 0; padding-right: 0">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="col-md-12">
                    <div class="row f20-print-exclude">
                        <div class="col-md-4 text-red" style="margin-top: 14px;"></div>

                        <div class="col-md-5 text-center">
                            <h5 style="font-weight: bold;">
                                @foreach ($business_locations as $location)
                                    <span class="f20_location_name">{{ $location }}</span>
                                @endforeach
                            </h5>
                        </div>

                        <div class="col-md-3 text-left">
                            <h5 style="font-weight: bold;" class="form-control">
                                @lang('mpcs::lang.form_no') : <span id="form_no1">{{ $F20_form_sn }}</span>
                            </h5>
                        </div>
                    </div>

                    <div class="row f20-print-exclude">
                        <div class="col-md-4 no-print" id="location_filter" style="padding-top: 25px;">
                            <button type="button" id="f20_print" class="btn btn-primary" onclick="openF20PrintPreview(); return false;">
                                <i class="fa fa-print"></i> @lang('mpcs::lang.print')
                            </button>
                        </div>

                        <div class="col-md-4 text-center">
                            <p>Filling Station Stock Sale Summary</p>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('type', 'Form Type:') !!}
                                {!! Form::select('16a_location_id', ['All' => 'All', 'Credit' => 'Credit', 'Cash' => 'Cash'], request()->get('form_type', 'All'), [
                                    'class' => 'form-control select2',
                                    'style' => 'width:100%',
                                    'id' => 'form_type_select',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('form_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text(
                                    'form_20_date_range',
                                    request()->get('form_date', now()->format('Y-m-d')),
                                    [
                                        'placeholder' => __('lang_v1.select_a_date_range'),
                                        'class' => 'form-control',
                                        'id' => 'form_20_date_range_data',
                                        'readonly',
                                    ],
                                ) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            @component('components.widget', ['class' => 'box-primary'])
                                <div class="col-md-12">
                                    <div class="row" style="margin-top: 20px;">
                                        <div class="col-md-12 f20-print-exclude">
                                            <div id="f20-standard-tools" class="f20-standard-tools" aria-label="F20 report tools"></div>
                                        </div>
                                        <div class="table-responsive f20-table-scroll" id="f20-table-scroll">
                                            <div id="table-container">
                                                <table class="table table-bordered table-striped"
                                                       id="form_20_table_data">
                                                    <thead>
                                                    <tr>
                                                        <th rowspan="2" class="bill-no-col">@lang('mpcs::lang.bill_no')</th>
                                                        <th rowspan="2">@lang('mpcs::lang.settlement_no')</th>
                                                        @foreach ($products as $productId => $product)
                                                            <th class="f20-product-col" data-product-id="{{ $productId }}">{{ data_get($product, '0.product_sku', '') }}</th>
                                                        @endforeach
                                                    </tr>
                                                    <tr>
                                                        @foreach ($products as $productId => $product)
                                                            <th class="text-left f20-product-col" data-product-id="{{ $productId }}">{{ data_get($product, '0.product_name', '') }}</th>
                                                        @endforeach
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <!-- Rows will be populated dynamically via AJAX -->
                                                    </tbody>
                                                    <tfoot class="bg-gray">
                                                    <tr>
                                                        <td class="text-bold">Total Qty</td>
                                                        <td></td>
                                                        @foreach ($products as $productId => $product)
                                                            <td class="text-bold total-qty f20-product-col" data-product-id="{{ $productId }}"></td>
                                                        @endforeach
                                                    </tr>
                                                    <tr>
                                                        <td class="text-bold">Unit Sales Price</td>
                                                        <td></td>
                                                        @foreach ($products as $productId => $product)
                                                            <td class="text-bold total-price f20-product-col" data-product-id="{{ $productId }}"></td>
                                                        @endforeach
                                                    </tr>
                                                    <tr>
                                                        <td class="text-bold">Total Amount</td>
                                                        <td></td>
                                                        @foreach ($products as $productId => $product)
                                                            <td class="text-bold total-amount f20-product-col" data-product-id="{{ $productId }}"></td>
                                                        @endforeach
                                                    </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- IS2349 #5: manager signature section on the F20 form. --}}
                                        <div class="f20-manager-signature">
                                            <div class="f20-manager-signature-box">
                                                <div class="f20-manager-signature-line"></div>
                                                <span>Manager Signature</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endcomponent
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        // Cache for form numbers to avoid repeated AJAX calls
        var formNumberCache = {};
        var pendingRequest = null;
        
        // Optimized fetchFormNumber with caching and independent execution
        window.fetchFormNumber = function() {
            var formType = $('#form_type_select').val();
            var formDate = $('#form_20_date_range_data').val();
            var cacheKey = formType + '|' + formDate;
            
            // Return cached result if available
            if (formNumberCache[cacheKey] !== undefined) {
                $('#form_no1').text(formNumberCache[cacheKey]);
                return;
            }
            
            // Create a new request object to avoid conflicts with other AJAX calls
            var formNumberRequest = $.ajax({
                url: '/mpcs/fetch-form-number',
                method: 'GET',
                data: {
                    form_type: formType,
                    form_date: formDate
                },
                success: function(response) {
                    formNumberCache[cacheKey] = response.form_number;
                    $('#form_no1').text(response.form_number);
                },
                error: function(xhr, status, error) {
                    if (status !== 'abort') {
                        console.error("Error fetching form number:", error);
                        // Don't show error to user, just log it
                    }
                }
            });
            
            // Store the request reference separately to avoid conflicts
            window.formNumberRequest = formNumberRequest;
        };
        
        // Preload cache on page load
        window.preloadFormNumberCache = function() {
            var formTypes = ['All', 'Credit', 'Cash'];
            var currentDate = $('#form_20_date_range_data').val();
            
            formTypes.forEach(function(formType) {
                var cacheKey = formType + '|' + currentDate;
                if (formNumberCache[cacheKey] === undefined) {
                    $.ajax({
                        url: '/mpcs/fetch-form-number',
                        method: 'GET',
                        data: {
                            form_type: formType,
                            form_date: currentDate
                        },
                        success: function(response) {
                            formNumberCache[cacheKey] = response.form_number;
                        },
                        error: function() {
                            // Silent fail for preloading
                        }
                    });
                }
            });
        };
        
        // Trigger on form date change with immediate and independent form number update
        $('#form_20_date_range_data').on('apply.daterangepicker', function(ev, picker) {
            // IMMEDIATE form number update - this will be instant if cached, or fast if not
            window.fetchFormNumber();
            
            // Preload other form types for this date in the background
            setTimeout(function() {
                window.preloadFormNumberCache();
            }, 100);
        });
        
        // Trigger on form type change
        $('#form_type_select').change(window.fetchFormNumber);
        
        // Trigger immediately on page load and preload cache
        window.fetchFormNumber();
        setTimeout(window.preloadFormNumberCache, 500);

        $(document).on('submit', 'form#add_21c_form_settings', function(e) {
            e.preventDefault();
            const tableRows = $('#products_table_body tr');
            if (tableRows.length === 0) {
                toastr.error('Please add at least one product.');
                return false;
            }

            $(this).find('button[type="submit"]').attr('disabled', true);

            const subcategoryIds = [];
            const productIds = [];

            tableRows.each(function() {
                const row = $(this);
                const productId = row.data('product-id');
                const subcategoryId = row.data('subcategory-id');

                subcategoryIds.push(subcategoryId);
                productIds.push(productId);
            });

            const data = {
                date: $('#datepicker').val(),
                starting_number: $('input[name="starting_number"]').val(),
                total_sale: $('input[name="total_sale"]').val(),
                cash_sale: $('input[name="cash_sale"]').val(),
                credit_sale: $('input[name="credit_sale"]').val(),
                category: subcategoryIds.join(','),
                product: productIds.join(','),
            };

            $.ajax({
                method: $(this).attr('method'),
                url: '/mpcs/store-20-form-setting',  // URL to send the data
                dataType: 'json',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                        window.location.reload(true);
                    } else {
                        toastr.success(result.msg);
                        $('div#form_16_a_settings_modal').modal('hide');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while submitting the form.');
                },
                complete: function() {
                    $(this).find('button[type="submit"]').attr('disabled', false);
                }
            });
        });

        $(document).on('submit', 'form#update_21c_form_settings', function(e) {
            e.preventDefault();
            const tableRows = $('#products_table_body tr');
            if (tableRows.length === 0) {
                toastr.error('Please add at least one product.');
                return false;
            }

            $(this).find('button[type="submit"]').attr('disabled', true);

            const subcategoryIds = [];
            const productIds = [];

            tableRows.each(function() {
                const row = $(this);
                const productId = row.data('product-id');
                const subcategoryId = row.data('subcategory-id');

                subcategoryIds.push(subcategoryId);
                productIds.push(productId);
            });

            const data = {
                date: $('#datepicker').val(),
                starting_number: $('input[name="starting_number"]').val(),
                total_sale: $('input[name="total_sale"]').val(),
                cash_sale: $('input[name="cash_sale"]').val(),
                credit_sale: $('input[name="credit_sale"]').val(),
                category: subcategoryIds.join(','),
                product: productIds.join(','),
            };

            $.ajax({
                method: $(this).attr('method'),
                url:  $(this).attr('action'),
                dataType: 'json',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(result) {
                    if (result.success === true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.success(result.msg);
                        window.location.reload(true);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while submitting the form.');
                },
                complete: function() {
                    $(this).find('button[type="submit"]').attr('disabled', false);
                }
            });
        });
    });
</script>
