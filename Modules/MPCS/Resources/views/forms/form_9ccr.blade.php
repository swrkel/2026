@extends('layouts.app')
@section('title', __('mpcs::lang.form_9_ccr_settings'))

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

        /* IS2346 #2/#3: one stable scroll owner for the F9C Credit page.
         * Do not repeatedly rewrite html/body/theme heights while DataTables is
         * drawing; that behaviour caused the viewport to jump up/down.  The F9C
         * section owns vertical scrolling, while the table-responsive wrapper
         * owns horizontal scrolling only. */
        body.mpcs-f9c-credit-page {
            overflow-x: hidden !important;
        }

        body.mpcs-f9c-credit-page #f9c-credit-page-scroll {
            width: 100%;
            max-height: calc(100vh - 135px);
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior-y: contain;
            scroll-behavior: auto !important;
            overflow-anchor: none;
            scrollbar-gutter: stable;
            padding-bottom: 24px;
        }

        body.mpcs-f9c-credit-page #f9c_credit_form_tab .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        body.mpcs-f9c-credit-page #f9c-credit-tabs,
        body.mpcs-f9c-credit-page #f9c-credit-tabs > .tab-content,
        body.mpcs-f9c-credit-page #f9c-credit-tabs > .tab-content > .tab-pane,
        body.mpcs-f9c-credit-page #f9c_credit_form_tab .box,
        body.mpcs-f9c-credit-page #f9c_credit_form_tab .box-body,
        body.mpcs-f9c-credit-page #form_9ccredit_table_wrapper {
            height: auto !important;
            max-height: none !important;
            overflow-y: visible !important;
        }

        @media (max-width: 768px) {
            .f9c-selected-date-display {
                font-size: 16px;
                padding: 7px 8px;
            }
        }

        @media print {
            body.mpcs-f9c-credit-page #f9c-credit-page-scroll {
                max-height: none !important;
                overflow: visible !important;
                padding-bottom: 0 !important;
            }

            .f9c-selected-date-display {
                color: #000 !important;
                font-size: 11pt;
                margin: 3px 0 6px;
                padding: 0;
            }
        }
    </style>
    <!-- Main content -->
    <section class="content" id="f9c-credit-page-scroll">

        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs" id="f9c-credit-tabs" data-mpcs-tabs data-auto-permission-module="mpcs">
                    <ul class="nav nav-tabs">
                        @if (auth()->user()->can('f9a_form'))
                            <li class="active">
                                <a href="#f9c_credit_form_tab" class="f9c_credit_form_link" role="tab" aria-controls="f9c_credit_form_tab" aria-selected="true" data-mpcs-tab-target="f9c_credit_form_tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.9c_credit_details')</strong>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->can('f9a_settings_form'))
                            <li class="">
                                <a href="#f9c_credit_settings_tab" class="f9c_credit_settings_link" role="tab" aria-controls="f9c_credit_settings_tab" aria-selected="false" data-mpcs-tab-target="f9c_credit_settings_tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.form_9_ccr_settings')</strong>
                                </a>
                            </li>
                        @endif

                    </ul>
                    <div class="tab-content">
                        @if (auth()->user()->can('f9a_form'))
                            <div class="tab-pane active in" id="f9c_credit_form_tab" role="tabpanel">
                                @include('mpcs::forms.partials.9ccr_form')
                            </div>
                        @endif
                        @if (auth()->user()->can('f9a_settings_form'))
                            <div class="tab-pane" id="f9c_credit_settings_tab" role="tabpanel" style="display:none;">
                                @include('mpcs::forms.partials.9ccr_settings_form')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade form_9_ccr_settings_modal" id="form_9_ccr_settings_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade update_form_9_ccr_settings_modal" id="update_form_9_ccr_settings_modal" tabindex="-1"
            role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>
    <!-- /.content -->

@endsection
@section('javascript')
    @include('mpcs::partials.safe_tabs')


    <script>
        $(document).ready(function() {
            // IS2346 #2/#3: keep scroll ownership stable.  No resize/draw
            // handler is allowed to rewrite the page height or scroll position.
            $('body').addClass('mpcs-f9c-credit-page');

            const f9cCreditTabs = document.getElementById('f9c-credit-tabs');

            function activateF9CCreditTab(targetId, sourceLink) {
                if (!f9cCreditTabs || !targetId) return;

                const targetPanel = document.getElementById(targetId);
                if (!targetPanel || !f9cCreditTabs.contains(targetPanel)) return;

                f9cCreditTabs.querySelectorAll(':scope > .nav-tabs > li').forEach(function(item) {
                    item.classList.remove('active');
                });
                f9cCreditTabs.querySelectorAll(':scope > .nav-tabs a[data-mpcs-tab-target]').forEach(function(link) {
                    const selected = link.getAttribute('data-mpcs-tab-target') === targetId;
                    link.setAttribute('aria-selected', selected ? 'true' : 'false');
                    if (selected) link.closest('li').classList.add('active');
                });
                f9cCreditTabs.querySelectorAll(':scope > .tab-content > .tab-pane').forEach(function(panel) {
                    panel.classList.remove('active', 'in');
                    panel.style.display = 'none';
                });

                targetPanel.classList.add('active', 'in');
                targetPanel.style.display = 'block';

                const activeLink = sourceLink || f9cCreditTabs.querySelector('[data-mpcs-tab-target="' + targetId + '"]');
                if (activeLink) $(activeLink).trigger('mpcs.tab.shown');
            }

            // Use a native capture handler so global Bootstrap/tab scripts cannot block this page's tabs.
            if (f9cCreditTabs) {
                f9cCreditTabs.addEventListener('click', function(event) {
                    const link = event.target.closest('a[data-mpcs-tab-target]');
                    if (!link || !f9cCreditTabs.contains(link)) return;

                    event.preventDefault();
                    event.stopPropagation();
                    activateF9CCreditTab(link.getAttribute('data-mpcs-tab-target'), link);
                }, true);
            }
            __currency_precision = {{ $currency_precision ?? 2 }};

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

            function updateF9CCreditSelectedDateDisplay() {
                const $wrapper = $('#form_9ccredit_table_wrapper');
                if (!$wrapper.length) return;

                let $display = $('#f9c_credit_selected_date_display');
                if (!$display.length) {
                    $display = $('<div>', {
                        id: 'f9c_credit_selected_date_display',
                        class: 'f9c-selected-date-display',
                        'aria-live': 'polite'
                    });

                    let $tableTarget = $wrapper.find('.dataTables_scroll').first();
                    if (!$tableTarget.length) {
                        $tableTarget = $wrapper.find('#form_9ccredit_table').first();
                    }

                    if ($tableTarget.length) {
                        $display.insertBefore($tableTarget);
                    } else {
                        $wrapper.append($display);
                    }
                }

                const text = formatF9CSelectedDate($('#9ccr_date_range').val());
                $display.text(text).toggle(Boolean(text));
            }
            let curdate = moment().format('YYYY-MM-DD');


            // Initialize the date picker
            $('#9ccr_date_range').daterangepicker({
                autoUpdateInput: false,
                showDropdowns: true, // To show the dropdown for predefined date ranges
                locale: {
                    format: moment_date_format, // Adjust the date format according to your needs
                    separator: ' ~ ',
                },
                ranges: {
                    /*
                     * IS2014: full system standard range list, matching the F9C
                     * Cash screen (form_9c.blade.php) so both behave the same.
                     * Previously only Today / Yesterday / Custom were offered.
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
                    // If the user selects 'Custom Date Range', show the modal for manual input
                    let curdata_YMD = curdate.split('-');
                    $('#custom_date_from_year1').val(curdata_YMD[0].substring(0, 1));
                    $('#custom_date_from_year2').val(curdata_YMD[0].substring(1, 2));
                    $('#custom_date_from_year3').val(curdata_YMD[0].substring(2, 3));
                    $('#custom_date_from_year4').val(curdata_YMD[0].substring(3, 4));
                    $('#custom_date_from_month1').val(curdata_YMD[1].substring(0, 1));
                    $('#custom_date_from_month2').val(curdata_YMD[1].substring(1, 2));
                    $('#custom_date_from_date1').val(curdata_YMD[2].substring(0, 1));
                    $('#custom_date_from_date2').val(curdata_YMD[2].substring(1, 2));
                    $('#custom_date_to_year1').val(curdata_YMD[0].substring(0, 1));
                    $('#custom_date_to_year2').val(curdata_YMD[0].substring(1, 2));
                    $('#custom_date_to_year3').val(curdata_YMD[0].substring(2, 3));
                    $('#custom_date_to_year4').val(curdata_YMD[0].substring(3, 4));
                    $('#custom_date_to_month1').val(curdata_YMD[1].substring(0, 1));
                    $('#custom_date_to_month2').val(curdata_YMD[1].substring(1, 2));
                    $('#custom_date_to_date1').val(curdata_YMD[2].substring(0, 1));
                    $('#custom_date_to_date2').val(curdata_YMD[2].substring(1, 2));
                    // Show the modal for manual input
                    $('.custom_date_typing_modal').modal('show');
                }
            });

            // When a non-custom range is applied, write the range ourselves so we always get start ~ end
            $('#9ccr_date_range').on('apply.daterangepicker', function(ev, picker) {
                if (picker.chosenLabel !== 'Custom Date Range') {
                    curdate = picker.startDate.format('YYYY-MM-DD');
                    $(this).val(picker.startDate.format(moment_date_format) + ' ~ ' + picker.endDate.format(
                        moment_date_format));
                    updateF9CCreditSelectedDateDisplay();
                    resetF9CCreditReportState();
                    form_9ccredit_table.ajax.reload(null, true);
                }
            });

            // Reset the field when the cancel button is clicked
            $('#9ccr_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#9ccr_date_range').val('');
                updateF9CCreditSelectedDateDisplay();
            });

            // Set the default selected date range when initializing the date picker
            $('#9ccr_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));
            $('#9ccr_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));

            $('#9ccr_date_range').val(moment().startOf('month').format(moment_date_format) + ' ~ ' + moment().endOf(
                'month').format(moment_date_format));

            // Display the selected date range on the page
            let date = $('#9ccr_date_range').val().split(' - ');

            $('.to_date').text(date[1]);

            $('#9ccr_date_range').on('change.f9cSelectedDateDisplay', updateF9CCreditSelectedDateDisplay);

            // $('#9ccr_date_range').change(function() {                
            //       console.log("eccce");
            //       form_9ccredit_table.ajax.reload(null, true);

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
                    if ($('#9ccr_date_range').length) {
                        $('#9ccr_date_range').val(fullRange);
                        $('#9ccr_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#9ccr_date_range').data('daterangepicker').setEndDate(moment(endDate));
                        updateF9CCreditSelectedDateDisplay();
                        resetF9CCreditReportState();
                        form_9ccredit_table.ajax.reload(null, true);

                    }
                    // Hide the modal
                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            });
            //form 9c credit list
            // Monetary totals are authoritative server values. The browser
            // retains only the latest data version and report criteria.
            var f9cCreditReportVersion = '';
            var f9cCreditReportCriteria = '';
            var f9cCreditResettingToFirstPage = false;

            function resetF9CCreditReportState() {
                f9cCreditReportVersion = '';
                f9cCreditReportCriteria = '';

                if (typeof form_9ccredit_table !== 'undefined' &&
                    $.fn.DataTable.isDataTable('#form_9ccredit_table')) {
                    form_9ccredit_table.page('first');
                }
            }

            // Footer totals are calculated on the server for every DataTables draw.
            form_9ccredit_table = $('#form_9ccredit_table').DataTable({
                autoWidth: false,
                processing: true,
                serverSide: true,
                deferRender: true,
                // Native .table-responsive owns horizontal scrolling.  Do not
                // enable DataTables scrollX here because its cloned header breaks
                // the two-row F9C heading alignment.
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
                    "url": "/mpcs/get-9ccredit-form",
                    data: function(d) {
                        const dateRange = $('#9ccr_date_range').val();

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
                        d.report_version = f9cCreditReportVersion;
                        d.report_criteria = f9cCreditReportCriteria;
                    }
                },

                columns: [{
                        data: 'billno',
                        name: 'billno'
                    },
                    {
                        data: 'ourref',
                        name: 'ourref'
                    },
                    {
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(data);
                        }
                    },
                    {
                        data: 'unit_price',
                        name: 'unit_price',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(data, false, false, __currency_precision);
                        }
                    },
                    {
                        data: 'page',
                        name: 'page'
                    },
                    {
                        data: 'final_total_rs',
                        name: 'final_total_rs',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(row.final_total_rs, false, false,
                                __currency_precision);
                        }
                    },
                    {
                        data: 'goods_rs',
                        name: 'goods_rs',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(row.goods_rs, false, false, __currency_precision);
                        }
                    },
                    {
                        data: 'loading_rs',
                        name: 'loading_rs',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(row.loading_rs, false, false, __currency_precision);
                        }
                    },
                    {
                        data: 'empty_rs',
                        name: 'empty_rs',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(row.empty_rs, false, false, __currency_precision);
                        }
                    },
                    {
                        data: 'transport_rs',
                        name: 'transport_rs',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(row.transport_rs, false, false, __currency_precision);
                        }
                    },
                    {
                        data: 'other_rs',
                        name: 'other_rs',
                        className: 'text-right',
                        render: function(data, type, row) {
                            if (data == null || data === '') return '';
                            return __number_f(row.other_rs, false, false, __currency_precision);
                        }
                    },

                ],
                initComplete: function() {
                    updateF9CCreditSelectedDateDisplay();
                },
                fnDrawCallback: function(oSettings) {
                    updateF9CCreditSelectedDateDisplay();
                    var api = this.api();
                    var response = api.ajax.json() || oSettings.json || {};
                    var browserPage = api.page.info().page;

                    f9cCreditReportVersion = String(response.report_version || f9cCreditReportVersion || '');
                    f9cCreditReportCriteria = String(response.report_criteria || f9cCreditReportCriteria || '');

                    if (response.reset_to_first_page && browserPage > 0) {
                        if (!f9cCreditResettingToFirstPage) {
                            f9cCreditResettingToFirstPage = true;
                            window.setTimeout(function() {
                                api.page('first').draw('page');
                            }, 0);
                        }
                        return;
                    }
                    f9cCreditResettingToFirstPage = false;

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

                    var formNo = parseInt(response.form_9ccr_no ?? 0, 10) || 0;
                    $('#form_9ccr_no').html(formNo > 0 ? formNo : '-');
                    $('#custom_message').html(response.custom_message ?? '');
                }
            });

            let form9ccrSettingsTable = null;

            function updateF9CCreditAddButtonState() {
                const hasRows = $('#form_9ccr_settings_table tbody tr').not(':has(td.dataTables_empty)').length > 0;
                $('#add_form_9ccr_settings_button').prop('disabled', hasRows);
            }

            function ensureF9CCreditSettingsTable() {
                if (form9ccrSettingsTable) {
                    form9ccrSettingsTable.ajax.reload(function() {
                        form9ccrSettingsTable.columns.adjust();
                        updateF9CCreditAddButtonState();
                    }, false);
                    return;
                }

                form9ccrSettingsTable = $('#form_9ccr_settings_table').DataTable({
                    processing: true,
                    serverSide: true,
                    paging: false,
                    searching: false,
                    info: false,
                    ajax: {
                        type: 'get',
                        url: "{{ url('/mpcs/get-form-9ccr-settings') }}",
                        error: function(xhr) {
                            console.error('F9C Credit settings load failed:', xhr.responseText || xhr.statusText);
                            toastr.error(xhr.responseJSON?.message || 'Unable to load F 9C Credit settings.');
                        }
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
                    ],
                    drawCallback: function() {
                        this.api().columns.adjust();
                        updateF9CCreditAddButtonState();
                    }
                });
            }

            $('.f9c_credit_settings_link').on('mpcs.tab.shown', function() {
                ensureF9CCreditSettingsTable();
            });

            function submitF9CCreditSettingsForm($form, modalSelector) {
                const $submit = $form.find('button[type="submit"]');
                $submit.prop('disabled', true);

                $.ajax({
                    method: $form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    data: $form.serialize(),
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            $(modalSelector).modal('hide');
                            ensureF9CCreditSettingsTable();
                        } else {
                            toastr.error(result.msg || 'Unable to save F 9C Credit settings.');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || (xhr.responseJSON?.errors
                            ? Object.values(xhr.responseJSON.errors).flat().join('<br>')
                            : 'Unable to save F 9C Credit settings.');
                        toastr.error(message);
                    },
                    complete: function() {
                        $submit.prop('disabled', false);
                    }
                });
            }

            $(document).on('submit', 'form#add_9ccr_form_settings', function(e) {
                e.preventDefault();
                submitF9CCreditSettingsForm($(this), '.form_9_ccr_settings_modal');
            });

            $(document).on('submit', 'form#update_9ccr_form_settings', function(e) {
                e.preventDefault();
                submitF9CCreditSettingsForm($(this), '.update_form_9_ccr_settings_modal');
            });

            $("#print_div").click(function() {
                printDiv();
            });

            function printDiv() {
                printClean("{{ URL::to('/') }}/mpcs/form-9c");
            }

            function printDivs() {
                printClean("{{ URL::to('/') }}/mpcs/form-9ccr");
            }

            function printClean(redirectUrl) {
                const table = document.getElementById('form_9ccredit_table');
                if (!table) return;

                const businessName = document.getElementById('business_name_print')?.innerText || '';
                const title = document.getElementById('credit_sales_title')?.innerText || '';
                const formNo = document.getElementById('form_9ccr_no')?.innerText || '';
                const selectedDate = $('#9ccr_date_range').val() || '';
                const printableTable = table.cloneNode(true);
                $(printableTable).find('.dataTables_empty').closest('tr').remove();

                const printWindow = window.open('', '_blank', 'width=1200,height=850');
                if (!printWindow) {
                    window.print();
                    return;
                }
                printWindow.document.open();
                printWindow.document.write(`<!doctype html><html><head><title>F9C Credit</title><style>
                    @page { size: A4 landscape; margin: 6mm; }
                    html, body { margin:0; padding:0; font-family:Arial,sans-serif; color:#000; font-size:10pt; }
                    * { box-sizing:border-box; }
                    .header { text-align:center; margin-bottom:4px; line-height:1.15; }
                    .business-name { font-size:16pt; font-weight:700; }
                    .title { font-size:13pt; font-weight:700; }
                    .meta { display:flex; justify-content:space-between; font-weight:700; margin:2px 0 5px; }
                    table { width:100%; border-collapse:collapse; table-layout:fixed; margin:0; }
                    th, td { border:1px solid #000; padding:3px 4px; text-align:center; line-height:1.2; overflow-wrap:anywhere; }
                    .footer { margin-top:10px; page-break-inside:avoid; }
                    .footer td { border:0; padding-top:15px; }
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


        });
    </script>
@endsection
