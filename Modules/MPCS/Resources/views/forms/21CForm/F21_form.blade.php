<?php
if(!isset($is_ajax)){
    ?>
{{-- @extends('layouts.app') --}}
@extends($layout)
@section('title', __('mpcs::lang.F21_form'))

@section('content')
<!-- Main content -->
<section class="content">
    <?php
}
?>


    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">FORM F21C</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">F21C</a></li>
                        <li><span>Last Record</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs" data-mpcs-tabs>
                @php
                    // Keep tab links and tab panes under the same permissions.
                    // The old code used different permission names/casing, which could
                    // leave the Settings pane active when Details was clicked.
                    $canViewF21cDetails = auth()->user()->can('f16a_form')
                        || auth()->user()->can('f21c_form')
                        || auth()->user()->can('F21_form');
                    $canViewF21cSettings = auth()->user()->can('f21c_form')
                        || auth()->user()->can('F21_form');
                    $detailsIsDefault = $canViewF21cDetails;
                @endphp

                <ul class="nav nav-tabs" id="f21c_page_tabs" role="tablist">
                    @if ($canViewF21cDetails)
                    <li class="{{ $detailsIsDefault ? 'active' : '' }}" role="presentation">
                        <a href="#f21c_form_details_tab"
                           class="f21c-page-tab"
                           data-toggle="tab"
                           role="tab"
                           aria-controls="f21c_form_details_tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.21_c_form_details')</strong>
                        </a>
                    </li>
                    @endif
                    @if ($canViewF21cSettings)
                    <li class="{{ !$detailsIsDefault ? 'active' : '' }}" role="presentation">
                        <a href="#f21c_form_settings_tab"
                           class="f21c-page-tab"
                           data-toggle="tab"
                           role="tab"
                           aria-controls="f21c_form_settings_tab">
                            <i class="fa fa-cog"></i> <strong>@lang('mpcs::lang.21_c_form_settings')</strong>
                        </a>
                    </li>
                    @endif
                </ul>
                <div class="tab-content" id="f21c_page_tab_content">
                    @if ($canViewF21cDetails)
                    <div class="tab-pane fade {{ $detailsIsDefault ? 'in active' : '' }}" id="f21c_form_details_tab" role="tabpanel">
                        @include('mpcs::forms.21CForm.21c_form')
                    </div>
                    @endif
                    @if ($canViewF21cSettings)
                    <div class="tab-pane fade {{ !$detailsIsDefault ? 'in active' : '' }}" id="f21c_form_settings_tab" role="tabpanel">
                        @include('mpcs::forms.21CForm.list_f21c')
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>


    @if (empty($is_ajax))
</section>
<!-- /.content -->
@endsection
@section('javascript')
    @include('mpcs::partials.safe_tabs')
@endif
<script type="text/javascript">
    // Add CSS for right-aligned input fields
    const style = document.createElement('style');
    style.textContent = `
            .rows input[type="number"], 
            .rows input[type="text"] {
                text-align: right !important;
                direction: ltr;
            }
            input[readonly] {
                background-color: #f8f9fa;
                font-weight: bold;
            }
            .table-responsive input {
                text-align: right !important;
            }
        `;
    document.head.appendChild(style);

    /**
     * Updates only the date display in the header when the date picker changes.
     * Form No is intentionally NOT updated here — it must come from the server
     * via get_21_c_form_all_query() so it always reflects the correct settings
     * record for the selected date.
     */
    function updateDateDisplayOnly(selectedDateStr) {
        $('#openingdate').text('Date: ' + selectedDateStr);
        // Show loading state for form number until AJAX responds
        $('#formno').text('Form No: ...');
        $('input[name="formnovalue"]').val('');
    }

    $(document).ready(function () {
        // Explicitly switch the F21C tab and pane. This fallback works even
        // when another global tab script interferes with Bootstrap tabs.
        $(document).off('click.f21cTabs', '#f21c_page_tabs a[data-toggle="tab"]')
            .on('click.f21cTabs', '#f21c_page_tabs a[data-toggle="tab"]', function (event) {
                event.preventDefault();

                var targetSelector = $(this).attr('href');
                var $tabs = $('#f21c_page_tabs');
                var $content = $('#f21c_page_tab_content');

                $tabs.find('li').removeClass('active');
                $(this).closest('li').addClass('active');

                $content.find('> .tab-pane').removeClass('in active').hide();
                $content.find(targetSelector).addClass('in active').show();

                // Also call Bootstrap's tab method when it is available.
                if ($.fn.tab) {
                    $(this).tab('show');
                }
            });

        // Ensure only the server-selected pane is visible on first load.
        $('#f21c_page_tab_content > .tab-pane').not('.active').hide();

        //             //get setting list

        //  form_21c_settings_tables = $('#form_21c_settings_tabless').DataTable({
        //     processing: true,
        //     serverSide: true,
        //     ajax: {
        //         url: '/mpcs/21cformsettings',
        //         type: 'GET',
        //         dataSrc: function(json) {
        //             var newData = [];

        //             json.data.forEach(function(item) {
        //                 // First row (General details, empty Pump columns)
        //                 newData.push({
        //                     action: item.action,
        //                     date: item.date,
        //                     starting_number: item.starting_number,
        //                     ref_pre_form_number: item.ref_pre_form_number,
        //                     rec_sec_prev_day_amt: item.rec_sec_prev_day_amt,
        //                     rec_sec_opn_stock_amt: item.rec_sec_opn_stock_amt,
        //                     issue_section_previous_day_amount: item.issue_section_previous_day_amount,
        //                     pump_name: "",  // Empty for first row
        //                     last_meter_value: "" // Empty for first row
        //                 });

        //                 // Additional rows for each pump
        //                 if (item.pumps_data && item.pumps_data.length > 0) {
        //                     item.pumps_data.forEach(function(pump) {
        //                         newData.push({
        //                             action: "",  // Empty for pump rows
        //                             date: "",
        //                             starting_number: "",
        //                             ref_pre_form_number: "",
        //                             rec_sec_prev_day_amt: "",
        //                             rec_sec_opn_stock_amt: "",
        //                             issue_section_previous_day_amount: "",
        //                             pump_name: pump.pump_name,
        //                             last_meter_value: pump.last_meter_value
        //                         });
        //                     });
        //                 }
        //             });

        //             return newData;
        //         }
        //     },
        //     columns: [
        //         { data: 'action', name: 'action', orderable: false, searchable: false, defaultContent: '' },
        //         { data: 'date', name: 'date' },
        //         { data: 'starting_number', name: 'starting_number' },
        //         { data: 'ref_pre_form_number', name: 'ref_pre_form_number' },
        //         { data: 'rec_sec_prev_day_amt', name: 'rec_sec_prev_day_amt' },
        //         { data: 'rec_sec_opn_stock_amt', name: 'rec_sec_opn_stock_amt' },
        //         { data: 'issue_section_previous_day_amount', name: 'issue_section_previous_day_amount' },
        //         { data: 'pump_name', name: 'pump_name', defaultContent: '' },
        //         { data: 'last_meter_value', name: 'last_meter_value', defaultContent: '' }
        //     ]
        //     });

        // get_21_c_form_all_query(); // Optional on page load
        $('#f21c_print').click(function (e) {
            e.preventDefault();
            $.ajax({
                method: 'post',
                url: '/mpcs/print-form-f21c',
                data: {
                    data: $('#f21c_form').serialize()
                },
                success: function (result) {
                    if (result.success == 0) {
                        toastr.error(result.msg);

                        return false;
                    }
                    onlyPrintPage(result);

                },
            });
        });

        function onlyPrintPage(content) {
            /*
             * IS2342 #4: print the complete server-generated document by itself.
             * Mixing layouts.partials.css with a second full HTML document inside
             * <body> caused the F21C preview to inherit screen colours/sizing.
             */
            var w = window.open('', '_blank', 'width=1600,height=1000');
            if (!w) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Please allow pop-ups to open the F21C print preview.');
                }
                return false;
            }

            w.document.open();
            w.document.write(content);
            w.document.close();

            var printWhenReady = function() {
                window.setTimeout(function() {
                    w.focus();
                    w.print();
                }, 250);
            };

            if (w.document.readyState === 'complete') {
                printWhenReady();
            } else {
                w.onload = printWhenReady;
            }

            w.onafterprint = function() {
                try { w.close(); } catch (e) {}
            };

            return false;
        }



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
        $('#form_date_range').on('apply.daterangepicker', function (ev, picker) {
            $(this).val(
                picker.startDate.format(moment_date_format) +
                ' - ' +
                picker.endDate.format(moment_date_format)
            );
        });

        $('#form_date_range').on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
        });



        if ($('#form_21c_date_range').length == 1) {
            // Date selector aligned with F16A configuration
            $('#form_21c_date_range').daterangepicker(
                {
                    singleDatePicker: false,
                    showDropdowns: true,
                    autoUpdateInput: false,
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    },
                    startDate: moment(),
                    endDate: moment(),
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: LANG.clear,
                        applyLabel: LANG.apply,
                        customRangeLabel: 'Custom Range',
                    },
                },
                function (start, end) {
                    clearTableData();
                    clearTableDataAll();

                    // Match F16A input formatting (single date when same, range when different)
                    const formattedRange = (start.format('YYYY-MM-DD') === end.format('YYYY-MM-DD'))
                        ? start.format('YYYY-MM-DD')
                        : start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');

                    $('#form_21c_date_range').val(formattedRange);

                    // Update the display date in the header immediately
                    $('#openingdate').text('Date: ' + formattedRange);

                    // Show loading state for form number; actual value comes from AJAX
                    updateDateDisplayOnly(start.format('YYYY-MM-DD'));

                    // Load data for selected period
                    get_21_c_form_all_query();
                }
            );

            // Default selection: today (same style as F16A)
            const todayStr = moment().format('YYYY-MM-DD');
            $('#form_21c_date_range').val(todayStr);
            $('#openingdate').text('Date: ' + todayStr);

            // Handle cancel event
            $('#form_21c_date_range').on('cancel.daterangepicker', function (ev, picker) {
                clearTableData();
                clearTableDataAll();
                $(this).val('');
            });

            // Fallback: ensure data refreshes when Apply is clicked directly on calendar (Issue #1)
            $('#form_21c_date_range').on('apply.daterangepicker', function (ev, picker) {
                var formattedRange = (picker.startDate.format('YYYY-MM-DD') === picker.endDate.format('YYYY-MM-DD'))
                    ? picker.startDate.format('YYYY-MM-DD')
                    : picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');

                // Set the value immediately
                $(this).val(formattedRange);

                // Show loading state for form number; actual value comes from AJAX
                updateDateDisplayOnly(picker.startDate.format('YYYY-MM-DD'));

                // Clear existing data to show we are loading
                clearTableData();
                clearTableDataAll();
                
                // Trigger full data load
                get_21_c_form_all_query();
            });

            // Custom date handler matching F16A
            $(document).off('click', '#custom_date_apply_button').on('click', '#custom_date_apply_button', function () {
                const start_date = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() +
                    $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" +
                    $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" +
                    $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();

                const end_date = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() +
                    $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" +
                    $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" +
                    $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (moment(start_date, 'YYYY-MM-DD').isValid() && moment(end_date, 'YYYY-MM-DD').isValid()) {
                    const formattedStartDate = moment(start_date).format('YYYY-MM-DD');
                    const formattedEndDate = moment(end_date).format('YYYY-MM-DD');

                    const fullRange = (formattedStartDate === formattedEndDate)
                        ? formattedStartDate
                        : formattedStartDate + ' - ' + formattedEndDate;

                    $('#form_21c_date_range').val(fullRange);

                    // Update daterangepicker internal state
                    const drp = $('#form_21c_date_range').data('daterangepicker');
                    if (drp) {
                        drp.setStartDate(moment(start_date));
                        drp.setEndDate(moment(end_date));
                    }

                    // Close modal
                    $('.custom_date_typing_modal').modal('hide');

                    // Refresh UI
                    $('#openingdate').text('Date: ' + fullRange);

                    // Show loading state for form number; actual value comes from AJAX
                    updateDateDisplayOnly(formattedStartDate);

                    clearTableData();
                    clearTableDataAll();
                    get_21_c_form_all_query();
                } else {
                    toastr.error('Please enter valid dates.');
                }
            });

            // Auto-refresh when location changes
            $('#f21c_location_id').on('change', function () {
                clearTableData();
                clearTableDataAll();
                get_21_c_form_all_query();
                // Update location name in header
                updateLocationName();
            });

            // Update location name in header on load
            function updateLocationName() {
                var selectedText = '';

                if ($('#f21c_location_id').is('select')) {
                    selectedText = $('#f21c_location_id option:selected').text().trim();
                } else {
                    selectedText = $('#f21c_location_name_readonly').val() || '';
                }

                if (!selectedText || selectedText === 'All') {
                    selectedText = '';
                }

                // Show only the name (strip "(code)" suffix)
                if (selectedText.includes('(')) {
                    selectedText = selectedText.split('(')[0].trim();
                }

                $('#print_location_name').text(selectedText);
            }
            updateLocationName();

            // Initial query with today's date
            get_21_c_form_all_query();
        }

        function clearTableDataAll() {
            // Reset all input fields to empty or default values
            $('.rows input[type="number"], .rows input[type="text"]').val('');

            // Clear pump names
            $('[id^=pump_name_]').text('');

            // Reset total fields
            $('[id$=_total]').val('');
        }
        // Function to clear table data
        function clearTableData() {
            // Reset all input fields to 0.00
            $('.rows input[type="number"]').not('[name$="[no]"]').val('');
            // $('.rows input[type="number"]').val('');

            // Reset total fields
            $('#cash_for_today_qty_total').val('0.00');
            $('#cash_for_today_val_total').val('0.00');
            $('#credit_for_today_qty_total').val('0.00');
            $('#credit_for_today_val_total').val('0.00');
            $('#issues_up_to_last_day_qty_total').val('0.00');
            $('#issues_up_to_last_day_val_total').val('0.00');
            $('#price_discounts_for_today_qty_total').val('0.00');
            $('#price_discounts_for_today_val_total').val('0.00');
            $('#pre_date_qty_total').val('0.00');
            $('#pre_date_val_total').val('0.00');
            $('#pump_meter_opening_qty_total').val('0.00');
            $('#pump_meter_closing_qty_total').val('0.00');
            $('#issued_qty_for_today_qty_total').val('0.00');
            $('#total_issues_one_qty_total').val('0.00');
            $('#total_issues_one_val_total').val('0.00');
            $('#total_discounts_qty_total').val('0.00');
            $('#total_discounts_val_total').val('0.00');
            $('#total_receipts_today_qty_total').val('0.00');
            $('#total_receipts_today_val_total').val('0.00');
            $('#total_for_today_one_plus_two_qty_total').val('0.00');
            $('#total_for_today_one_plus_two_val_total').val('0.00');
            $('#balances_qty_total').val('0.00');
            $('#balances_val_total').val('0.00');
            $('#total_receipts_qty_total').val('0.00');
            $('#total_receipts_val_total').val('0.00');

            // Clear pump names
            $('[id^=pump_name_]').text('');
        }


        //21c list
        //form 21C

        $('#form_21c_date_range_list').daterangepicker();
        $('#form_21c_date_range_list').daterangepicker({
            onSelect: function () {
                $(this).change();
            }
        });

        if ($('#form_21c_date_range_list').length == 1) {
            $('#form_21c_date_range_list').daterangepicker(dateRangeSettings, function (start, end) {
                $('#form_21c_date_range_list').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                get_21_c_form_all_query_list(); // Call the function when the date range changes
            });

            $('#form_21c_date_range_list').on('cancel.daterangepicker', function (ev, picker) {
                $('#product_sr_date_filter').val('');
                get_21_c_form_all_query_list(); // Call the function when the date range is cancelled
            });

            $('#form_21c_date_range_list')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#form_21c_date_range_list')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }

        var today = 0;
        var previous = 0;
        var opening = 0;
        var today_inc = 0;
        var predate_inc = 0;


        $('#_own_usage_sales_today').on('keyup', function () {
            var cash_sales_today = $('#_cash_sales_today').val() == "" ? 0 : $('#_cash_sales_today')
                .val();
            var credit_sales_today = $('#_credit_sales_today').val() == "" ? 0 : $(
                '#_credit_sales_today').val();
            var own_usage_sales_today = $('#_own_usage_sales_today').val() == "" ? 0 : $(
                '#_own_usage_sales_today').val();
            var price_reduction_today = $('#_price_reduction_today').val() == "" ? 0 : $(
                '#_price_reduction_today').val();
            var price_reduction_predate = $('#_price_reduction_predate').val() == "" ? 0 : $(
                '#_price_reduction_predate').val();

            $('#_price_reduction_total').val(parseInt(price_reduction_today) + parseInt(
                price_reduction_predate));

            $('#_total_issued_today').val(parseInt(cash_sales_today) + parseInt(credit_sales_today) +
                parseInt(own_usage_sales_today) + parseInt(price_reduction_today))

        });

        $('#_price_reduction_today').on('keyup', function () {
            var cash_sales_today = $('#_cash_sales_today').val() == "" ? 0 : $('#_cash_sales_today')
                .val();
            var credit_sales_today = $('#_credit_sales_today').val() == "" ? 0 : $(
                '#_credit_sales_today').val();
            var own_usage_sales_today = $('#_own_usage_sales_today').val() == "" ? 0 : $(
                '#_own_usage_sales_today').val();
            var price_reduction_today = $('#_price_reduction_today').val() == "" ? 0 : $(
                '#_price_reduction_today').val();
            var price_reduction_predate = $('#_price_reduction_predate').val() == "" ? 0 : $(
                '#_price_reduction_predate').val();

            $('#_price_reduction_total').val(parseInt(price_reduction_today) + parseInt(
                price_reduction_predate));

            $('#_total_issued_today').val(parseInt(cash_sales_today) + parseInt(credit_sales_today) +
                parseInt(own_usage_sales_today) + parseInt(price_reduction_today))

        })
        function get_21_c_form_all_query() {
            const dateRangeValue = $('input#form_21c_date_range').val();
            let start_date, end_date;

            if (dateRangeValue.includes(' - ')) {
                const parts = dateRangeValue.split(' - ');
                start_date = parts[0].trim();
                end_date = parts[1].trim();
            } else if (dateRangeValue.includes(' ~ ')) {
                const parts = dateRangeValue.split(' ~ ');
                start_date = parts[0].trim();
                end_date = parts[1].trim();
            } else {
                start_date = end_date = dateRangeValue.trim();
            }

            const location_id = $('#f21c_location_id').val();

            $.ajax({
                method: 'get',
                url: '/mpcs/get_21_c_form_all_query',
                data: { start_date, end_date, location_id },
                success: function (result) {
                    console.log('API Response:', result);

                    // Handle form header information (including full date or date range)
                    handleFormHeader(result, start_date, end_date);

                    // Initialize data structures
                    const categoryData = {};
                    const pumpData = {};

                    // Process all data rows and populate categoryData
                    processAllDataRows(result, categoryData);

                    // Process pump operator data
                    processPumpOperatorData(result, pumpData);

                    // Calculate derived values and update UI
                    calculateAndUpdateAllValues(categoryData, pumpData);

                    // Populate No column per category from F16 data (item 6)
                    updateF16NoColumn(result);
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching form 21C data:", error);
                    alert("Error fetching data. Please check console for details.");
                }
            });
        }

        /**
         * Populate the "No" (item 6) and "Value" (item 7) header inputs per fuel
         * sub-category from F16 data for the selected date range.
         *
         * - f16_qty_by_category: total purchase qty per category_id
         * - f16_val_by_category: SUM(qty × sell_price_at_purchase) — historical price,
         *   frozen at transaction time, never affected by later price changes.
         *
         * Input IDs: f16_no_cat_{categoryId} (qty), f16_val_cat_{categoryId} (value)
         */
        function updateF16NoColumn(result) {
            const qtyByCat = result.f16_qty_by_category || {};
            const valByCat = result.f16_val_by_category || {};

            // Collect all category IDs from both maps
            const allCatIds = new Set([
                ...Object.keys(qtyByCat),
                ...Object.keys(valByCat)
            ]);

            allCatIds.forEach(function(catId) {
                const qty = parseFloat(qtyByCat[catId]) || 0;
                const val = parseFloat(valByCat[catId]) || 0;

                const $qtyInput = $('#f16_no_cat_' + catId);
                const $valInput = $('#f16_val_cat_' + catId);

                if ($qtyInput.length) $qtyInput.val(formatQuantity(qty));
                if ($valInput.length) $valInput.val(formatCurrency(val));
            });

            // Zero-out any rendered category that had no F16 data returned
            $('[id^="f16_no_cat_"]').each(function() {
                const catId = this.id.replace('f16_no_cat_', '');
                if (!(catId in qtyByCat)) $(this).val(formatQuantity(0));
            });
            $('[id^="f16_val_cat_"]').each(function() {
                const catId = this.id.replace('f16_val_cat_', '');
                if (!(catId in valByCat)) $(this).val(formatCurrency(0));
            });
        }

        // Handle form header information
        function handleFormHeader(result, start_date, end_date) {
            // Update date display – show single date or full range
            if (start_date === end_date) {
                $('#openingdate').text('Date: ' + start_date);
            } else {
                $('#openingdate').text('Date: ' + start_date + ' to ' + end_date);
            }

            // Update manager name from server response
            if (result.calculated_manager_name) {
                $('#manager_name').text(result.calculated_manager_name);
            }

            // Form No: must come from server (correct settings record for the selected date)
            if (result.calculated_form_no) {
                const formNo = result.calculated_form_no;
                $('#formno').text('Form No: ' + formNo);
                $('input[name="formnovalue"]').val(formNo);

                // Opening Stock row 'No' column logic:
                // If an F22 exists, always show F22 form number in "F22 – {no}" format.
                // Otherwise, show the calculated 21C form number.
                if (result.form22_form_no) {
                    $('#opening_stock_no').val('F22 \u2013 ' + result.form22_form_no);
                    $('#opening_stock_f22_nos_table').text('');
                } else {
                    $('#opening_stock_no').val(formNo);
                    $('#opening_stock_f22_nos_table').text('');
                }

                // Remove any previous "no form" warning
                $('#no_form_warning').remove();
            } else {
                // No settings record found for the selected date — show clear message
                $('#formno').text('Form No: -');
                $('input[name="formnovalue"]').val('');
                $('#opening_stock_no').val('');
                $('#opening_stock_f22_nos_table').text('');

                // Show warning banner if not already present
                if ($('#no_form_warning').length === 0) {
                    $('#print_content').prepend(
                        '<div id="no_form_warning" class="alert alert-warning" style="margin-bottom:10px;">' +
                        '<strong>No form found for the selected date.</strong> ' +
                        'Please select a date that has a configured 21C form setting.' +
                        '</div>'
                    );
                }
            }

            // Show F16 form numbers for Today row (item 8)
            if (result.today_f16_nos && result.today_f16_nos.length > 0) {
                const f16_nos_str = result.today_f16_nos.join(', ');
                $('#today_f16_nos_table').text(f16_nos_str);
                $('#today_no').val(f16_nos_str);
                // Dedicated item 8 display field
                $('#f16_form_nos_display').text(f16_nos_str);
            } else {
                $('#today_f16_nos_table').text('-');
                $('#today_no').val('-');
                // Dedicated item 8 display field
                $('#f16_form_nos_display').text('-');
            }

            // Show F16 form numbers for Previous Day row
            if (result.previous_f16_nos && result.previous_f16_nos.length > 0) {
                const prev_f16_nos_str = result.previous_f16_nos.join(', ');
                $('#previous_f16_nos_table').text(prev_f16_nos_str);
                $('#previous_day_no').val(prev_f16_nos_str);
            } else {
                $('#previous_f16_nos_table').text('-');
                $('#previous_day_no').val('-');
            }

            // Show F17 form numbers for Price Increment Today row
            if (result.today_f17_nos && result.today_f17_nos.length > 0) {
                const f17_nos_str = result.today_f17_nos.join(', ');
                $('#today_f17_nos_table').text(f17_nos_str);
                $('#price_inc_today_no').val(f17_nos_str);
            } else {
                $('#today_f17_nos_table').text('-');
                $('#price_inc_today_no').val('-');
            }
        }

        // Process all data rows and populate categoryData
        function processAllDataRows(result, categoryData) {
            // Define all data types we need to process
            const dataTypes = [
                { key: 'today_sales', type: 'today' },
                { key: 'previous_day', type: 'previous_day' },
                { key: 'opening_stock', type: 'opening_stock' },
                { key: 'cash_sales_today', type: 'cash_for_today' },
                { key: 'credit_sales_today', type: 'credit_for_today' },
                { key: 'total_receipts_last', type: 'issues_up_to_last_day' },
                { key: 'price_dec_today', type: 'price_discounts_for_today' },
                { key: 'price_dec_previous', type: 'pre_date' },
                { key: 'price_inc_today', type: 'price_inc_today' },
                { key: 'price_inc_previous', type: 'price_inc_previous_day' },
                { key: 'cooperative_sales_today', type: 'cooperative_section_for_today' }
            ];

            // Pre-initialize all categories so the table never looks empty
            // (API often omits rows for categories with 0 totals)
            const allCategoryIds = (result.fuelCategory && typeof result.fuelCategory === 'object')
                ? Object.keys(result.fuelCategory)
                : [];

            allCategoryIds.forEach(catId => {
                if (!categoryData[catId]) {
                    categoryData[catId] = {
                        today_qty: 0, today_val: 0,
                        previous_day_qty: 0, previous_day_val: 0,
                        opening_stock_qty: 0, opening_stock_val: 0,
                        cash_qty: 0, cash_val: 0,
                        credit_qty: 0, credit_val: 0,
                        issues_up_to_last_day_qty: 0, issues_up_to_last_day_val: 0,
                        price_discounts_qty: 0, price_discounts_val: 0,
                        pre_date_qty: 0, pre_date_val: 0,
                        price_inc_today_qty: 0, price_inc_today_val: 0,
                        price_inc_previous_qty: 0, price_inc_previous_val: 0,
                        cooperative_qty: 0, cooperative_val: 0
                    };
                }

                // Fill base fields with 0.00 by default
                dataTypes.forEach(({ type }) => {
                    const $qty = $(`#${type}_qty_${catId}`);
                    const $val = $(`#${type}_val_${catId}`);
                    if ($qty.length) $qty.val(formatQuantity(0));
                    if ($val.length) $val.val(formatCurrency(0));
                });
            });

            // Process each data type
            dataTypes.forEach(({ key, type }) => {
                if (result[key] && Array.isArray(result[key])) {
                    result[key].forEach(item => {
                        const catId = item.category_id;

                        // Initialize category if not exists
                        if (!categoryData[catId]) {
                            categoryData[catId] = {
                                today_qty: 0, today_val: 0,
                                previous_day_qty: 0, previous_day_val: 0,
                                opening_stock_qty: 0, opening_stock_val: 0,
                                cash_qty: 0, cash_val: 0,
                                credit_qty: 0, credit_val: 0,
                                issues_up_to_last_day_qty: 0, issues_up_to_last_day_val: 0,
                                price_discounts_qty: 0, price_discounts_val: 0,
                                pre_date_qty: 0, pre_date_val: 0,
                                price_inc_today_qty: 0, price_inc_today_val: 0,
                                price_inc_previous_qty: 0, price_inc_previous_val: 0
                            };
                        }

                        // Set values based on type
                        let qty = parseFloat(item.total_quantity) || 0;
                        let val = parseFloat(item.total_sales) || 0;

                        if (type === 'price_discounts_for_today' || type === 'pre_date') {
                            qty = Math.abs(qty);
                            val = Math.abs(val);
                        }

                        switch (type) {
                            case 'today':
                                categoryData[catId].today_qty = qty;
                                categoryData[catId].today_val = val;
                                break;
                            case 'previous_day':
                                categoryData[catId].previous_day_qty = qty;
                                categoryData[catId].previous_day_val = val;
                                break;
                            case 'opening_stock':
                                categoryData[catId].opening_stock_qty = qty;
                                categoryData[catId].opening_stock_val = val;
                                break;
                            case 'cash_for_today':
                                categoryData[catId].cash_qty = qty;
                                categoryData[catId].cash_val = val;
                                break;
                            case 'credit_for_today':
                                categoryData[catId].credit_qty = qty;
                                categoryData[catId].credit_val = val;
                                break;
                            case 'issues_up_to_last_day':
                                categoryData[catId].issues_up_to_last_day_qty = qty;
                                categoryData[catId].issues_up_to_last_day_val = val;
                                break;
                            case 'price_discounts_for_today':
                                categoryData[catId].price_discounts_qty = qty;
                                categoryData[catId].price_discounts_val = val;
                                break;
                            case 'pre_date':
                                categoryData[catId].pre_date_qty = qty;
                                categoryData[catId].pre_date_val = val;
                                break;
                            case 'price_inc_today':
                                categoryData[catId].price_inc_today_qty = qty;
                                categoryData[catId].price_inc_today_val = val;
                                break;
                            case 'price_inc_previous_day':
                                categoryData[catId].price_inc_previous_qty = qty;
                                categoryData[catId].price_inc_previous_val = val;
                                break;
                            case 'cooperative_section_for_today':
                                categoryData[catId].cooperative_qty = qty;
                                categoryData[catId].cooperative_val = val;
                                break;
                        }

                        // Update UI for basic fields with proper formatting (always show 0.00 instead of blank)
                        $(`#${type}_qty_${catId}`).val(formatQuantity(qty));
                        $(`#${type}_val_${catId}`).val(formatCurrency(val));
                    });
                }
            });
        }
        // Process pump operator data with date validation
        function processPumpOperatorData(result, pumpData) {
            // Exit if no pump data is available
            if (!result.pump_operator || !Array.isArray(result.pump_operator)) {
                return;
            }

            const monthStartReset = result.month_start_reset === true;
            let totalOpeningMeter = 0, totalClosingMeter = 0, totalIssuedQty = 0;

            // Iterate through each pump operator item
            result.pump_operator.forEach(item => {
                const pumpId = item.pump_id;
                const catId = item.category_id;
                const pumpName = item.pump_no || '-';

                // Parse actual values from the server response
                const actualMinMeter = parseFloat(item.min_starting_meter) || 0;
                const actualMaxMeter = parseFloat(item.max_closing_meter) || 0;
                const actualDiff = actualMaxMeter - actualMinMeter;

                // Always use actual meter values from the server response
                const displayMinMeter = actualMinMeter;
                const displayMaxMeter = actualMaxMeter;
                const displayDiff = actualDiff;

                // Store pump data
                pumpData[pumpId] = {
                    pump_no: pumpName,
                    opening_meter: actualMinMeter,
                    closing_meter: actualMaxMeter,
                    issued_qty: actualDiff,
                    category_id: catId
                };

                // Helper function to update input fields
                // Escape brackets in name attribute for jQuery selector
                const escName = (s) => s.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
                const updateField = (selector, value, alwaysShow = false) => {
                    const $field = $(`input[name="${escName(selector)}"][data-pump-id="${pumpId}"]`);
                    $field.val((alwaysShow || value !== 0) ? formatQuantity(value) : '');
                };

                // Update UI fields with display values
                // Opening meter always shows (even 0) so the field is never blank
                updateField(`pump_meter_opening[${catId}][val][]`, displayMinMeter, true);
                updateField(`pump_meter_closing[${catId}][val][]`, displayMaxMeter, monthStartReset);
                updateField(`issued_qty_for_today[${catId}][val][]`, displayDiff, monthStartReset);

                // Accumulate totals for display values
                totalOpeningMeter += displayMinMeter;
                totalClosingMeter += displayMaxMeter;
                totalIssuedQty += displayDiff;
            });

            // Helper function to format and set total values
            const setTotalValue = (selector, value) => {
                $(selector).val((monthStartReset || value !== 0) ? formatQuantity(value) : '');
            };

            // Update total values in the UI
            setTotalValue('#pump_meter_opening_qty_total', totalOpeningMeter);
            setTotalValue('#pump_meter_closing_qty_total', totalClosingMeter);
            setTotalValue('#issued_qty_for_today_qty_total', totalIssuedQty);
        }

        // Calculate derived values and update UI
        function calculateAndUpdateAllValues(categoryData, pumpData) {
            const totals = {
                today_qty: 0, today_val: 0,
                previous_day_qty: 0, previous_day_val: 0,
                opening_stock_qty: 0, opening_stock_val: 0,
                cash_qty: 0, cash_val: 0,
                credit_qty: 0, credit_val: 0,
                issues_up_to_last_day_qty: 0, issues_up_to_last_day_val: 0,
                price_discounts_qty: 0, price_discounts_val: 0,
                pre_date_qty: 0, pre_date_val: 0,
                total_issues_qty: 0, total_issues_val: 0,
                total_issues_one_qty: 0, total_issues_one_val: 0,
                total_discounts_qty: 0, total_discounts_val: 0,
                total_receipts_qty: 0, total_receipts_val: 0,
                price_inc_today_qty: 0, price_inc_today_val: 0,
                price_inc_previous_qty: 0, price_inc_previous_val: 0,
                price_inc_total_qty: 0, price_inc_total_val: 0,
                total_receipts_today_qty: 0, total_receipts_today_val: 0,
                total_for_today_one_plus_two_qty: 0, total_for_today_one_plus_two_val: 0,
                balances_qty: 0, balances_val: 0,
                cooperative_qty: 0, cooperative_val: 0
            };

            // Process each category
            Object.keys(categoryData).forEach(catId => {
                const data = categoryData[catId];

                // Calculate derived values
                const total_issues_qty = data.cash_qty + data.credit_qty + data.cooperative_qty;
                const total_issues_val = data.cash_val + data.credit_val + data.cooperative_val;

                const total_issues_one_qty = total_issues_qty + data.issues_up_to_last_day_qty;
                const total_issues_one_val = total_issues_val + data.issues_up_to_last_day_val;

                const total_discounts_qty = data.price_discounts_qty + data.pre_date_qty;
                const total_discounts_val = data.price_discounts_val + data.pre_date_val;

                // Calculate Total Receipts (Image Row No. 18 + Image Row No. 19)
                const total_receipts_qty = data.today_qty + data.previous_day_qty;
                const total_receipts_val = data.today_val + data.previous_day_val;

                // Calculate Total Receipts for Price Increment (Image Row No. 12 + Image Row No. 13)
                const price_inc_total_qty = data.price_inc_today_qty + data.price_inc_previous_qty;
                const price_inc_total_val = data.price_inc_today_val + data.price_inc_previous_val;

                const total_receipts_today_qty = total_receipts_qty + data.opening_stock_qty + price_inc_total_qty;
                const total_receipts_today_val = total_receipts_val + data.opening_stock_val + price_inc_total_val;

                const total_for_today_one_plus_two_qty = total_issues_one_qty + total_discounts_qty;
                const total_for_today_one_plus_two_val = total_issues_one_val + total_discounts_val;

                const balances_qty = total_receipts_today_qty - total_for_today_one_plus_two_qty;

                // Keep the balance quantity and balance amount on the same valuation basis.
                // A positive stock quantity must not display a negative stock value merely
                // because receipt and issue rows were valued using different historical rates.
                // Normally retain the standard amount formula. If its sign conflicts with the
                // quantity, revalue the remaining quantity using the receipt-side average rate.
                let balances_val = total_receipts_today_val - total_for_today_one_plus_two_val;
                const receiptAverageRate = total_receipts_today_qty !== 0
                    ? Math.abs(total_receipts_today_val / total_receipts_today_qty)
                    : 0;

                if (balances_qty > 0 && balances_val < 0) {
                    balances_val = balances_qty * receiptAverageRate;
                } else if (balances_qty < 0 && balances_val > 0) {
                    balances_val = -Math.abs(balances_qty * receiptAverageRate);
                } else if (Math.abs(balances_qty) < 0.0000001) {
                    balances_val = 0;
                }

                // Update UI for derived fields with proper formatting (always show 0.00 instead of blank)
                $(`#total_issues_qty_${catId}`).val(formatQuantity(total_issues_qty));
                $(`#total_issues_val_${catId}`).val(formatCurrency(total_issues_val));

                $(`#total_issues_one_qty_${catId}`).val(formatQuantity(total_issues_one_qty));
                $(`#total_issues_one_val_${catId}`).val(formatCurrency(total_issues_one_val));

                $(`#total_discounts_qty_${catId}`).val(formatQuantity(total_discounts_qty));
                $(`#total_discounts_val_${catId}`).val(formatCurrency(total_discounts_val));

                $(`#total_receipts_qty_${catId}`).val(formatQuantity(total_receipts_qty));
                $(`#total_receipts_val_${catId}`).val(formatCurrency(total_receipts_val));

                $(`#price_inc_total_qty_${catId}`).val(formatQuantity(price_inc_total_qty));
                $(`#price_inc_total_val_${catId}`).val(formatCurrency(price_inc_total_val));

                $(`#total_receipts_today_qty_${catId}`).val(formatQuantity(total_receipts_today_qty));
                $(`#total_receipts_today_val_${catId}`).val(formatCurrency(total_receipts_today_val));

                $(`#total_for_today_one_plus_two_qty_${catId}`).val(formatQuantity(total_for_today_one_plus_two_qty));
                $(`#total_for_today_one_plus_two_val_${catId}`).val(formatCurrency(total_for_today_one_plus_two_val));

                $(`#balances_qty_${catId}`).val(formatQuantity(balances_qty));
                $(`#balances_val_${catId}`).val(formatCurrency(balances_val));

                // Accumulate totals
                totals.today_qty += data.today_qty;
                totals.today_val += data.today_val;
                totals.previous_day_qty += data.previous_day_qty;
                totals.previous_day_val += data.previous_day_val;
                totals.opening_stock_qty += data.opening_stock_qty;
                totals.opening_stock_val += data.opening_stock_val;
                totals.cash_qty += data.cash_qty;
                totals.cash_val += data.cash_val;
                totals.credit_qty += data.credit_qty;
                totals.credit_val += data.credit_val;
                totals.issues_up_to_last_day_qty += data.issues_up_to_last_day_qty;
                totals.issues_up_to_last_day_val += data.issues_up_to_last_day_val;
                totals.price_discounts_qty += data.price_discounts_qty;
                totals.price_discounts_val += data.price_discounts_val;
                totals.pre_date_qty += data.pre_date_qty;
                totals.pre_date_val += data.pre_date_val;
                totals.price_inc_today_qty += data.price_inc_today_qty;
                totals.price_inc_today_val += data.price_inc_today_val;
                totals.price_inc_previous_qty += data.price_inc_previous_qty;
                totals.price_inc_previous_val += data.price_inc_previous_val;
                totals.price_inc_total_qty += price_inc_total_qty;
                totals.price_inc_total_val += price_inc_total_val;
                totals.total_issues_qty += total_issues_qty;
                totals.total_issues_val += total_issues_val;
                totals.total_issues_one_qty += total_issues_one_qty;
                totals.total_issues_one_val += total_issues_one_val;
                totals.total_discounts_qty += total_discounts_qty;
                totals.total_discounts_val += total_discounts_val;
                totals.total_receipts_qty += total_receipts_qty;
                totals.total_receipts_val += total_receipts_val;
                totals.total_receipts_today_qty += total_receipts_today_qty;
                totals.total_receipts_today_val += total_receipts_today_val;
                totals.total_for_today_one_plus_two_qty += total_for_today_one_plus_two_qty;
                totals.total_for_today_one_plus_two_val += total_for_today_one_plus_two_val;
                totals.balances_qty += balances_qty;
                totals.balances_val += balances_val;
                totals.cooperative_qty += data.cooperative_qty;
                totals.cooperative_val += data.cooperative_val;
            });

            // Update all total fields with proper formatting (always show 0.00 instead of blank)
            $('#today_qty_total').val(formatQuantity(totals.today_qty));
            $('#today_val_total').val(formatCurrency(totals.today_val));
            $('#previous_day_qty_total').val(formatQuantity(totals.previous_day_qty));
            $('#previous_day_val_total').val(formatCurrency(totals.previous_day_val));
            $('#opening_stock_qty_total').val(formatQuantity(totals.opening_stock_qty));
            $('#opening_stock_val_total').val(formatCurrency(totals.opening_stock_val));
            $('#cash_for_today_qty_total').val(formatQuantity(totals.cash_qty));
            $('#cash_for_today_val_total').val(formatCurrency(totals.cash_val));
            $('#credit_for_today_qty_total').val(formatQuantity(totals.credit_qty));
            $('#credit_for_today_val_total').val(formatCurrency(totals.credit_val));
            $('#issues_up_to_last_day_qty_total').val(formatQuantity(totals.issues_up_to_last_day_qty));
            $('#issues_up_to_last_day_val_total').val(formatCurrency(totals.issues_up_to_last_day_val));
            $('#price_discounts_for_today_qty_total').val(formatQuantity(totals.price_discounts_qty));
            $('#price_discounts_for_today_val_total').val(formatCurrency(totals.price_discounts_val));
            $('#pre_date_qty_total').val(formatQuantity(totals.pre_date_qty));
            $('#pre_date_val_total').val(formatCurrency(totals.pre_date_val));
            $('#total_issues_qty_total').val(formatQuantity(totals.total_issues_qty));
            $('#total_issues_val_total').val(formatCurrency(totals.total_issues_val));
            $('#total_issues_one_qty_total').val(formatQuantity(totals.total_issues_one_qty));
            $('#total_issues_one_val_total').val(formatCurrency(totals.total_issues_one_val));
            $('#total_discounts_qty_total').val(formatQuantity(totals.total_discounts_qty));
            $('#total_discounts_val_total').val(formatCurrency(totals.total_discounts_val));
            $('#total_receipts_qty_total').val(formatQuantity(totals.total_receipts_qty));
            $('#total_receipts_val_total').val(formatCurrency(totals.total_receipts_val));

            $('#price_inc_today_qty_total').val(formatQuantity(totals.price_inc_today_qty));
            $('#price_inc_today_val_total').val(formatCurrency(totals.price_inc_today_val));
            $('#price_inc_previous_day_qty_total').val(formatQuantity(totals.price_inc_previous_qty));
            $('#price_inc_previous_day_val_total').val(formatCurrency(totals.price_inc_previous_val));
            $('#price_inc_total_qty_total').val(formatQuantity(totals.price_inc_total_qty));
            $('#price_inc_total_val_total').val(formatCurrency(totals.price_inc_total_val));

            $('#total_receipts_today_qty_total').val(formatQuantity(totals.total_receipts_today_qty));
            $('#total_receipts_today_val_total').val(formatCurrency(totals.total_receipts_today_val));
            $('#total_for_today_one_plus_two_qty_total').val(formatQuantity(totals.total_for_today_one_plus_two_qty));
            $('#total_for_today_one_plus_two_val_total').val(formatCurrency(totals.total_for_today_one_plus_two_val));
            $('#balances_qty_total').val(formatQuantity(totals.balances_qty));
            $('#balances_val_total').val(formatCurrency(totals.balances_val));

            $('#cooperative_section_for_today_qty_total').val(formatQuantity(totals.cooperative_qty));
            $('#cooperative_section_for_today_val_total').val(formatCurrency(totals.cooperative_val));
        }

        // ── Number-formatting helpers (Issue #2) ────────────────────────────────────
        // Uses PHP-injected precision so values are always correct even if the global
        // __currency_precision / __quantity_precision aren't available yet.
        const _21c_qty_precision = {{ $qty_precision ?? 2
    }};
    const _21c_currency_precision = {{ $currency_precision ?? 2 }};

    function formatCurrency(value) {
        let num = parseFloat(value);
        if (isNaN(num)) num = 0;
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: _21c_currency_precision,
            maximumFractionDigits: _21c_currency_precision,
            useGrouping: true
        }).format(num);
    }

    function formatQuantity(value) {
        let num = parseFloat(value);
        if (isNaN(num)) num = 0;
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: _21c_qty_precision,
            maximumFractionDigits: _21c_qty_precision,
            useGrouping: true
        }).format(num);
    }

    function formatInputValue(value, isQuantity) {
        isQuantity = isQuantity || false;
        const num = isNaN(parseFloat(value)) ? 0 : parseFloat(value);
        return isQuantity ? formatQuantity(num) : formatCurrency(num);
    }

    // ── Price Increment auto-calculation (Issue #14) ─────────────────────────────
    // row Total = row Today + row Previous Day, per category
    function recalcPriceIncrementTotals(fuelCategoryIds) {
        let total_qty = 0;
        let total_val = 0;

        fuelCategoryIds.forEach(function (catId) {
            const todayQty = parseFloat(($('#price_inc_today_qty_' + catId).val() || '').replace(/,/g, '')) || 0;
            const todayVal = parseFloat(($('#price_inc_today_val_' + catId).val() || '').replace(/,/g, '')) || 0;
            const prevQty = parseFloat(($('#price_inc_previous_day_qty_' + catId).val() || '').replace(/,/g, '')) || 0;
            const prevVal = parseFloat(($('#price_inc_previous_day_val_' + catId).val() || '').replace(/,/g, '')) || 0;

            const sumQty = todayQty + prevQty;
            const sumVal = todayVal + prevVal;

            $('#price_inc_total_qty_' + catId).val(formatQuantity(sumQty));
            $('#price_inc_total_val_' + catId).val(formatCurrency(sumVal));

            total_qty += sumQty;
            total_val += sumVal;
        });

        $('#price_inc_total_qty_total').val(formatQuantity(total_qty));
        $('#price_inc_total_val_total').val(formatCurrency(total_val));
    }

    // ── Real-time Recalculation (Issue #20, #21) ────────────────────────────────
    function recalculateAllFromDom() {
        const categoryData = {};
        const pumpData = {};

        // 1. Collect category-wise data from inputs
        $('[id*="_qty_"]').each(function () {
            const idParts = this.id.split('_qty_');
            if (idParts.length < 2) return;
            const type = idParts[0];
            const catId = idParts[1];

            // Skip totals and non-category inputs
            if (catId === 'total' || isNaN(catId)) return;

            if (!categoryData[catId]) categoryData[catId] = {};

            let qtyVal = parseFloat($(this).val().replace(/,/g, '')) || 0;
            let valVal = parseFloat($(`#${type}_val_${catId}`).val().replace(/,/g, '')) || 0;

            if (type === 'price_discounts_for_today' || type === 'pre_date') {
                qtyVal = Math.abs(qtyVal);
                valVal = Math.abs(valVal);
            }

            categoryData[catId][type + '_qty'] = qtyVal;
            categoryData[catId][type + '_val'] = valVal;
        });

        // Support categoryData keys mapping for calculation function
        Object.keys(categoryData).forEach(catId => {
            const d = categoryData[catId];
            d.cash_qty = d.cash_for_today_qty || 0;
            d.cash_val = d.cash_for_today_val || 0;
            d.credit_qty = d.credit_for_today_qty || 0;
            d.credit_val = d.credit_for_today_val || 0;
            d.price_discounts_qty = d.price_discounts_for_today_qty || 0;
            d.price_discounts_val = d.price_discounts_for_today_val || 0;
            d.price_inc_today_qty = d.price_inc_today_qty || 0;
            d.price_inc_today_val = d.price_inc_today_val || 0;
            d.price_inc_previous_qty = d.price_inc_previous_day_qty || 0;
            d.price_inc_previous_val = d.price_inc_previous_day_val || 0;
        });

        // 2. Call the existing calculation function
        calculateAndUpdateAllValues(categoryData, pumpData);
    }

    // Wire up global listener for table inputs
    $(document).on('change keyup', '#form_21c_table .qty-input, #form_21c_table .val-input', function () {
        recalculateAllFromDom();
    });

    // Fix: Recalculate price increment row specifically if needed (though global handler covers it now)
    $(document).on('change blur', '[id^="price_inc_today_"],[id^="price_inc_previous_day_"]', function () {
        recalculateAllFromDom();
    });


    // all
    function get_21_c_form_all_query_list() {
        var start_date = $('input#form_21c_date_range_list')
            .data('daterangepicker')
            .startDate.format('YYYY-MM-DD');
        var end_date = $('input#form_21c_date_range_list')
            .data('daterangepicker')
            .endDate.format('YYYY-MM-DD');
        var location_id = $('#f21c_location_id').val();

        $.ajax({
            method: 'get',
            url: '/mpcs/get-9c-forms',
            data: {
                start_date,
                end_date,
                location_id
            },
            contentType: 'html',
            success: function (result) {
                console.log("result_list_ss");
                $('#21c_details_section').empty().append(result);


            },
        });
    }

        });

    /*
     * IS-1936: swallow the default action as well.
     *
     * The button is now type="button" (see 21c_form.blade.php), which is the real
     * fix. This preventDefault is a second line of defence: #print_div lives inside
     * the f21c_form, and if anything ever restores type="submit" - or a stray Enter
     * key lands on it - the page would POST to /mpcs/21CForm, which is a GET-only
     * route, and the user would get a 405 error page behind the print preview again.
     */
    $("#print_div").click(function (e) {
        e.preventDefault();
        printDiv();
    });

    function printDiv() {
        // Clone the content so we don't mess with the original DOM
        var content = document.getElementById("print_content").cloneNode(true);

        // Replace visible inputs with spans; hidden fields must not become stray text (e.g. formnovalue "33")
        content.querySelectorAll('input').forEach(function (input) {
            if (input.type === 'hidden') {
                if (input.parentNode) {
                    input.parentNode.removeChild(input);
                }
                return;
            }
            if (input.type === 'checkbox') {
                var cbSpan = document.createElement('span');
                cbSpan.textContent = input.checked ? '\u2713 ' : '';
                cbSpan.className = 'f21c-print-checkbox';
                input.parentNode.replaceChild(cbSpan, input);
                return;
            }
            var span = document.createElement('span');
            span.textContent = input.value;
            span.style.display = 'inline-block';
            span.style.width = '100%';
            span.style.textAlign = 'right';
            input.parentNode.replaceChild(span, input);
        });

        // Open in a new blank tab so the current page is never replaced
        var w = window.open('', '_blank');
        var html = `
        <!DOCTYPE html>
        <html>
            <head>
                <title>21C Form</title>
                <style>
                    @page {
                        size: A4 landscape;
                        margin: 8mm;
                    }
                    html, body {
                        width: 100%;
                        margin: 0;
                        padding: 0;
                        overflow: visible !important;
                    }
                    * {
                        font-size: 8pt;
                        box-sizing: border-box;
                    }
                    .text-center { text-align: center; }
                    .text-right  { text-align: right; }
                    .pull-left   { float: left; }
                    /* Block layout — flex was collapsing footer rows into the table grid */
                    .row {
                        display: block;
                        width: 100%;
                        clear: both;
                        margin-bottom: 8px;
                    }
                    .col-md-12 { width: 100%; display: block; }
                    .f21c-after-table .col-md-6 {
                        display: inline-block;
                        width: 48%;
                        vertical-align: top;
                        box-sizing: border-box;
                    }
                    .f21c-after-table {
                        margin-top: 14px;
                        page-break-inside: avoid;
                    }
                    /* Inline max-height:80vh caps layout height but table paints full height — footer then overlaps rows */
                    .table-responsive {
                        max-height: none !important;
                        height: auto !important;
                        overflow: visible !important;
                    }
                    .box-body {
                        max-height: none !important;
                        overflow: visible !important;
                    }
                    .f21c-document-footer {
                        display: block !important;
                        width: 100% !important;
                        clear: both !important;
                        float: none !important;
                        margin-top: 16pt !important;
                        padding-top: 10pt !important;
                        border-top: 2px solid #000 !important;
                        background: #fff !important;
                        page-break-inside: avoid;
                    }
                    .f21c-form-meta {
                        line-height: 1.35;
                    }
                    .f21c-form-meta #formno {
                        white-space: nowrap;
                    }
                    tr.pump-row td,
                    tr.pump-meter-data td {
                        vertical-align: middle !important;
                        padding: 4px 5px !important;
                    }
                    h4 { font-weight: bold; text-align: center; margin: 2px 0; }
                    h3 { font-weight: bold; text-align: right;  margin: 2px 0; }
                    h5 { font-weight: bold; margin: 2px 0; }
                    table {
                        border-collapse: collapse;
                        width: 100%;
                        table-layout: auto;
                        page-break-inside: auto;
                    }
                    table, th, td {
                        border: 1px solid black;
                        padding: 2px 4px;
                    }
                    th {
                        background-color: #f2f2f2;
                        text-align: center;
                    }
                    .rows { text-align: right !important; }
                    .rows span { white-space: nowrap; }
                    [style*="overflow"]:not(html):not(body) {
                        overflow: visible !important;
                    }
                    /* Sticky positioning breaks print — reset it */
                    th, td {
                        position: static !important;
                    }
                    /* Hide business name, 21C heading */
                    .print-hide-header {
                        display: none !important;
                    }
                    @media print {
                        html, body {
                            width: 100%;
                            overflow: visible !important;
                        }
                        /* Prevent header rows (including F16 Total) from repeating on page breaks */
                        #form_21c_table thead {
                            display: table-row-group !important;
                        }
                        .table-responsive {
                            max-height: none !important;
                            height: auto !important;
                            overflow: visible !important;
                        }
                        .box-body {
                            max-height: none !important;
                            overflow: visible !important;
                        }
                        [style*="overflow"]:not(html):not(body) {
                            overflow: visible !important;
                        }
                        th, td {
                            position: static !important;
                        }
                        .print-hide-header {
                            display: none !important;
                        }
                    }
                </style>
            </head>
            <body>
                ${content.innerHTML}
            </body>
        </html>
        `;

        w.document.write(html);
        w.document.close();
        w.focus();
        // Give the browser a moment to render before triggering print
        setTimeout(function() { w.print(); }, 300);
    }







</script>
@if (empty($is_ajax))
@endsection
@endif
