@extends('layouts.app')
@section('title', __('mpcs::lang.F22StockTaking_form'))

@section('content')
    <style>
        .half-width-input {
            width: 10%;
        }

        .flex-container {
            display: flex;
        }
    </style>
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1> @lang('mpcs::lang.F22StockTaking_form')
            <small>@lang('mpcs::lang.F22StockTaking_form', ['contacts' => __('mpcs::lang.mange_F22StockTaking_form')])</small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs" id="mpcs_f22_tabs" data-mpcs-tabs data-auto-permission-module="mpcs">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#f22_form_tab" class="f22_form_tab" data-toggle="tab">
                                <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.f22_form')</strong>
                            </a>
                        </li>

                        <li>
                            <a href="#f22_last_verified_stock_tab" class="f22_last_verified_stock_tab" style=""
                               data-toggle="tab">
                                <i class="fa fa-check"></i> <strong>
                                    @lang('mpcs::lang.f22_last_verified_stock') </strong>
                            </a>
                        </li>

                        <li>
                            <a href="#list_f22_stock_taking_tab" class="list_f22_stock_taking_tab" style=""
                               data-toggle="tab">
                                <i class="fa fa-sign-in"></i> <strong>
                                    @lang('mpcs::lang.list_f22_stock_taking') </strong>
                            </a>
                        </li>
                        <li>
                            <a href="#list_f22_link_account_tab" class="list_f22_link_account" style=""
                               data-toggle="tab">
                                <i class="fa fa-sign-in"></i> <strong>
                                    @lang('mpcs::lang.F22_link_account') </strong>
                            </a>
                        </li>
                         @if ($mpcs_authorized_signature_permission)
                        <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#mpcs_signature_tab" role="tab">
                                {{ __('membership::lang.authorized_signature') }}
                                </a>
                        </li>
                        @endif
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="f22_form_tab">
                            @include('mpcs::forms.F22.partials.f22_form')
                        </div>

                        <div class="tab-pane" id="f22_last_verified_stock_tab">
                            @include('mpcs::forms.F22.partials.f22_last_verified_stock')
                        </div>

                        <div class="tab-pane" id="list_f22_stock_taking_tab">
                            @include('mpcs::forms.F22.partials.list_f22_stock_taking')
                        </div>
                        <div class="tab-pane" id="list_f22_link_account_tab">
                            @include('mpcs::forms.F22.partials.list_f22_link_account')
                        </div>
                          @if ($mpcs_authorized_signature_permission)
                        <div class="tab-pane" id="mpcs_signature_tab" role="tabpanel">
                            @include('mpcs::forms.F22.partials.f22_signatures')
                        </div>
                           @endif
                    </div>
                </div>
            </div>
        </div>


    </section>
    <!-- /.content -->

    <div class="modal" tabindex="-1" role="dialog" id="f22DateRangeModal" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title">@lang('mpcs::lang.select_custom_date_range')</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <input class="form-control" placeholder="Select Start Date" readonly id="start-date-range"/>
                        </div>
                        <div class="col-md-6">
                            <input class="form-control" placeholder="Select End Date" readonly id="end-date-range"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">@lang('mpcs::lang.cancel')</button>
                    <button type="button" class="btn btn-primary"
                            id="apply-date-range">@lang('mpcs::lang.apply')</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('javascript')
    @include('mpcs::partials.safe_tabs')
    <script type="text/javascript">
        $(document).ready(function () {
            // Isolate F22 tab navigation from the global tab engine. This restores
            // Last Verified Stock, List Stock Taking and Link Account reliably.
            var $f22Tabs = $('#mpcs_f22_tabs');

            function normalizeF22TabTarget(target) {
                target = String(target || '').trim();
                if (!target) {
                    return '';
                }

                if (target.charAt(0) !== '#') {
                    try {
                        var targetUrl = new URL(target, window.location.href);
                        if (targetUrl.origin !== window.location.origin ||
                            targetUrl.pathname !== window.location.pathname) {
                            return '';
                        }
                        target = targetUrl.hash;
                    } catch (error) {
                        return '';
                    }
                }

                return /^#[A-Za-z][A-Za-z0-9_:.-]*$/.test(target) ? target : '';
            }

            function activateF22Tab(target, updateHash) {
                target = normalizeF22TabTarget(target);
                if (!target) {
                    return false;
                }

                var $link = $f22Tabs.children('.nav-tabs').find('a').filter(function () {
                    return normalizeF22TabTarget($(this).attr('href')) === target;
                }).first();
                var pane = document.getElementById(target.slice(1));
                var $pane = pane ? $(pane) : $();

                if (!$link.length || !$pane.length) {
                    return false;
                }

                $f22Tabs.children('.nav-tabs').find('li').removeClass('active');
                $f22Tabs.children('.nav-tabs').find('a')
                    .removeClass('active')
                    .attr('aria-selected', 'false');
                $link.closest('li').addClass('active');
                $link.addClass('active').attr('aria-selected', 'true');
                $f22Tabs.children('.tab-content').children('.tab-pane').removeClass('active in').hide();
                $pane.addClass('active in').show();

                $f22Tabs.children('.nav-tabs').find('a').attr('aria-expanded', 'false');
                $link.attr('aria-expanded', 'true');

                if ($.fn.dataTable) {
                    var tableMap = {
                        '#f22_form_tab': '#form_22_table',
                        '#f22_last_verified_stock_tab': '#form_22_last_verified_table',
                        '#list_f22_stock_taking_tab': '#form_f22_list_table',
                        '#list_f22_link_account_tab': '#form_f22_list_table_stock_taking',
                        '#mpcs_signature_tab': '#f22_signatures_table'
                    };
                    var tableSelector = tableMap[target];
                    if (tableSelector && $.fn.dataTable.isDataTable(tableSelector)) {
                        $(tableSelector).DataTable().columns.adjust();
                    }
                }

                // Keep existing page-specific loaders, especially the last-verified
                // pump loader, working after the manual panel switch.
                $link.trigger('shown.bs.tab');

                if (updateHash && window.history && window.history.replaceState) {
                    window.history.replaceState(null, document.title, window.location.pathname + window.location.search + target);
                }

                return true;
            }

            $f22Tabs.children('.nav-tabs').find('a[data-toggle="tab"]')
                .off('click.f22SafeTabs')
                .on('click.f22SafeTabs', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    activateF22Tab($(this).attr('href'), true);
                    return false;
                });

            var initialF22Tab = window.location.hash || '#f22_form_tab';
            if (!activateF22Tab(initialF22Tab, false)) {
                activateF22Tab('#f22_form_tab', false);
            }

            let form22EditedValues = {};
            let newPageLength = parseInt({{ $settings->F22_no_of_product_per_page ?? 25 }});
            var dateRangeSettings = {
                singleDatePicker: true, // ✅ Only single date selection
                showDropdowns: true,
                autoUpdateInput: true,
                startDate: moment(), // ✅ Default date: today
                locale: {
                    format: moment_date_format // Your desired format
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    // 'Custom Date': [moment(), moment()],
                    // 'Custom Date Range': [moment(), moment()]
                },
                showCustomRangeLabel: false, // Optional: for custom modal
                alwaysShowCalendars: true,
            };


        /*
         * IS2126 / IS2122: send the selected date as an unambiguous Y-m-d.
         *
         * #f22_date is a text field written by the daterangepicker in the
         * BUSINESS display format. A value like "08/12/2026" is 12 August under
         * m/d/Y and 8 December under d/m/Y, and the server cannot tell which was
         * meant - which is why a saved F22 came back with the day and month
         * swapped.
         *
         * moment is given the SAME format the picker used to write the value, so
         * this is a straight conversion rather than a guess. The field on screen
         * is untouched; only what is transmitted changes.
         */
        function f22SelectedDateIso() {
            var raw = $.trim($('#f22_date').val() || '');

            if (raw === '') {
                return '';
            }

            if (typeof moment === 'function') {
                var parsed = moment(raw, moment_date_format, true);

                if (parsed.isValid()) {
                    return parsed.format('YYYY-MM-DD');
                }

                // Already ISO, or written by something other than the picker.
                var loose = moment(raw, ['YYYY-MM-DD', moment_date_format]);

                if (loose.isValid()) {
                    return loose.format('YYYY-MM-DD');
                }
            }

            return raw;
        }

            $('#f22_date').daterangepicker(dateRangeSettings, function (start, end, label) {
                // ✅ Only one date selected, so use `start`
                $('#f22_date').val(start.format(moment_date_format));
                form_22_table.ajax.reload();
                if (typeof form_f22_list_table !== 'undefined') {
                    form_f22_list_table.ajax.reload();
                }
                if (typeof form_f22_list_table_stock_taking !== 'undefined') {
                    form_f22_list_table_stock_taking.ajax.reload();
                }
                fetchPumps();
            });

            // Optional: handle clear
            $('#f22_date').on('cancel.daterangepicker', function (ev, picker) {
                $('#f22_date').val('');
            });
            // Optional: default start value as today
            $('#f22_date').data('daterangepicker').setStartDate(moment());

            // Initialize Start Date Picker
            $('#start-date-range').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                autoApply: true,
                showCustomRangeLabel: false,
                ranges: {}, // 👈 disable range menu
                locale: {
                    format: moment_date_format
                }
            }, function (start) {
                // Immediately update input
                $('#start-date-range').val(start.format(moment_date_format));

                // Update END date picker's minDate and open it
                const endPicker = $('#end-date-range').data('daterangepicker');
                endPicker.minDate = start;
                endPicker.setStartDate(start);
                endPicker.setEndDate(start);

                // Auto open end date picker
                setTimeout(() => {
                    $('#end-date-range').trigger('click');
                }, 150);
            });

            // END DATE PICKER
            $('#end-date-range').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                autoApply: true,
                minDate: moment(), // Will be updated by start-date picker
                showCustomRangeLabel: false,
                ranges: {}, // 👈 disables predefined range list
                locale: {
                    format: moment_date_format
                }
            }, function (end) {
                $('#end-date-range').val(end.format(moment_date_format));
            });

            $('#apply-date-range').on('click', function () {
                const startDate = $('#start-date-range').val();
                const endDate = $('#end-date-range').val();
                if (startDate && endDate) {
                    $('#f22_date').val(
                        startDate.format(moment_date_format) + ' - ' + endDate.format(moment_date_format)
                    );
                    $('#f22DateRangeModal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            });

            fetchPumps();

            function fetchPumps() {
                let product_id = $('#f22_product_id').val();
                let location_id = $('#f22_location_id').val();
                let sub_category_id = $('#f22_sub_category_id').val();
                let date = $('#f22_date').val();
                $.ajax({
                    url: '/mpcs/fetch-pumps',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        product_id: product_id,
                        location_id: location_id,
                        sub_category_id: sub_category_id,
                        date: date
                    },
                    success: function (response) {
                        let html = "";

                        if (response.pumps.length > 0) {
                            // Group pumps by product name
                            let groupedPumps = {};

                            response.pumps.forEach(pump => {
                                if (!groupedPumps[pump.product_name]) {
                                    groupedPumps[pump.product_name] = [];
                                }
                                groupedPumps[pump.product_name].push(pump);
                            });
                            // Loop through grouped products
                            for (let product_name in groupedPumps) {
                                html += `
                    <div class="pump-section" style="width:100%;">
                        <h4>${product_name}</h4>
                        <table class="table table-bordered " style="border: none; width: 100%;">
                            <tr>
                                <th class="column-50 no-wrap">Pump Name</th>
                                <th>Current Meter</th>

                            </tr>`;

                                // Add pump rows for this product
                                groupedPumps[product_name].forEach(pump => {
                                    html += `
                            <tr>
                                <td>${pump.pump_name}</td>
                                <td><input type="text" class="form-control pump-meter-input" style="width:85px;" name="pump_meters[${pump.id}]" data-pump-id="${pump.id}" data-pump-name="${pump.pump_name}" data-product-name="${pump.product_name}" value="${pump.last_meter_reading}"></td>

                            </tr>`;
                                });

                                html += `</table></div>`;
                            }
                        } else {
                            html = `<p style="color: red;">No pumps found.</p>`; // Handle empty response
                        }

                        $(".pumps-container").html(html); // Insert HTML inside the container
                    },
                    error: function (xhr, status, error) {
                        console.error("Error fetching data:", error);
                        $(".pumps-container").html(
                            `<p style="color: red;">Failed to load pumps. Try again later.</p>`);
                    }
                });
            }


            $.ajax({
                url: '/mpcs/check-user-existence', // Adjust the route based on your setup
                type: 'GET',
                success: function (response) {
                    if (response.exists) {
                        $('#save_button').prop('Enabled', true); // Disable Save button
                    } else {
                        $('#save_button').prop('Disable', false); // Enable Save button
                    }
                },
                error: function () {
                    console.log('Error checking user existence');
                }
            });

            $(document).on('click', '.edit-button', function (e) {
                e.preventDefault(); // Prevent default action
                $('#save_button').prop('Disable', false); // Enable Save button
            });

            $('#f22_product_id').select2();
            $('#form_date_range').daterangepicker({
                ranges: ranges,
                autoUpdateInput: false,
                locale: {
                    format: moment_date_format,
                    cancelLabel: LANG.clear,
                    applyLabel: LANG.apply,
                    customRangeLabel: LANG.custom_range,
                },
            });


            // The controller preselects the first-created location. Avoid forcing the
            // first DOM option here because option 0 can be the Select2 placeholder (All).
            $(document).ready(function () {
                form_f22_list_table = $('#form_f22_list_table').DataTable({
                    processing: true,
                    serverSide: false,
                    /*
                     * IS2044: scrollX must be TRUE.
                     *
                     * This table has NINE columns. Without scrollX, DataTables
                     * constrains it to the container width and squeezes the
                     * columns until the last ones are clipped - the data is
                     * there with nowhere to go. The .table-responsive wrapper
                     * cannot help, because DataTables has already sized the
                     * table to fit inside it.
                     *
                     * With scrollX the columns take the width they need and the
                     * section scrolls sideways to reach them.
                     */
                    scrollX: true,
                    autoWidth: false,
                    order: [[0, 'desc']],
                    ajax: {
                        url: '/mpcs/get-form-f22-list',
                        data: function (d) {
                            /*
                             * IS-1933: do NOT feed the F22 ENTRY form's pickers into
                             * this list.
                             *
                             * #f22_location_id and #f22_date belong to the "F22 Form"
                             * entry tab, not to this list. Sending them meant the
                             * saved-forms list was silently filtered by controls the
                             * user cannot even see while looking at the list:
                             * #f22_location_id defaults to the first business location,
                             * so a form saved against any other location vanished from
                             * the list the moment the page reloaded after saving.
                             *
                             * This is the same defect IS1454 already fixed for the date
                             * picker ("List F22 Stock Taking must show all saved forms.
                             * Do not automatically filter the list by the F22 entry date
                             * picker"). The location half was left behind. Every other
                             * copy of this screen (PriceChanges, and the older
                             * stock_taking.blade.php) already has these two lines
                             * commented out - this was the odd one out.
                             *
                             * The server still restricts rows to the user's permitted
                             * locations, so nothing is exposed that was not before.
                             */
                        }
                    },
                    columns: [{
                        data: 'form_date',
                        name: 'form_date'
                    },
                        {
                            data: 'locations_name',
                            name: 'location'
                        },
                        {
                            data: 'form_no',
                            name: 'form_no'
                        },
                        {
                            data: 'stock_adjustment_no',
                            name: 'stock_adjustment_no',
                            className: 'text-right'
                        },
                        {
                            data: 'total_stock_lose_purchase',
                            name: 'total_stock_lose_purchase',
                            className: 'text-right'

                        },

                        {
                            data: 'total_stock_lose_sale',
                            name: 'total_stock_lose_sale',
                            className: 'text-right'
                        },

                        {
                            data: 'username',
                            name: 'username'
                        },
                        {
                            data: 'action',
                            name: 'action'
                        },

                    ],
                    fnDrawCallback: function (oSettings) {

                    },
                });

                $('<div id="f22_header_notice" style="text-align: center;">' +
                    '<p style="color: red; margin: 10px 0;">' +
                    'Total stock loss is displayed as a negative value, while stock gain is displayed as a positive value.' +
                    '</p></div>'
                ).insertBefore('#form_f22_list_table');

                form_f22_list_table_stock_taking = $('#form_f22_list_table_stock_taking').DataTable({
                    processing: true,
                    serverSide: false,
                    order: [
                        [3, 'desc']
                    ], // Ensure sorting by the first column (updated_at)
                    ajax: {
                        url: '/mpcs/get-form-f22-list_gain_loss',
                        data: function (d) {
                            // d.location_id = $('#f22_location_id').val();
                            // d.product_id = $('#f22_product_id').val();
                        }
                    },
                    columns: [{
                        data: 'action',
                        name: 'action'
                    },
                        {
                            data: 'updated_at',
                            name: 'updated_at'
                        },
                        {
                            data: 'stock_loss_account',
                            name: 'stock_loss_account',
                            className: 'text-right'
                        },
                        {
                            data: 'stock_gain_account',
                            name: 'stock_gain_account',
                            className: 'text-right'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'added_user',
                            name: 'added_user'
                        },


                    ],
                    fnDrawCallback: function (oSettings) {

                    },
                });

                $('#f22_product_id, #f22_location_id').change(function () {
                    form_22_table.ajax.reload();
                    fetchPumps();
                    if ($('#f22_location_id').val() !== '' && $('#f22_location_id').val() !== undefined) {
                        $('.f22_location_name').text($('#f22_location_id :selected').text());
                        $('#f22_location_name').val($('#f22_location_id :selected').text());
                    } else {
                        $('.f22_location_name').text('All');
                        $('#f22_location_name').val('All');
                    }
                });
                $('#form_22_table_search_input').on('keyup change', function () {
                    form_22_table.search(this.value).draw();
                });

                $('#f22_sub_category_id').on('change', function() {
                    form_22_table.ajax.reload();
                    fetchPumps();
                    var subCategoryId = $(this).val();
                    var productSelect = $('#f22_product_id');

                    // Reset dropdown
                    productSelect.empty().append('<option value="">All</option>');

                    if (subCategoryId) {
                        $.ajax({
                            url: '/mpcs/get-products-by-subcategory/' + subCategoryId,
                            type: 'GET',
                            success: function(data) {
                                $.each(data, function(id, name) {
                                    productSelect.append('<option value="' + id + '">' + name + '</option>');
                                });
                                productSelect.trigger('change');
                            },
                            error: function() {
                                alert('Unable to load products for this sub-category');
                            }
                        });
                    }
                });


                var ppage_totals = [];
                var spage_totals = [];
                var pre_gppage_totals = [];
                var pre_gspage_totals = [];

                function parseF22Number(value) {
                    if (value === null || value === undefined || value === '') {
                        return 0;
                    }

                    value = value.toString().replace(/,/g, '').trim();
                    value = parseFloat(value);

                    return isNaN(value) ? 0 : value;
                }

                function getRowKeyFromTr(tr) {
                    const stockInputName = tr.find('input.stock_count').attr('name') || '';
                    const match = stockInputName.match(/^f22\[(.+?)\]\[stock_count\]$/);
                    return match ? match[1] : null;
                }

                function parseFieldFromHtml(html, selector, attrName) {
                    const $el = $('<div>' + (html || '') + '</div>').find(selector).first();
                    if (!$el.length) {
                        return '';
                    }
                    if (attrName) {
                        return $el.attr(attrName) || '';
                    }
                    if ($el.is('input')) {
                        return $el.val() || '';
                    }
                    return ($el.text() || '').trim();
                }

                function buildFullF22TableData() {
                    const rows = [];

                    form_22_table.rows().every(function () {
                        const rowData = this.data() || {};
                        const rowKeyMatch = (parseFieldFromHtml(rowData.product, 'input[type="hidden"][name^="f22["]', 'name') || '')
                            .match(/^f22\[(.+?)\]\[/);
                        const rowKey = rowKeyMatch ? rowKeyMatch[1] : null;
                        if (!rowKey) {
                            return;
                        }

                        const currentStock = parseF22Number(parseFieldFromHtml(rowData.current_stock, '.current_stock', 'data-orig-value'));
                        const unitPurchasePrice = parseF22Number(parseFieldFromHtml(rowData.unit_purchase_price, '.unit_purchase_price', 'data-orig-value'));
                        const unitSalePrice = parseF22Number(parseFieldFromHtml(rowData.unit_sale_price, '.unit_sale_price', 'data-orig-value'));

                        const edited = form22EditedValues[rowKey] || {};
                        const stockCount = parseF22Number(
                            edited.stock_count !== undefined ? edited.stock_count : parseFieldFromHtml(rowData.stock_count, 'input.stock_count')
                        );
                        const totalPurchase = parseF22Number(
                            edited.total_purchase_price !== undefined ? edited.total_purchase_price : (stockCount * unitPurchasePrice)
                        );
                        const totalSale = parseF22Number(
                            edited.total_sale_price !== undefined ? edited.total_sale_price : (stockCount * unitSalePrice)
                        );
                        const qtyDiff = parseF22Number(
                            edited.qty_difference !== undefined ? edited.qty_difference : parseFieldFromHtml(rowData.qty_difference, 'input.qty_difference')
                        );

                        rows.push({
                            ['f22[' + rowKey + '][sku]']: parseFieldFromHtml(rowData.sku, 'input[type="hidden"]', 'value'),
                            ['f22[' + rowKey + '][product]']: parseFieldFromHtml(rowData.product, 'input[type="hidden"][name$="[product]"]', 'value'),
                            ['f22[' + rowKey + '][variation_id]']: parseFieldFromHtml(rowData.product, 'input[type="hidden"][name$="[variation_id]"]', 'value'),
                            ['f22[' + rowKey + '][book_no]']: '',
                            ['f22[' + rowKey + '][current_stock]']: currentStock,
                            ['f22[' + rowKey + '][stock_count]']: stockCount,
                            ['f22[' + rowKey + '][unit_purchase_price]']: unitPurchasePrice,
                            ['f22[' + rowKey + '][total_purchase_price]']: totalPurchase,
                            ['f22[' + rowKey + '][unit_sale_price]']: unitSalePrice,
                            ['f22[' + rowKey + '][total_sale_price]']: totalSale,
                            ['f22[' + rowKey + '][qty_difference]']: qtyDiff
                        });
                    });

                    return rows;
                }

                //form_22_table
                form_22_table = $('#form_22_table').DataTable({
                    dom: 'lrtip',
                    processing: true,
                    serverSide: false,
                    pagingType: 'simple', // ðŸ‘ˆ Only "Previous" and "Next"
                    lengthChange: false,
                    scrollY: '50vh', // Adjust height as needed
                    scrollX: true, // Enable horizontal scrolling
                    scrollCollapse: true, // Allow table to collapse if content is smaller
                    fixedHeader: false,
                    pageLength: {{!empty($settings->F22_no_of_product_per_page) ? $settings->F22_no_of_product_per_page : 25}},
                    columnDefs: [
                        {
                            "targets": 0,
                            "orderable": false,
                            "width": '5%',
                        },
                        {
                            "targets": 1,
                            "width": '5%',
                        },
                        {
                            "targets": 2,
                            "width": '20%',
                        },
                        {
                            "targets": 3,
                            "width": '10%',
                        },
                        {
                            "targets": 4,
                            "width": '10%',
                        },
                        {
                            "targets": 5,
                            "width": '10%',
                        },
                        {
                            "targets": 6,
                            "width": '10%',
                        },
                        {
                            "targets": 7,
                            "width": '10%',
                        },
                        {
                            "targets": 8,
                            "width": '10%',
                        },
                        {
                            "targets": 9,
                            "width": '10%',
                        },

                    ],
                    ajax: {
                        url: '/mpcs/get-form-f22',
                        data: function (d) {
                            d.location_id = $('#f22_location_id').val();
                            d.sub_category_id = $('#f22_sub_category_id').val();
                            d.product_id = $('#f22_product_id').val();
                            d.date = $('#f22_date').val();
                        }
                    },
                    columns: [
                        {data: 'DT_Row_Index', name: 'DT_Row_Index'},
                        {data: 'sku', name: 'sku'},
                        {data: 'product', name: 'product'},
                        {data: 'current_stock', name: 'current_stock', className: 'text-right'},
                        {data: 'stock_count', name: 'stock_count', className: 'text-right'},
                        {data: 'unit_purchase_price', name: 'unit_purchase_price', className: 'text-right'},
                        {data: 'total_purchase_price', name: 'total_purchase_price', className: 'text-right'},
                        {data: 'unit_sale_price', name: 'unit_sale_price', className: 'text-right'},
                        {data: 'total_sale_price', name: 'total_sale_price', className: 'text-right'},
                        {data: 'qty_difference', name: 'qty_difference', className: 'text-right'},
                    ],
                    fnDrawCallback: function (oSettings) {
                        var api = this.api();
                        var currency_precision = __currency_precision;
                        api.rows({ page: 'current' }).every(function () {
                            var tr = $(this.node());
                            if (!tr.length) return;
                            var rowData = this.data();
                            var rowKeyMatch = (parseFieldFromHtml(rowData.product, 'input[type="hidden"][name^="f22["]', 'name') || '')
                                .match(/^f22\[(.+?)\]\[/);
                            var rowKey = rowKeyMatch ? rowKeyMatch[1] : null;
                            if (rowKey && form22EditedValues[rowKey] !== undefined) {
                                var edited = form22EditedValues[rowKey];
                                tr.find('input.stock_count').val(edited.stock_count);
                                tr.find('.total_purchase_price').text(__number_f(edited.total_purchase_price, false, false, currency_precision));
                                tr.find('.total_purchase_price').data('orig-value', edited.total_purchase_price);
                                tr.find('input.total_purchase_price_input').val(edited.total_purchase_price);
                                tr.find('.total_sale_price').text(__number_f(edited.total_sale_price, false, false, currency_precision));
                                tr.find('.total_sale_price').data('orig-value', edited.total_sale_price);
                                tr.find('input.total_sale_price_input').val(edited.total_sale_price);
                                tr.find('.qty_difference').val(edited.qty_difference);
                            }
                        });
                        calculateTotals(api);
                    },
                    "initComplete": function (settings, json) {
                        var api = this.api();
                        var table_info = api.page.info(); //get table info
                        for (i = 0; i < table_info.pages; i++) {
                            ppage_totals[i] = 0.00;
                            spage_totals[i] = 0.00;
                            pre_gppage_totals[i] = 0.00;
                            pre_gspage_totals[i] = 0.00;

                        }
                        // $('#mpcs-form-current-stock').css('width', '42px');
                        // $('#mpcs-form-stock-count').css('width', '42px');
                        // $('#mpcs-form-total-purchase-price').css('width', '50px');
                        // $('#mpcs-form-unit-purchase-price').css('width', '50px');
                        // $('#mpcs-form-unit-sale-price').css('width', '50px');
                        // $('#mpcs-form-total-sale-price').css('width', '50px');
                    },


                    //     footerCallback: function(row, data, start, end, display) {
                    //     var api = this.api();

                    //     let pagePurchase = 0;
                    //     let pageSale = 0;
                    //     let grandPurchase = 0;
                    //     let grandSale = 0;

                    //     data.forEach(function(rowData, index) {
                    //         const purchase = parseFloat(rowData.total_purchase_price) || 0;
                    //         const sale = parseFloat(rowData.total_sale_price) || 0;

                    //         grandPurchase += purchase;
                    //         grandSale += sale;

                    //         if (index >= start && index < end) {
                    //             pagePurchase += purchase;
                    //             pageSale += sale;
                    //         }
                    //     });

                    //     $('#footer_total_purchase_price').text(__number_f(pagePurchase));
                    //     $('#footer_total_sale_price').text(__number_f(pageSale));
                    //     $('#grand_total_purchase_price').text(__number_f(grandPurchase));
                    //     $('#grand_total_sale_price').text(__number_f(grandSale));
                    //     $('#pre_total_purchase_price').text(__number_f(grandPurchase - pagePurchase));
                    //     $('#pre_total_sale_price').text(__number_f(grandSale - pageSale));

                    //     // Update hidden form fields too
                    //     $('#purchase_price1, #purchase_price3').val(pagePurchase);
                    //     $('#sales_price1, #sales_price3').val(pageSale);
                    // },

                    drawCallback: function () {
                        __currency_convert_recursively($('#form_22_table'));
                    }





                    // fnDrawCallback: function(oSettings) {
                    //     __currency_convert_recursively($('#form_22_table'));
                    // }
                });
                // Fix header alignment on resize
                $(window).on('resize', function () {
                    form_22_table.columns.adjust();
                });

                // Fix alignment on draw
                form_22_table.on('draw', function () {
                    form_22_table.columns.adjust();
                });

                $(document).on('keyup', '.stock_count', function () {
                    let tr = $(this).closest('tr');
                    let rowIndex = getRowKeyFromTr(tr);
                    if (!rowIndex) {
                        return;
                    }

                    let unit_purchase_price = parseF22Number(tr.find('.unit_purchase_price').data('orig-value'));
                    let unit_sale_price = parseF22Number(tr.find('.unit_sale_price').data('orig-value'));
                    let current_stock = parseF22Number(tr.find('.current_stock').data('orig-value'));
                    let stock = parseF22Number($(this).val());

                    let total_purchase_value = unit_purchase_price * stock;
                    let total_sale_value = unit_sale_price * stock;
                    let qty_difference = stock - current_stock;

                    tr.find('.total_purchase_price').text(__number_f(total_purchase_value, false, false, __currency_precision));
                    tr.find('.total_purchase_price').data('orig-value', total_purchase_value);
                    tr.find('input.total_purchase_price_input').val(total_purchase_value);
                    tr.find('.total_sale_price').text(__number_f(total_sale_value, false, false, __currency_precision));
                    tr.find('.total_sale_price').data('orig-value', total_sale_value);
                    tr.find('input.total_sale_price_input').val(total_sale_value);
                    tr.find('.qty_difference').val(qty_difference);

                    // Store changes
                    form22EditedValues[rowIndex] = {
                        stock_count: stock,
                        total_purchase_price: total_purchase_value,
                        total_sale_price: total_sale_value,
                        qty_difference: qty_difference
                    };

                    calculateTotals();
                });


                function calculateTotals(apiInstance) {
                    const api = (apiInstance && typeof apiInstance === 'object') ? apiInstance : $('#form_22_table').DataTable();
                    if (!api) return;
                    const info = api.page.info();
                    if (!info) return;
                    const start = info.start;
                    const end = info.end;

                    let pagePurchase = 0;
                    let pageSale = 0;
                    let prevPurchase = 0;
                    let prevSale = 0;

                    api.rows({ search: 'applied', order: 'applied' }).every(function (rowIdx, tableLoop, containerLoop) {
                        const rowData = this.data();
                        const rowKeyMatch = (parseFieldFromHtml(rowData.product, 'input[type="hidden"][name^="f22["]', 'name') || '')
                            .match(/^f22\[(.+?)\]\[/);
                        const rowKey = rowKeyMatch ? rowKeyMatch[1] : null;
                        
                        const unitPurchasePrice = parseF22Number(parseFieldFromHtml(rowData.unit_purchase_price, '.unit_purchase_price', 'data-orig-value'));
                        const unitSalePrice = parseF22Number(parseFieldFromHtml(rowData.unit_sale_price, '.unit_sale_price', 'data-orig-value'));

                        const edited = rowKey ? (form22EditedValues[rowKey] || {}) : {};
                        
                        let stockCount;
                        if (edited.stock_count !== undefined) {
                            stockCount = parseF22Number(edited.stock_count);
                        } else {
                            stockCount = parseF22Number(parseFieldFromHtml(rowData.stock_count, 'input.stock_count'));
                        }

                        const purchase = edited.total_purchase_price !== undefined ? parseF22Number(edited.total_purchase_price) : (stockCount * unitPurchasePrice);
                        const sale = edited.total_sale_price !== undefined ? parseF22Number(edited.total_sale_price) : (stockCount * unitSalePrice);

                        if (containerLoop >= start && containerLoop < end) {
                            pagePurchase += purchase;
                            pageSale += sale;
                        } else if (containerLoop < start) {
                            prevPurchase += purchase;
                            prevSale += sale;
                        }
                    });

                    const grandPurchase = prevPurchase + pagePurchase;
                    const grandSale = prevSale + pageSale;

                    $('#footer_total_purchase_price').text(__number_f(pagePurchase, false, false, __currency_precision));
                    $('#footer_total_sale_price').text(__number_f(pageSale, false, false, __currency_precision));

                    $('#pre_total_purchase_price').text(__number_f(prevPurchase, false, false, __currency_precision));
                    $('#pre_total_sale_price').text(__number_f(prevSale, false, false, __currency_precision));

                    $('#grand_total_purchase_price').text(__number_f(grandPurchase, false, false, __currency_precision));
                    $('#grand_total_sale_price').text(__number_f(grandSale, false, false, __currency_precision));

                    // Update hidden form fields for submit
                    $('#purchase_price1').val(pagePurchase);
                    $('#purchase_price3').val(pagePurchase);
                    $('#sales_price1').val(pageSale);
                    $('#sales_price3').val(pageSale);
                }

                $('#form_22_table').on('page.dt', function () {
                    calculateTotals(1);
                });

                // Define fetchLastVerifiedPumps function BEFORE the DataTable initialization
                function fetchLastVerifiedPumps() {
                    console.log('Fetching last verified pumps...');
                    // Ensure the container exists
                    if ($(".lf-pumps-container").length === 0) {
                        console.warn('Pumps container not found, skipping fetch');
                        return;
                    }
                    
                    $.ajax({
                        url: '/mpcs/get-last-verified-form-f22-header',
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            console.log('Header response:', response);
                            if (response && response.pump_meters && Array.isArray(response.pump_meters) && response.pump_meters.length > 0) {
                                // Use saved pump meter data
                                let html = "";
                                let groupedPumps = {};
                                
                                response.pump_meters.forEach(pump => {
                                    const productName = pump.product_name || 'Unknown Product';
                                    if (!groupedPumps[productName]) {
                                        groupedPumps[productName] = [];
                                    }
                                    groupedPumps[productName].push(pump);
                                });
                                
                                for (let product_name in groupedPumps) {
                                    html += `
                                    <div class="pump-section" style="width:100%;">
                                        <h4>${product_name || 'Unknown Product'}</h4>
                                        <table class="table table-bordered" style="border: none; width: 100%;">
                                            <tr>
                                                <th class="column-50 no-wrap">Pump Name</th>
                                                <th>Meter Reading</th>
                                            </tr>`;
                                    
                                    groupedPumps[product_name].forEach(pump => {
                                        const pumpName = pump.pump_name || 'N/A';
                                        const meterReading = pump.meter_reading ? parseFloat(pump.meter_reading).toFixed(3) : '0.000';
                                        html += `
                                            <tr>
                                                <td>${pumpName}</td>
                                                <td>${meterReading}</td>
                                            </tr>`;
                                    });
                                    
                                    html += `</table></div>`;
                                }
                                
                                console.log('Setting HTML:', html);
                                $(".lf-pumps-container").html(html);
                            } else {
                                console.log('No pump meters found in response:', response);
                                if (response && response.header) {
                                    $(".lf-pumps-container").html(`<p style="color: #666; font-style: italic;">No pump meter data saved for this form.</p>`);
                                } else {
                                    $(".lf-pumps-container").html(`<p style="color: #666; font-style: italic;">No form data available.</p>`);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Header fetch error:', error, xhr);
                            $(".lf-pumps-container").html(`<p style="color: red;">Failed to load pump data. Error: ${error}</p>`);
                        }
                    });
                }

                //form_22_table
                var lf_ppage_totals = [];
                var lf_spage_totals = [];
                var lf_pre_gppage_totals = [];
                var lf_pre_gspage_totals = [];
                form_22_last_verified_table = $('#form_22_last_verified_table').DataTable({
                    processing: true,
                    serverSide: false,
                    // IS2044: TWELVE columns here - same reasoning as the list
                    // table above.
                    scrollX: true,
                    autoWidth: false,
                    pageLength: newPageLength,
                    buttons: [
                        'csv', 
                        'excel', 
                        'colvis'
                    ],
                    ajax: {
                        url: '/mpcs/get-last-verified-form-f22',
                        data: function (d) {
                        }
                    },
                    "columnDefs": [{
                        "width": "2%",
                        "targets": 2
                    }],
                    columns: [{
                        data: 'DT_Row_Index',
                        name: 'DT_Row_Index'
                    },
                        {
                            data: 'form_date',
                            name: 'form_date'
                        },
                        {
                            data: 'sku',
                            name: 'sku'
                        },
                        {
                            data: 'product',
                            name: 'product'
                        },
                        {
                            data: 'current_stock',
                            name: 'current_stock'
                        },
                        {
                            data: 'stock_count',
                            name: 'stock_count'
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
                            data: 'qty_difference',
                            name: 'qty_difference'
                        }


                    ],
                    fnDrawCallback: function (oSettings) {

                    },
                    initComplete: function (settings, json) {
                        var api = this.api();
                        var table_info = api.page.info();
                        for (i = 0; i < table_info.pages; i++) {
                            lf_ppage_totals[i] = 0.00;
                            lf_spage_totals[i] = 0.00;
                            lf_pre_gppage_totals[i] = 0.00;
                            lf_pre_gspage_totals[i] = 0.00;
                        }
                        lastFormCalculateTotals(api);
                        
                        // Fetch pumps after table is initialized
                        fetchLastVerifiedPumps();
                    },
                    drawCallback: function () {
                        lastFormCalculateTotals(this.api());
                    }
                });
                
                // Fetch pumps when tab is shown (Bootstrap tab event)
                $('a[href="#f22_last_verified_stock_tab"]').on('shown.bs.tab', function() {
                    console.log('Tab shown - fetching pumps...');
                    setTimeout(function() {
                        fetchLastVerifiedPumps();
                    }, 200);
                });
                
                // Also fetch pumps when tab link is clicked
                $('a[href="#f22_last_verified_stock_tab"]').on('click', function() {
                    console.log('Tab clicked - will fetch pumps when shown');
                });
                
                // Fetch pumps immediately if the tab is already active when page loads
                if ($('#f22_last_verified_stock_tab').hasClass('active')) {
                    setTimeout(function() {
                        fetchLastVerifiedPumps();
                    }, 500);
                }

                function sum_table_col(table, class_name) {
                    var total = 0;
                    table.find('tbody tr').each(function () {
                        var td = $(this).find('.' + class_name);
                        var value = td.data('orig-value');
                        total += parseF22Number(value);
                    });
                    return total;
                }


                // function sum_table_col(table, class_name) {
                //     var total = 0;
                //     table.find('tbody tr').each(function () {
                //         var value = __read_number($(this).find('.' + class_name));
                //         if (!isNaN(value)) total += value;
                //     });
                //     return total;
                // }

                function lastFormCalculateTotals(apiInstance) {
                    // Recalculate totals for "Last verified stock" based on DataTables row data (all pages).
                    var api = (apiInstance && typeof apiInstance === 'object') ? apiInstance : $('#form_22_last_verified_table').DataTable();
                    if (!api) return;
                    var info = api.page.info();
                    if (!info) return;
                    var start = info.start;
                    var end = info.end;

                    var pagePurchase = 0;
                    var pageSale = 0;
                    var prevPurchase = 0;
                    var prevSale = 0;

                    // Helper to extract numeric value from the HTML string in the row data
                    function extractValue(html) {
                        if (html === null || html === undefined) return 0;
                        if (typeof html === 'number') return html;
                        html = html.toString();
                        // Prefer data-orig-value if present
                        var match = html.match(/data-orig-value="([^"]+)"/);
                        var valStr = match ? match[1] : html.replace(/<[^>]*>/g, '');
                        return parseF22Number(valStr);
                    }

                    api.rows({ search: 'applied', order: 'applied' }).every(function (rowIdx, tableLoop, containerLoop) {
                        const rowData = this.data();
                        var purchaseHtml = rowData.total_purchase_price || rowData[7] || '';
                        var saleHtml = rowData.total_sale_price || rowData[9] || '';

                        var purchase = extractValue(purchaseHtml);
                        var sale = extractValue(saleHtml);

                        if (containerLoop >= start && containerLoop < end) {
                            pagePurchase += purchase;
                            pageSale += sale;
                        } else if (containerLoop < start) {
                            prevPurchase += purchase;
                            prevSale += sale;
                        }
                    });

                    var grandPurchase = prevPurchase + pagePurchase;
                    var grandSale = prevSale + pageSale;

                    $('#lf_footer_total_purchase_price').text(__number_f(pagePurchase, false, false, __currency_precision));
                    $('#lf_footer_total_sale_price').text(__number_f(pageSale, false, false, __currency_precision));

                    $('#lf_pre_total_purchase_price').text(__number_f(prevPurchase, false, false, __currency_precision));
                    $('#lf_pre_total_sale_price').text(__number_f(prevSale, false, false, __currency_precision));

                    $('#lf_grand_total_purchase_price').text(__number_f(grandPurchase, false, false, __currency_precision));
                    $('#lf_grand_total_sale_price').text(__number_f(grandSale, false, false, __currency_precision));
                }

                $('#form_22_last_verified_table').on('page.dt', function () {
                    lastFormCalculateTotals(1);
                });
                // $('#form_22_last_verified_table').on('init.dt', function() {
                //     lastFormCalculateTotals();
                // }).dataTable();

                // function lastFormCalculateTotals(page_change = null) {
                //     let lf_pgrand = 0.00;
                //     let lf_sgrand = 0.00;
                //     let total_purchase_amount = sum_table_col($('#form_22_last_verified_table'),
                //         'lf_total_purchase_price');
                //     let total_sales_amount = sum_table_col($('#form_22_last_verified_table'), 'lf_total_sale_price');

                //     let info = form_22_last_verified_table.page.info(); //get table info

                //     if (page_change == 1) {
                //         lf_ppage_totals[info.page] = total_purchase_amount;
                //         lf_spage_totals[info.page] = total_sales_amount;
                //         if (info.page == 0) {
                //             lf_pgrand = lf_ppage_totals[info.page];
                //             lf_sgrand = lf_spage_totals[info.page];
                //         } else {
                //             lf_pgrand = lf_ppage_totals[info.page] + lf_pre_gppage_totals[info.page - 1];
                //             lf_sgrand = lf_spage_totals[info.page] + lf_pre_gspage_totals[info.page - 1];
                //         }

                //         lf_pre_gppage_totals[info.page] = lf_pgrand;
                //         lf_pre_gspage_totals[info.page] = lf_sgrand;

                //     } else {
                //         lf_ppage_totals[info.page] = total_purchase_amount;
                //         lf_spage_totals[info.page] = total_sales_amount;
                //         if (info.page == 0) {
                //             lf_pgrand = lf_ppage_totals[info.page];
                //             lf_sgrand = lf_spage_totals[info.page];
                //         } else {
                //             lf_pgrand = lf_ppage_totals[info.page] + lf_pre_gppage_totals[info.page - 1];
                //             lf_sgrand = lf_spage_totals[info.page] + lf_pre_gspage_totals[info.page - 1];
                //         }

                //         lf_pre_gppage_totals[info.page] = lf_pgrand;
                //         lf_pre_gspage_totals[info.page] = lf_sgrand;
                //     }


                //     $('#lf_footer_total_purchase_price').text(__number_f(lf_ppage_totals[info.page], false, false,
                //         __currency_precision));
                //     $('#lf_footer_total_sale_price').text(__number_f(lf_spage_totals[info.page], false, false,
                //         __currency_precision));
                //     $('#lf_pre_total_purchase_price').text(__number_f(lf_pre_gppage_totals[info.page - 1], false, false,
                //         __currency_precision));
                //     $('#lf_pre_total_sale_price').text(__number_f(lf_pre_gspage_totals[info.page - 1], false, false,
                //         __currency_precision));
                //     $('#lf_grand_total_purchase_price').text(__number_f(lf_pgrand, false, false, __currency_precision));
                //     $('#lf_grand_total_sale_price').text(__number_f(lf_sgrand, false, false, __currency_precision));
                // }


                $('#f22_save_and_print').click(function (e) {
                    e.preventDefault();
                    const button = $(this);
                    button.attr('disabled', 'disabled');

                    // CSRF token for AJAX POST
                    const token = $('meta[name="csrf-token"]').attr('content');

                    // Single date picker -> take the field value directly
                    const firstDate = f22SelectedDateIso();

                    // Build table data from ALL DataTable rows, not only visible DOM rows.
                    // This ensures saved/view/print contains the full list for the selected date.
                    const tableData = buildFullF22TableData();

                    // Get form data (includes _token as a field but we also send header param explicitly)
                    const formData = $('#f22_form').serializeArray();
                    
                    // Collect pump meter data
                    const pumpMeters = [];
                    $('.pump-meter-input').each(function() {
                        const input = $(this);
                        pumpMeters.push({
                            pump_id: input.data('pump-id'),
                            pump_name: input.data('pump-name'),
                            product_name: input.data('product-name'),
                            meter_reading: input.val()
                        });
                    });

                    // Prepare data for sending
                    const postData = {
                        table_data: JSON.stringify(tableData),
                        form_data: JSON.stringify(formData),
                        pump_meters: JSON.stringify(pumpMeters),
                        date_value: firstDate,
                        _token: token,
                        product_id: $('#f22_product_id').val(),
                        location_id: $('#f22_location_id').val(),
                        sub_category_id: $('#f22_sub_category_id').val()
                    };

                    $.ajax({
                        method: 'post',
                        url: '/mpcs/save-form-f22',
                        data: postData,
                        headers: token ? {'X-CSRF-TOKEN': token} : {},
                        success: function (result) {
                            // If server returned HTML, proceed to print. If JSON error {success:0}, handle below
                            try {
                                if (typeof result === 'object' && result.success === 0) {
                                    toastr.error(result.msg || 'Failed to save.');
                                    button.removeAttr('disabled');
                                    return;
                                }
                            } catch (e) { /* ignore */
                            }

                            printPage(result);

                            /*
                             * MA-002 (IS-1904 #3): show the saved form afterwards.
                             *
                             * The save worked and the print opened, but nothing ever
                             * refreshed the page - so the "F 22 Last Verified Stock"
                             * tab still showed whatever was there when the page was
                             * first loaded, and the form just saved was nowhere to be
                             * seen.
                             *
                             * That tab is rendered SERVER-SIDE from $last_form_no, so
                             * it cannot update itself; the page has to be reloaded for
                             * it to pick up the new form. The hash makes it open on
                             * that tab rather than back on the blank entry form.
                             *
                             * The delay lets the print window open first - reloading
                             * immediately can cancel it in some browsers.
                             */
                            setTimeout(function () {
                                window.location.href = window.location.pathname
                                    + '#f22_last_verified_stock_tab';
                                window.location.reload();
                            }, 1200);
                        },
                        error: function (xhr) {
                            button.removeAttr('disabled');
                            const msg = xhr && xhr.responseText ? xhr.responseText : 'Unexpected error';
                            toastr.error('Error saving data: ' + msg);
                        }
                    });
                });
                $('#f22_print').click(function (e) {
                    e.preventDefault();
                    const token = $('meta[name="csrf-token"]').attr('content');
                    const firstDate = f22SelectedDateIso();
                    const tableData = buildFullF22TableData();
                    const formData = $('#f22_form').serializeArray();
                    $.ajax({
                        method: 'post',
                        url: '/mpcs/print-form-f22',
                        headers: token ? {'X-CSRF-TOKEN': token} : {},
                        data: {
                            _token: token,
                            table_data: JSON.stringify(tableData),
                            form_data: JSON.stringify(formData),
                            data: form_22_table.$('input, select').serialize() + '&' + $('#f22_form').serialize(),
                            date_value: firstDate,
                            product_id: $('#f22_product_id').val(),
                            location_id: $('#f22_location_id').val(),
                            sub_category_id: $('#f22_sub_category_id').val()
                        },
                        success: function (result) {
                            if (typeof result === 'object' && result.success == 0) {
                                toastr.error(result.msg);
                                return false;
                            }
                            onlyPrintPage(result);
                        },
                        error: function (xhr) {
                            const msg = xhr && xhr.responseText ? xhr.responseText : 'Unexpected error';
                            toastr.error('Error: ' + msg);
                        }
                    });
                });
                $('#lf_f22_print').click(function (e) {
                    e.preventDefault();
                    $.ajax({
                        method: 'post',
                        url: '/mpcs/print-form-f22',
                        data: {
                            data: form_22_last_verified_table.$('input, select').serialize() + $(
                                '#lf_f22_form').serialize()
                        },
                        success: function (result) {
                            if (result.success == 0) {
                                toastr.error(result.msg);

                                return false;
                            }
                            printPage(result);

                        },
                    });
                });
                $(document).on('click', '.reprint_form', function (e) {
                    e.preventDefault();
                    href = $(this).data('href');

                    $.ajax({
                        method: 'get',
                        url: href,
                        data: {},
                        success: function (result) {
                            if (result.success == 0) {
                                toastr.error(result.msg);

                                return false;
                            }
                            printPage(result, 'list');

                        },
                    });
                });

            });


            function printPage(content, type = null) {
                var w = window.open('', '_blank');
                if (w) {
                    setTimeout(function() {
                        try {
                            w.document.open();
                            w.document.write(content);
                            w.document.close();
                            w.document.title = '';
                            w.focus();
                            w.print();
                        } catch(e) {}
                    }, 800);
                } else {
                    toastr.warning('Please allow popups for this site to enable printing.');
                }
                if (type == 'list') {
                    setTimeout(function() {
                        window.location.href = "{{ URL::to('/') }}/mpcs/F22_stock_taking#list_f22_stock_taking_tab";
                    }, 1500);
                } else {
                    setTimeout(function() {
                        window.location.href = "{{ URL::to('/') }}/mpcs/F22_stock_taking";
                    }, 1500);
                }
            }

            function onlyPrintPage(content) {
                var w = window.open('', '_blank');
                if (w) {
                    setTimeout(function() {
                        try {
                            w.document.open();
                            w.document.write(content);
                            w.document.close();
                            w.document.title = '';
                            w.focus();
                            w.print();
                        } catch(e) {}
                    }, 800);
                } else {
                    toastr.warning('Please allow popups for this site to enable printing.');
                }
                return false;
            }
        });

    </script>
@endsection
