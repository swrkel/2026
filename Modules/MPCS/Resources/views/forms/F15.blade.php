@extends('layouts.app')
@section('title', __('mpcs::lang.15_form'))
@section('content')
    <!-- Main content -->
    <section class="content" id="mpcs-page" data-mpcs-page="f15" style="padding-block: 10px">
        <div class="page-title-area">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div class="breadcrumbs-area clearfix">
                        <h4 class="page-title pull-left">FORM F15</h4>
                        <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                            <li><a href="#">F15</a></li>
                            <li><span>Last Record</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs" data-mpcs-tabs>
                    <ul class="nav nav-tabs">
                        @if(auth()->user()->can('f15_form'))
                            <li class="{{ !empty($openDailyReport) ? '' : 'active' }}">
                                <a href="#f15_form_tab" class="f15-page-tab" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.15_form')</strong>
                                </a>
                            </li>
                        @endif
                        @if(auth()->user()->can('f15_form'))
                            <li class="">
                                <a href="#f15_form_settings_tab" class="f15-page-tab" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i>
                                    <strong>@lang('mpcs::lang.15_form_settings')</strong>
                                </a>
                            </li>
                        @endif
                        @if(auth()->user()->can('f15_form'))
                            <li class="{{ !empty($openDailyReport) ? 'active' : '' }}">
                                <a href="#f15_daily_report_tab" class="f15-page-tab" data-toggle="tab">
                                    <i class="fa fa-line-chart"></i>
                                    <strong>F 15 Daily Report - New</strong>
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="tab-content">
            @if(auth()->user()->can('f15_form'))
                <div class="tab-pane {{ !empty($openDailyReport) ? '' : 'active in' }}" id="f15_form_tab">
                    @include('mpcs::forms.partials.15_forms')
                </div>
            @endif
            @if(auth()->user()->can('f15_form'))
                <div class="tab-pane" id="f15_form_settings_tab">
                    @include('mpcs::forms.partials.list_f15')
                </div>
            @endif
            @if(auth()->user()->can('f15_form'))
                <div class="tab-pane {{ !empty($openDailyReport) ? 'active in' : '' }}" id="f15_daily_report_tab">
                    @include('mpcs::forms.partials.f15_daily_report_content')
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
    @if(auth()->user()->can('f15_form'))
        @include('mpcs::forms.partials.f15_daily_report_scripts')
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            var f15Request = null;
            var f15DataUrl = @json(url('/mpcs/get-15-setting-data'));
            var f15ErrorMessage = @json(__('messages.something_went_wrong'));

            var f15IdMap = {
                form_number: '15f_form_no',
                form_9a_number: 'form_9a_number',
                store_purchase_book_no: 'store_purchase_book_no',
                direct_purchase_book_no: 'direct_purchase_book_no',
                opening_f22_book_refs: 'opening_stock_f22_book',
                price_increment_form_numbers: 'price_increment_form_numbers',
                price_reduction_form_numbers: 'price_reduction_form_numbers'
            };

            var f15ClassKeys = [
                'store_purchase_previous', 'store_purchase_today', 'store_purchase_total',
                'direct_purchase_previous', 'direct_purchase_today', 'direct_purchase_total',
                'sub_total_previous', 'sub_total_today', 'sub_total_total',
                'total_purchase_previous', 'total_purchase_today', 'total_purchase_total',
                'price_increment_previous', 'price_increment_today', 'price_increment_total',
                'opening_stock_previous', 'opening_stock_today', 'opening_stock_total',
                'cash_previous', 'cash_today', 'cash_total',
                'card_previous', 'card_today', 'card_total',
                'credit_previous', 'credit_today', 'credit_total',
                'price_reduction_previous', 'price_reduction_today', 'price_reduction_total',
                'grand_total1_previous', 'grand_total1_today', 'grand_total1_total',
                'total_sale_previous', 'total_sale_today', 'total_sale_total',
                'balance_stock_previous', 'balance_stock_today', 'balance_stock_total',
                'grand_total2_previous', 'grand_total2_today', 'grand_total2_total'
            ];

            function activateF15Tab(targetId) {
                if (!targetId || !document.getElementById(targetId)) {
                    return;
                }

                $('.settlement_tabs .nav-tabs li').removeClass('active');
                $('.settlement_tabs .nav-tabs a').each(function() {
                    if ($(this).attr('href') === '#' + targetId) {
                        $(this).closest('li').addClass('active');
                    }
                });

                $('#mpcs-page .settlement_tabs > .tab-content > .tab-pane').removeClass('active in show').hide();
                $('#' + targetId).addClass('active in').show();

                if (targetId === 'f15_form_settings_tab' &&
                    $.fn.DataTable &&
                    $.fn.DataTable.isDataTable('#form_15_settings_table')) {
                    var settingsTable = $('#form_15_settings_table').DataTable();
                    settingsTable.ajax.reload(null, false);
                    settingsTable.columns.adjust();
                }
            }

            function normalizeF15Date(value) {
                var raw = String(value || '').trim();
                if (!raw) {
                    return window.moment ? moment().format('YYYY-MM-DD') : new Date().toISOString().slice(0, 10);
                }

                var firstPart = raw.split(/\s*(?:~|\bto\b|\s+-\s+)\s*/i)[0].trim();
                var isoMatch = firstPart.match(/^(\d{4}-\d{2}-\d{2})/);
                if (isoMatch) {
                    return isoMatch[1];
                }

                if (window.moment) {
                    var parsed = moment(firstPart, [
                        'MM/DD/YYYY',
                        'DD/MM/YYYY',
                        'YYYY/MM/DD',
                        'DD-MM-YYYY',
                        'MM-DD-YYYY'
                    ], true);
                    if (parsed.isValid()) {
                        return parsed.format('YYYY-MM-DD');
                    }
                }

                return window.moment ? moment().format('YYYY-MM-DD') : new Date().toISOString().slice(0, 10);
            }

            function selectedF15Date() {
                var $dateInput = $('#form_15_date_range');
                var picker = $dateInput.data('daterangepicker');
                if (picker && picker.startDate) {
                    return picker.startDate.format('YYYY-MM-DD');
                }
                return normalizeF15Date($dateInput.val());
            }

            function setF15Loading(isLoading) {
                $('#form_f15_table')
                    .attr('aria-busy', isLoading ? 'true' : 'false')
                    .toggleClass('mpcs-loading', !!isLoading);
            }

            function setF15TextById(id, value) {
                var element = document.getElementById(id);
                if (element) {
                    element.textContent = value === null || value === undefined ? '' : String(value);
                }
            }

            function setF15TextByClass(className, value) {
                var text = value === null || value === undefined ? '' : String(value);
                document.querySelectorAll('.' + className).forEach(function(element) {
                    element.textContent = text;
                });
            }

            function renderF15Data(result) {
                result = result || {};

                Object.keys(f15IdMap).forEach(function(key) {
                    setF15TextById(f15IdMap[key], result[key]);
                });

                f15ClassKeys.forEach(function(key) {
                    setF15TextByClass(key, result[key]);
                });

                var page = document.getElementById('mpcs-page');
                if (page) {
                    page.setAttribute('data-f15-monthly-reset', result.is_monthly_reset ? '1' : '0');
                    page.setAttribute('data-f15-f22-reset', result.is_f22_reset ? '1' : '0');
                }
            }

            function loadF15Data() {
                var reportDate = selectedF15Date();
                if (!reportDate) {
                    return;
                }

                $('#form_15_date_range').val(reportDate);

                if (f15Request && f15Request.readyState !== 4) {
                    f15Request.abort();
                }

                setF15Loading(true);
                f15Request = $.ajax({
                    url: f15DataUrl,
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    data: {
                        start_date: reportDate
                    },
                    success: function(result) {
                        renderF15Data(result);
                    },
                    error: function(xhr, status) {
                        if (status === 'abort') {
                            return;
                        }

                        var message = f15ErrorMessage;
                        if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) {
                            message = xhr.responseJSON.message || xhr.responseJSON.msg;
                        }

                        if (window.toastr) {
                            toastr.error(message);
                        } else {
                            console.error('[MPCS:F15] ' + message);
                        }
                    },
                    complete: function() {
                        setF15Loading(false);
                    }
                });
            }

            function initializeF15DatePicker() {
                var $dateInput = $('#form_15_date_range');
                var initialDate = normalizeF15Date($dateInput.val());
                $dateInput.val(initialDate);

                if ($.fn.daterangepicker && window.moment) {
                    var existingPicker = $dateInput.data('daterangepicker');
                    if (existingPicker && typeof existingPicker.remove === 'function') {
                        existingPicker.remove();
                    }

                    $dateInput.daterangepicker({
                        singleDatePicker: true,
                        showDropdowns: true,
                        autoUpdateInput: true,
                        startDate: moment(initialDate, 'YYYY-MM-DD'),
                        endDate: moment(initialDate, 'YYYY-MM-DD'),
                        locale: {
                            format: 'YYYY-MM-DD'
                        }
                    });

                    $dateInput.off('.mpcsF15')
                        .on('apply.daterangepicker.mpcsF15', function(event, picker) {
                            $(this).val(picker.startDate.format('YYYY-MM-DD'));
                            loadF15Data();
                        })
                        .on('change.mpcsF15', function() {
                            var normalized = normalizeF15Date(this.value);
                            this.value = normalized;
                            var picker = $(this).data('daterangepicker');
                            if (picker) {
                                picker.setStartDate(normalized);
                                picker.setEndDate(normalized);
                            }
                            loadF15Data();
                        });
                } else {
                    $dateInput.off('.mpcsF15').on('change.mpcsF15 blur.mpcsF15', function() {
                        this.value = normalizeF15Date(this.value);
                        loadF15Data();
                    });
                }

                loadF15Data();
            }

            $(document).on('click', '.settlement_tabs .nav-tabs a[data-toggle="tab"]', function(event) {
                var targetId = String($(this).attr('href') || '').replace(/^#/, '');
                if (!targetId || !document.getElementById(targetId)) {
                    return;
                }
                event.preventDefault();
                activateF15Tab(targetId);
            });

            $(document).on('click', '#printButton', function(event) {
                event.preventDefault();
                var source = document.getElementById('f15_print_area');
                if (!source) {
                    return;
                }

                var clone = source.cloneNode(true);
                $(source).find('input, textarea, select').each(function(index) {
                    var target = $(clone).find('input, textarea, select').get(index);
                    if (!target) return;
                    target.value = this.value;
                });

                var printWindow = window.open('', '_blank', 'width=1200,height=850');
                if (!printWindow) {
                    window.print();
                    return;
                }

                printWindow.document.open();
                printWindow.document.write(`<!doctype html><html><head><title>F15</title><style>
                    @page { size: A4 portrait; margin: 8mm; }
                    html, body { margin:0; padding:0; font-family:Arial,sans-serif; font-size:9px; color:#000; }
                    * { box-sizing:border-box; }
                    table { width:100%; border-collapse:collapse; table-layout:fixed; }
                    th, td { border:1px solid #000; padding:2px 3px; line-height:1.15; vertical-align:middle; }
                    th { text-align:center; }
                    .text-right { text-align:right !important; }
                    .text-center { text-align:center !important; }
                    .f15-summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:3px 6px; margin:3px 0 5px; }
                    .f15-summary-field { display:grid; grid-template-columns:1fr 1fr; align-items:center; gap:3px; }
                    input { width:100%; border:0; border-bottom:1px solid #000; height:16px; font-size:8px; }
                    h3,h5 { margin:2px 0; }
                    .no-print, button, .dataTables_wrapper .row { display:none !important; }
                </style></head><body>${clone.outerHTML}</body></html>`);
                printWindow.document.close();
                printWindow.focus();
                printWindow.onload = function() { printWindow.print(); };
                printWindow.onafterprint = function() { printWindow.close(); };
            });

            $(document).on('click', '#f15_close_alert', function() {
                $('#custom-alert').hide();
            });

            window.f15ReloadData = loadF15Data;
            initializeF15DatePicker();
        });
    </script>
@endsection
