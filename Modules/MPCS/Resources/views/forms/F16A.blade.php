@extends('layouts.app')
@section('title', __('mpcs::lang.16A_form'))
@section('content')
@section('css')
<style type="text/css" media="print">
    .dataTables_filter,
    .dataTables_length,
    .dataTables_paginate,
    .dataTables_info,
    .dt-buttons,
    .buttons-print,
    .buttons-excel,
    .buttons-pdf,
    .buttons-csv,
    .buttons-copy,
    #form_16a_live_table_wrapper > .row:first-child,
    #form_16a_live_table_wrapper > .row:last-child,
    #form_16a_live_table_wrapper .dataTables_processing {
        display: none !important;
    }

       @page {
        margin: 8mm;
    }
    
    body {
        margin-top: 1cm;
        margin-bottom: 1cm;
    }
    
    #form_16a_live_table {
        width: 100% !important;
    }
    
    
    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: transparent !important;
    }
    
</style>
@endsection
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
            <div class="settlement_tabs" id="mpcs_f16a_tabs" data-mpcs-tabs>
                <ul class="nav nav-tabs">
                    @if (auth()->user()->can('f16a_form') || auth()->user()->can('f16_form'))
                    <li class="active">
                        <a href="#f16a_form_tab" class="f16a_form_tab" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.16A_form')</strong>
                        </a>
                    </li>
                    @endif
                    @if (auth()->user()->can('f16a_form') || auth()->user()->can('f16_form'))
                    <li class="">
                        <a href="#f16a_form_list_tab" class="f16a_settings_tab" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.16A_form_settings')</strong>
                        </a>
                    </li>
                    @endif
                </ul>
                <div class="tab-content">

                    @if (auth()->user()->can('f16a_form') || auth()->user()->can('f16_form'))
                    <div class="tab-pane active in" id="f16a_form_tab">
                        @include('mpcs::forms.partials.16a_form')
                    </div>
                    @endif
                    @if (auth()->user()->can('16a_form') || auth()->user()->can('f16a_form') || auth()->user()->can('f16_form'))
                    <div class="tab-pane" id="f16a_form_list_tab">
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
@include('mpcs::partials.safe_tabs')
<script type="text/javascript">
    var current_form_number = "{{ $F16a_from_no ?? 1 }}";
    var f16aOpeningDate = "{{ optional($settings)->date ?? '' }}";
    var f16aStartingNumber = "{{ optional($settings)->starting_number ?? '' }}";
    var f16aReportVersion = '';
    var f16aReportCriteria = '';
    var f16aResettingToFirstPage = false;
    var f16aConfiguredPageLength = parseInt("{{ optional($settings)->no_of_rows_per_page ?? 25 }}", 10);
    var f16aDefaultPageLength = f16aConfiguredPageLength <= 25 ? 25 : (f16aConfiguredPageLength <= 50 ? 50 : 100);

    function resetF16AReportState() {
        f16aReportVersion = '';
        f16aReportCriteria = '';
    }

    function formatF16AAmount(value) {
        value = parseFloat(value || 0);
        if (isNaN(value)) {
            value = 0;
        }

        return __number_f(value, false, false, __currency_precision);
    }

    function updateF16AServerTotals(response) {
        response = response || {};
        var pageIndex = parseInt(response.page_index || 0, 10);
        var isFirstPage = pageIndex === 0;

        var pagePurchase = parseFloat(response.page_total_purchase || 0);
        var pageSale = parseFloat(response.page_total_sale || 0);
        var previousPurchase = isFirstPage
            ? parseFloat(response.total_previous_day_purchase || 0)
            : parseFloat(response.previous_page_total_purchase || 0);
        var previousSale = isFirstPage
            ? parseFloat(response.total_previous_day_sale || 0)
            : parseFloat(response.previous_page_total_sale || 0);
        var grandPurchase = parseFloat(response.grand_total_purchase || 0);
        var grandSale = parseFloat(response.grand_total_sale || 0);

        $('#footer_F16A_total_purchase_price').text(formatF16AAmount(pagePurchase));
        $('#footer_F16A_total_sale_price').text(formatF16AAmount(pageSale));
        $('#pre_F16A_total_purchase_price').text(formatF16AAmount(previousPurchase));
        $('#pre_F16A_total_sale_price').text(formatF16AAmount(previousSale));
        $('#grand_F16A_total_purchase_price').text(formatF16AAmount(grandPurchase));
        $('#grand_F16A_total_sale_price').text(formatF16AAmount(grandSale));

        $('#total_this_p').val(pagePurchase);
        $('#total_this_s').val(pageSale);
        $('#total_this_p_prev').val(previousPurchase);
        $('#total_this_s_prev').val(previousSale);

        if (isFirstPage) {
            $('#f16a_previous_purchase_label').text('Total Previous Day');
            $('#f16a_previous_sale_label').text('Previous Day Sale Total');
        } else {
            $('#f16a_previous_purchase_label').text('Previous Page Total');
            $('#f16a_previous_sale_label').text('Previous Page Sale Total');
        }

        if (response.display_form !== undefined && response.display_form !== null) {
            $('#form_no1').text(response.display_form);
            $('#F16a_from_no').val(response.display_form);
        }
    }

    function computeFormNumberForDate(dateStr) {
        if (!dateStr) {
            return '';
        }

        var startNumber = parseInt(f16aStartingNumber, 10);
        if (isNaN(startNumber) || startNumber < 1) {
            startNumber = 1;
        }

        if (!f16aOpeningDate) {
            return startNumber;
        }

        var opening = moment(f16aOpeningDate, 'YYYY-MM-DD', true);
        var selected = moment(dateStr, 'YYYY-MM-DD', true);
        if (!opening.isValid() || !selected.isValid()) {
            return startNumber;
        }

        if (selected.isBefore(opening, 'day')) {
            return startNumber;
        }

        return startNumber + selected.diff(opening, 'days');
    }

    function applyFormNumberDisplay(pageInfo, baseNumber) {
        if (baseNumber === null || baseNumber === undefined || baseNumber === '') {
            return;
        }

        var baseText = String(baseNumber);
        var suffix = '';
        if (pageInfo && pageInfo.pages > 1) {
            suffix = '-' + (pageInfo.page + 1);
        }

        var displayNumber = baseText + suffix;
        $('#form_no1').text(displayNumber);
        $('#F16a_from_no').val(displayNumber);
    }
    $(document).ready(function() {

        // F16A tabs use a page-scoped switcher instead of relying on the global
        // Bootstrap tab handler. This prevents selector conflicts with other MPCS
        // forms and keeps the Settings panel independently accessible.
        var $f16aTabs = $('#mpcs_f16a_tabs');

        function activateF16ATab(target, updateHash) {
            var $link = $f16aTabs.children('.nav-tabs').find('a[href="' + target + '"]').first();
            var $pane = $f16aTabs.children('.tab-content').children(target);

            if (!$link.length || !$pane.length) {
                return false;
            }

            $f16aTabs.children('.nav-tabs').find('li').removeClass('active');
            $link.closest('li').addClass('active');
            $f16aTabs.children('.tab-content').children('.tab-pane').removeClass('active in').hide();
            $pane.addClass('active in').show();

            $f16aTabs.children('.nav-tabs').find('a').attr('aria-expanded', 'false');
            $link.attr('aria-expanded', 'true');

            if (target === '#f16a_form_tab' && $.fn.dataTable && $.fn.dataTable.isDataTable('#form_16a_live_table')) {
                $('#form_16a_live_table').DataTable().columns.adjust();
            }

            if (target === '#f16a_form_list_tab' && $.fn.dataTable && $.fn.dataTable.isDataTable('#form_16a_settings_table')) {
                $('#form_16a_settings_table').DataTable().columns.adjust();
            }

            $link.trigger('shown.bs.tab');

            if (updateHash && window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, window.location.pathname + window.location.search + target);
            }

            return true;
        }

        $f16aTabs.children('.nav-tabs').find('a[data-toggle="tab"]')
            .off('click.f16aSafeTabs')
            .on('click.f16aSafeTabs', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                activateF16ATab($(this).attr('href'), true);
                return false;
            });

        var initialF16ATab = window.location.hash || '#f16a_form_tab';
        if (!activateF16ATab(initialF16ATab, false)) {
            activateF16ATab('#f16a_form_tab', false);
        }

        $('#form-id').hide();
        let previousFormNo = null;
        let previousInvoiceNo = null;
        let previousSupplier = null;
        let previousDateRange = null;
        let previousProduct = null;
        
        // Initialize Select2 for product filter with AJAX - default to "All" (no filter)
        $('#16a_product_filter').select2({
            placeholder: "@lang('lang_v1.all')",
            allowClear: true,
            ajax: {
                url: function() {
                    return $('#16a_product_filter').data('url');
                },
                dataType: 'json',
                delay: 100, // Further reduced delay for faster response
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    return {
                        results: data.results,
                        pagination: data.pagination
                    };
                },
                cache: true,
                // Add error handling
                error: function(xhr, status, error) {
                    console.error('Product search error:', error);
                    return { results: [] };
                }
            },
            // Let user open the dropdown immediately; still supports typing to search
            minimumInputLength: 0,
            maximumInputLength: 50,
            templateResult: function(data) {
                if (!data.id) return data.text;
                return $('<span>')
                    .text(data.text)
                    .attr('title', data.text); // Add tooltip for long names
            },
            templateSelection: function(data) {
                if (!data.id) return data.text;
                return data.text;
            },
            // Enhanced dropdown options
            dropdownAutoWidth: true,
            width: '100%',
            theme: 'bootstrap4',
            containerCssClass: ':all:',
            // Add keyboard navigation
            tags: false
        });

        // Add supplier search functionality if supplier field exists
        $('#supplier').select2({
            placeholder: 'Search suppliers...',
            allowClear: true,
            ajax: {
                url: '/mpcs/get-suppliers-search',
                dataType: 'json',
                delay: 100,
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    return {
                        results: data.results,
                        pagination: data.pagination
                    };
                },
                cache: true
            },
            minimumInputLength: 1,
            width: '100%'
        });

        // Add invoice number search with autocomplete
        $('#invoice_no').on('keyup', function() {
            const value = $(this).val();
            if (value.length >= 2) {
                // Debounce the search
                clearTimeout($(this).data('timeout'));
                $(this).data('timeout', setTimeout(function() {
                    // Trigger search or autocomplete
                    searchInvoiceNumbers(value);
                }, 300));
            }
        });

        // Function to search invoice numbers
        function searchInvoiceNumbers(searchTerm) {
            $.ajax({
                url: '/mpcs/get-invoice-numbers-search',
                method: 'GET',
                data: { search: searchTerm },
                success: function(data) {
                    // Update dropdown or autocomplete list
                    console.log('Invoice search results:', data);
                },
                error: function() {
                    console.log('Invoice search failed');
                }
            });
        }

        // Ensure visible zeros even before AJAX responses
        $('#pre_F16A_total_purchase_price, #pre_F16A_total_sale_price, #grand_F16A_total_purchase_price, #grand_F16A_total_sale_price').text(__number_f(0, false, false, __currency_precision));

        // Note: form_f22_list_table removed — element #form_f22_list_table does not exist on this page


        // $(document).ready(function() {
        var form16aDatePicker = $('#form_16a_date').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: true,
            locale: {
                format: 'YYYY-MM-DD'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Custom Date Range': [moment().startOf('month'), moment().endOf('month')],
            }
        }, function(start, end, label) {
            if (label === 'Custom Date Range') {
                var prevDate = $('#form_16a_date').data('f16a-prev-date') || moment().format('YYYY-MM-DD');
                $('#form_16a_date').val(prevDate);
                form16aDatePicker.data('daterangepicker').setStartDate(moment(prevDate));
                $('#form_16a_date').data('f16a-custom-range', true);
                $('.custom_date_typing_modal').modal('show');
            }
        });

        $('#form_16a_date').on('apply.daterangepicker', function(ev, picker) {
            if ($('#form_16a_date').data('f16a-custom-range')) {
                $('#form_16a_date').removeData('f16a-custom-range');
                return;
            }
            var formattedDate = picker.startDate.format('YYYY-MM-DD');
            $('#form_16a_date').data('f16a-prev-date', formattedDate);
            $('.from_date').text(formattedDate);
            current_form_number = computeFormNumberForDate(formattedDate);
            if ($.fn.DataTable.isDataTable('#form_16a_live_table')) {
                resetF16AReportState();
                applyFormNumberDisplay(form_16a_table.page.info(), current_form_number);
                form_16a_table.ajax.reload(null, true);
            }
        });

        $('#custom_date_apply_button').on('click', function() {
            let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
            let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

            if (startDate.length === 10 && endDate.length === 10) {
                let formattedStartDate = moment(startDate).format('YYYY-MM-DD');

                $('#form_16a_date').val(formattedStartDate); // Set formatted date in input
                $('#form_16a_date').data('f16a-prev-date', formattedStartDate);
                $('.from_date').text(formattedStartDate);

                // Optional: update your table
                current_form_number = computeFormNumberForDate(formattedStartDate);
                if ($.fn.DataTable.isDataTable('#form_16a_live_table')) {
                    resetF16AReportState();
                    applyFormNumberDisplay(form_16a_table.page.info(), current_form_number);
                    form_16a_table.ajax.reload(null, true);
                }
                // Hide the modal
                $('.custom_date_typing_modal').modal('hide');
            } else {
                alert("Please select both start and end dates.");
            }
        });


        // Cancel resets the input
        $('#form_16a_date').on('cancel.daterangepicker', function(ev, picker) {
            $('#form_16a_date').val('');
            $('#form_16a_date').removeData('f16a-prev-date');
            $('#form_no1').text('');
            $('#F16a_from_no').val('');
            current_form_number = '';
            $('.from_date').text('');
            if ($.fn.DataTable.isDataTable('#form_16a_live_table')) {
                resetF16AReportState();
                form_16a_table.ajax.reload(null, true);
            }
        });

        // S757: keep the default/selected business location as the real value,
        // not only as Select2 display text. F16A must always request data for the
        // same location that is visible to the user.
        var $f16aLocation = $('#16a_location_id');
        var f16aSelectedLocationId = String($f16aLocation.val() || @json((string) ($default_location_id ?? '')));

        $f16aLocation.select2({
            placeholder: '@lang("lang_v1.all")',
            allowClear: false,
            width: '100%'
        });

        function ensureF16ALocationSelection() {
            var currentValue = $f16aLocation.val();

            if (currentValue !== null && currentValue !== '') {
                f16aSelectedLocationId = String(currentValue);
                return f16aSelectedLocationId;
            }

            if (f16aSelectedLocationId !== '') {
                var hasSavedOption = $f16aLocation.find('option').filter(function() {
                    return String(this.value) === f16aSelectedLocationId;
                }).length > 0;

                if (hasSavedOption) {
                    $f16aLocation.val(f16aSelectedLocationId).trigger('change.select2');
                    return f16aSelectedLocationId;
                }
            }

            return '';
        }

        // Re-apply the server default after Select2 initialisation. This prevents
        // generic location/select2 initialisers from leaving the visible label and
        // the underlying select value out of sync.
        ensureF16ALocationSelection();

        // Default date on page load = today
        const today = moment().format('YYYY-MM-DD');
        $('#form_16a_date').val(today);
        $('#form_16a_date').data('f16a-prev-date', today);
        $('.from_date').text(today);
        if ($('#form_16a_date').data('daterangepicker')) {
            $('#form_16a_date').data('daterangepicker').setStartDate(moment(today));
        }

        var initialLocationText = ensureF16ALocationSelection()
            ? $f16aLocation.find('option:selected').text()
            : '@lang("petro::lang.all")';
        $('.f16a_location_name').text(initialLocationText);

        // Rebuilt F16A DataTable: one header row, no DataTables-managed footer,
        // and exactly 11 columns matching the live table structure.
        function initializeF16ADataTable() {
            var $table = $('#form_16a_live_table');
            if (!$table.length) {
                return;
            }

            if ($.fn.DataTable.isDataTable($table[0])) {
                $table.DataTable().clear().destroy();
                $table.find('tbody').empty();
            }

            $table.off('.f16a');
            $table.on('xhr.dt.f16a', function(e, settings, json) {
                if (!json) {
                    return;
                }

                if (json.form_no !== undefined) {
                    current_form_number = json.form_no;
                }
                if (json.report_version !== undefined) {
                    f16aReportVersion = json.report_version || '';
                }
                if (json.report_criteria !== undefined) {
                    f16aReportCriteria = json.report_criteria || '';
                }

                updateF16AServerTotals(json);

                if (json.reset_to_first_page && settings._iDisplayStart > 0 && !f16aResettingToFirstPage) {
                    f16aResettingToFirstPage = true;
                    setTimeout(function() {
                        form_16a_table.page('first').draw('page');
                        f16aResettingToFirstPage = false;
                    }, 0);
                }
            });

            form_16a_table = $table.DataTable({
                processing: true,
                serverSide: true,
                paging: true,
                pageLength: f16aDefaultPageLength,
                lengthMenu: [[25, 50, 100], [25, 50, 100]],
                searching: true,
                searchDelay: 250,
                ordering: false,
                autoWidth: false,
                responsive: false,
                // Keep totals in a dedicated full-width slot between the data rows and
                // the DataTables info/pagination controls. This avoids Bootstrap row
                // negative margins clipping the first/last summary cells.
                dom: "<'row f16a-dt-toolbar'<'col-sm-6'l><'col-sm-6'f>>" +
                     "rt" +
                     "<'f16a-summary-slot'>" +
                     "<'row f16a-dt-footer'<'col-sm-5'i><'col-sm-7'p>>",
                // S755: the F16A report must fit the available screen width.
                // Fixed percentage column widths in the view handle wrapping, so
                // DataTables must not create a horizontal scroll canvas.
                scrollX: false,
                scrollCollapse: false,
                ajax: {
                    url: '/mpcs/get-form-16a',
                    type: 'GET',
                    data: function(d) {
                        var selectedDate = $('#form_16a_date').val();
                        d.start_date = selectedDate || '';
                        d.end_date = selectedDate || '';
                        d.location_id = ensureF16ALocationSelection();
                        d.product_id = $('#16a_product_filter').val() || '';
                        d.report_version = f16aReportVersion;
                        d.report_criteria = f16aReportCriteria;

                        if (selectedDate) {
                            $('.from_date').text(selectedDate);
                        }
                    },
                    error: function(xhr, error, thrown) {
                        console.error('[F16A] AJAX error:', xhr.status, error, thrown, xhr.responseText);
                    }
                },
                columns: [
                    { data: 'index_no', name: 'index_no', defaultContent: '' },
                    { data: 'reference_no', name: 'reference_no', defaultContent: '-' },
                    { data: 'invoice_no', name: 'invoice_no', defaultContent: '-' },
                    { data: 'product', name: 'product', defaultContent: '' },
                    { data: 'location', name: 'location', defaultContent: '' },
                    { data: 'received_qty', name: 'received_qty', defaultContent: '0.00' },
                    { data: 'unit_purchase_price', name: 'unit_purchase_price', defaultContent: '0.00' },
                    { data: 'total_purchase_price', name: 'total_purchase_price', defaultContent: '0.00' },
                    { data: 'unit_sale_price', name: 'unit_sale_price', defaultContent: '0.00' },
                    { data: 'total_sale_price', name: 'total_sale_price', defaultContent: '0.00' },
                    {
                        data: 'stock_book_no',
                        name: 'stock_book_no',
                        defaultContent: '---',
                        render: function(data) {
                            return data && data !== '' ? data : '---';
                        }
                    }
                    // IS2039: the Action column was removed from the table, so its
                    // definition goes too - DataTables errors if the header count
                    // and the columns array disagree. The trailing comma went with
                    // it; older browsers reject a dangling comma in an array.
                ],
                /* Keep the totals immediately after the report rows but before
                 * DataTables info/pagination. The dedicated slot is intentionally
                 * NOT a Bootstrap .row, so it cannot inherit negative margins and
                 * clip the first or last total in screen/print layouts. */
                initComplete: function() {
                    var $wrapper = $('#form_16a_live_table').closest('.dataTables_wrapper');
                    var $summary = $('#form_16a_summary_table');
                    var $slot = $wrapper.find('.f16a-summary-slot').first();

                    if ($slot.length && $summary.length) {
                        $summary.detach().appendTo($slot);
                    }
                },
                drawCallback: function(settings) {
                    var pageInfo = form_16a_table.page.info();
                    var response = settings.json || {};
                    var displayForm = response.form_no !== undefined ? response.form_no : current_form_number;
                    applyFormNumberDisplay(pageInfo, displayForm);
                    updateF16AServerTotals(response);
                }
            });
        }

        // Run after the page and tab partial are fully available. This also wins over
        // any generic DataTables initializer that may have touched the table earlier.
        setTimeout(initializeF16ADataTable, 100);

        $(document).on('shown.bs.tab.f16a', '#mpcs_f16a_tabs a[href="#f16a_form_tab"]', function() {
            if (!$.fn.DataTable.isDataTable('#form_16a_live_table')) {
                setTimeout(initializeF16ADataTable, 50);
            } else {
                form_16a_table.columns.adjust();
            }
        });



        // Print the currently displayed F16A report without replacing the live page.
        $('#print_form_16a_btn').off('click.f16aPrint').on('click.f16aPrint', function(e) {
            e.preventDefault();

            var printArea = document.getElementById('printarea');
            if (!printArea) {
                toastr.error('The F16A print area is not available.');
                return false;
            }

            var printWindow = window.open('', '_blank', 'width=1280,height=850,scrollbars=yes');
            if (!printWindow) {
                toastr.warning('Please allow popups for this site to enable printing.');
                return false;
            }

            var styles = $('link[rel="stylesheet"], style').map(function() {
                return this.outerHTML;
            }).get().join('\n');

            var printCss = [
                '<style>',
                '@page{size:A4 landscape;margin:7mm;}',
                'html,body{width:100%!important;max-width:100%!important;background:#fff!important;margin:0!important;padding:0!important;font-family:Arial,sans-serif!important;font-size:13px!important;color:#263238!important;}',
                '*{box-sizing:border-box!important;}',
                '#printarea,#printarea>.col-md-12,#printarea .f16a-report-container,#form_16a_live_table_wrapper,#form_16a_live_table,#form_16a_summary_table,.f16a-summary-slot{width:100%!important;min-width:0!important;max-width:100%!important;margin-left:0!important;margin-right:0!important;padding-left:0!important;padding-right:0!important;float:none!important;overflow:visible!important;}',
                '#printarea .box,#printarea .box-body,#printarea .box-primary{width:100%!important;max-width:100%!important;border:0!important;box-shadow:none!important;margin:0!important;padding:0!important;}',
                '#printarea .row,#form_16a_live_table_wrapper>.row{margin-left:0!important;margin-right:0!important;}',
                '.dataTables_filter,.dataTables_length,.dataTables_paginate,.dataTables_info,.dt-buttons,.dataTables_processing,.f16a-dt-toolbar,.f16a-dt-footer{display:none!important;}',
                '.dataTables_scroll,.dataTables_scrollHead,.dataTables_scrollHeadInner,.dataTables_scrollBody,.table-responsive{width:100%!important;min-width:0!important;max-width:100%!important;margin:0!important;padding:0!important;border:0!important;overflow:visible!important;}',
                '.f16a-report-header{display:grid!important;grid-template-columns:23% 54% 23%!important;align-items:center!important;width:100%!important;margin:0 0 7px!important;padding:7px 9px!important;border:1px solid #d9e1e8!important;border-radius:0!important;background:#fff!important;}',
                '.f16a-report-label{font-size:13px!important;font-weight:700!important;line-height:1.05!important;}',
                '.f16a-report-value,.f16a-report-business-name{font-size:16px!important;font-weight:700!important;line-height:1.08!important;}',
                '.f16a-report-location{font-size:14px!important;font-weight:600!important;line-height:1.08!important;}',
                '#form_16a_live_table,#form_16a_summary_table{width:100%!important;min-width:0!important;max-width:100%!important;table-layout:fixed!important;border-collapse:collapse!important;border-spacing:0!important;font-family:Arial,sans-serif!important;}',
                '#form_16a_live_table{margin:0!important;border:1px solid #d8e0e7!important;}',
                '#form_16a_live_table th{font-size:13px!important;font-weight:700!important;line-height:1.08!important;padding:5px 2px!important;color:#263238!important;background:#eef2f5!important;border:1px solid #d8e0e7!important;border-bottom:2px solid #c7d0d8!important;white-space:normal!important;word-break:normal!important;overflow-wrap:normal!important;text-align:center!important;vertical-align:middle!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}',
                '#form_16a_live_table td{font-size:13px!important;line-height:1.15!important;padding:5px 3px!important;color:#263238!important;background:#fff!important;border:1px solid #e0e6eb!important;white-space:normal!important;word-break:normal!important;overflow-wrap:anywhere!important;vertical-align:middle!important;}',
                '.f16a-summary-slot{display:block!important;clear:both!important;margin:7px 0 0!important;padding:0!important;}',
                '#form_16a_summary_table{margin:0!important;border:1px solid #cfd8df!important;background:#fff!important;}',
                '#form_16a_summary_table td{font-size:13px!important;line-height:1.15!important;padding:6px 8px!important;color:#263238!important;background:#fff!important;border:1px solid #dde4e9!important;vertical-align:middle!important;}',
                '#form_16a_summary_table .f16a-summary-label{width:27%!important;font-weight:600!important;text-align:left!important;white-space:normal!important;overflow-wrap:anywhere!important;}',
                '#form_16a_summary_table .f16a-summary-value{width:23%!important;font-weight:700!important;text-align:right!important;white-space:nowrap!important;overflow-wrap:normal!important;font-variant-numeric:tabular-nums!important;}',
                '#form_16a_summary_table tr:nth-child(2) td{background:#f8fafb!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}',
                '#form_16a_summary_table tr:last-child td{background:#eef2f5!important;border-top:2px solid #c7d0d8!important;font-weight:700!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}',
                '#form_16a_live_table tr,#form_16a_summary_table tr{break-inside:avoid!important;page-break-inside:avoid!important;}',
                '</style>'
            ].join('');

            printWindow.document.open();
            printWindow.document.write(
                '<!doctype html><html><head><meta charset="utf-8"><title>F16A</title>' +
                styles + printCss + '</head><body>' + printArea.outerHTML +
                '<script>window.addEventListener("load",function(){setTimeout(function(){window.focus();window.print();},350);});<\/script>' +
                '</body></html>'
            );
            printWindow.document.close();

            return false;
        });

        // Note: orphaned form_f22_list_table change handlers removed

        // $('#form_16a_date_range, #16a_location_id').change(function() {
        //     if ($('#16a_location_id').val() !== '' && $('#16a_location_id').val() !== undefined) {
        //         $('.f16a_location_name').text($('#16a_location_id :selected').text());
        //         form_16a_table.ajax.reload();
        //     } else {
        //         $('.f16a_location_name').text('All');
        //         form_16a_table.ajax.reload();
        //     }
        // });

        $f16aLocation.off('change.f16aLocation').on('change.f16aLocation', function () {
            var currentValue = $(this).val();

            // The F16A location is intentionally sticky. If another global script
            // clears the select, immediately restore the last/default location.
            if ((currentValue === null || currentValue === '') && f16aSelectedLocationId !== '') {
                $(this).val(f16aSelectedLocationId).trigger('change.select2');
                currentValue = f16aSelectedLocationId;
            } else if (currentValue !== null && currentValue !== '') {
                f16aSelectedLocationId = String(currentValue);
            }

            var locationText = currentValue
                ? $(this).find('option:selected').text()
                : '@lang("petro::lang.all")';

            $('.f16a_location_name').text(locationText);

            if ($.fn.DataTable.isDataTable('#form_16a_live_table')) {
                resetF16AReportState();
                form_16a_table.ajax.reload(null, true);
            }
        });

        $('#16a_product_filter').on('change', function() {
            let productId = $(this).val();
            if (productId !== previousProduct) {
                previousProduct = productId;
                if ($.fn.DataTable.isDataTable('#form_16a_live_table')) {
                    resetF16AReportState();
                    form_16a_table.ajax.reload(null, true);
                }
            }
        });

        $('#f16a_save_all').click(function(e) {
            e.preventDefault();
            var selectedDate = $('#form_16a_date').val();
            var formNo = current_form_number || $('#F16a_from_no').val();
            var locationId = $('#16a_location_id').val() || '';
            var thisFormTotal = $('#total_this_p').val() || '0';
            var lastFormTotal = $('#pre_F16A_total_purchase_price').text() || '0';
            var grandTotal = $('#grand_F16A_total_purchase_price').text() || '0';

            if (!selectedDate) {
                toastr.error('Please select a date first.');
                return;
            }
            if (!formNo) {
                toastr.error('Form number is not available. Please check date and settings.');
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                method: 'POST',
                url: '/mpcs/save-all-form-f16',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    date: selectedDate,
                    form_no: formNo,
                    location_id: locationId,
                    this_form_total: thisFormTotal,
                    last_form_total: lastFormTotal,
                    grand_total: grandTotal
                },
                success: function(result) {
                    $btn.prop('disabled', false);
                    if (result.success == 0) {
                        toastr.error(result.msg);
                        return;
                    }
                    toastr.success(result.msg);
                },
                error: function() {
                    $btn.prop('disabled', false);
                    toastr.error('An error occurred while saving.');
                }
            });
        });
    }); // End of document ready

    // Functions outside document ready are fine
    function get_previous_value_16a() {
        if ($.fn.DataTable.isDataTable('#form_16a_live_table')) {
            var settings = $('#form_16a_live_table').DataTable().settings()[0];
            updateF16AServerTotals(settings.json || {});
        }
    }

    function caculateF16AFromTotal() {
        get_previous_value_16a();
    }

    // Note: You already defined get_previous_value_16a() above.


</script>




@include('mpcs::partials.module_script', [
    'mpcs_page' => 'f16a',
    'mpcs_config' => [
        'title' => 'MPCS F16A',
    ],
])

@endsection
