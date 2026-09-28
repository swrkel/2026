@extends('layouts.app')
@section('title', __('vat::lang.vat_statements'))

@section('content')
@php
    /*
     * S584: use real page navigation for the VAT Statement headings.
     * This deliberately does not depend on Bootstrap's JavaScript tab plugin or
     * any global click handler. Each heading opens the same page with a server-
     * selected active section, so navigation still works even if another module
     * has a broken/duplicated tab listener.
     */
    $vat_statement_tab = request()->query('tab', 'tax_invoice');
    $vat_statement_tabs = [
        'tax_invoice',
        'list_statements',
        'prefixes',
        'statement_settings',
        'statement_fonts',
        'invoice_126',
    ];

    if (!in_array($vat_statement_tab, $vat_statement_tabs, true)) {
        $vat_statement_tab = 'tax_invoice';
    }

    if (!$enable_126_statement && $vat_statement_tab === 'invoice_126') {
        $vat_statement_tab = 'tax_invoice';
    }

    $vat_statement_page_url = url()->current();
@endphp
<section class="content main-content-inner vat-statement-page">
    <style>
        @page { size: auto; margin: 5mm; }

        .vat-statement-page {
            width: 100%;
            max-width: 100%;
            padding: 14px 16px 34px;
            overflow: visible;
        }
        .vat-statement-page .settlement_tabs {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            margin-bottom: 12px;
        }
        .vat-statement-page .nav-tabs {
            display: block;
            width: max-content;
            min-width: 100%;
            white-space: nowrap;
            margin: 0;
        }
        .vat-statement-page .nav-tabs > li {
            float: none;
            display: inline-block;
            margin: 0 4px 0 0;
            vertical-align: bottom;
        }
        .vat-statement-page .nav-tabs > li > a {
            position: relative;
            display: block;
            margin: 0;
            cursor: pointer;
            pointer-events: auto !important;
            text-decoration: none;
            z-index: 2;
        }
        .vat-statement-page .settlement_tabs {
            position: relative;
            z-index: 5;
        }
        .vat-statement-page .tab-content,
        .vat-statement-page .tab-pane,
        .vat-statement-page .tab-pane > section.content {
            width: 100%;
            max-width: 100%;
        }
        .vat-statement-page .tab-pane > section.content {
            margin: 0;
            padding: 0;
        }
        .vat-statement-page .table-responsive,
        .vat-statement-page .vat-action-table-wrap,
        .vat-statement-page .dataTables_wrapper,
        .vat-statement-page .dataTables_scrollBody {
            overflow: visible !important;
        }
        .vat-statement-page table.dataTable tbody td {
            vertical-align: middle;
        }
        .vat-statement-page .btn-group,
        .vat-statement-page .dropdown {
            position: relative;
        }
        .vat-statement-page .dropdown-menu {
            z-index: 1070;
            min-width: 205px;
        }
        .vat-statement-page .dropdown-toggle {
            margin: 0 !important;
        }
        .vat-statement-page .fuel_tank_modal .modal-dialog,
        .vat-statement-page .customer_statement_modal .modal-dialog,
        .vat-statement-page .pay_contact_due_modal .modal-dialog {
            max-width: 1100px;
        }
        .vat-statement-page .fuel_tank_modal .modal-body,
        .vat-statement-page .customer_statement_modal .modal-body,
        .vat-statement-page .pay_contact_due_modal .modal-body {
            overflow: visible;
        }
        .select2-container--open { z-index: 10650 !important; }

        #customer_statement_table > tbody > tr > td {
            padding: 3px 5px !important;
        }
        #customer_statement_list_table_wrapper .dt-buttons,
        #prefixes_table_wrapper .dt-buttons,
        #logos_table_wrapper .dt-buttons {
            margin-bottom: 10px;
        }

        @media print {
            .dt-buttons,
            .dataTables_length,
            .dataTables_filter,
            .dataTables_info,
            .dataTables_paginate,
            .notexport,
            .no-print {
                display: none !important;
            }
            #print_header_div { display: block !important; }
            .customer_details_div { display: none !important; }
        }
    </style>

    <div class="row no-print">
        <div class="col-md-12 dip_tab">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs" id="vat_statement_main_tabs" role="navigation">
                    <li class="{{ $vat_statement_tab === 'tax_invoice' ? 'active' : '' }}">
                        <a class="vat-statement-page-link" href="{{ $vat_statement_page_url }}?tab=tax_invoice">
                            <i class="fa fa-superpowers"></i> <strong>@lang('vat::lang.vat_statements')</strong>
                        </a>
                    </li>
                    <li class="{{ $vat_statement_tab === 'list_statements' ? 'active' : '' }}">
                        <a class="vat-statement-page-link" href="{{ $vat_statement_page_url }}?tab=list_statements">
                            <i class="fa fa-list"></i> <strong>@lang('vat::lang.list_vat_statements')</strong>
                        </a>
                    </li>
                    <li class="{{ $vat_statement_tab === 'prefixes' ? 'active' : '' }}">
                        <a class="vat-statement-page-link" href="{{ $vat_statement_page_url }}?tab=prefixes">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.prefix_and_starting_nos')</strong>
                        </a>
                    </li>
                    <li class="{{ $vat_statement_tab === 'statement_settings' ? 'active' : '' }}">
                        <a class="vat-statement-page-link" href="{{ $vat_statement_page_url }}?tab=statement_settings">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.statement_settings')</strong>
                        </a>
                    </li>
                    <li class="{{ $vat_statement_tab === 'statement_fonts' ? 'active' : '' }}">
                        <a class="vat-statement-page-link" href="{{ $vat_statement_page_url }}?tab=statement_fonts">
                            <i class="fa fa-font"></i> <strong>@lang('vat::lang.customer_statement_font_setting')</strong>
                        </a>
                    </li>
                    @if($enable_126_statement)
                        <li class="{{ $vat_statement_tab === 'invoice_126' ? 'active' : '' }}">
                            <a class="vat-statement-page-link" href="{{ $vat_statement_page_url }}?tab=invoice_126">
                                <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.126_statement')</strong>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="tab-content" id="vat_statement_main_tab_content">
        <div class="tab-pane {{ $vat_statement_tab === 'tax_invoice' ? 'active in' : '' }}" id="customer_statements">
            @include('vat::customer_statement.partials.customer_statements')
        </div>
        <div class="tab-pane {{ $vat_statement_tab === 'list_statements' ? 'active in' : '' }}" id="list_customer_statements">
            @include('vat::customer_statement.partials.list_customer_statements')
        </div>
        <div class="tab-pane {{ $vat_statement_tab === 'prefixes' ? 'active in' : '' }}" id="vat_prefixes">
            @include('vat::vat_statement_prefixes.index')
        </div>
        <div class="tab-pane {{ $vat_statement_tab === 'statement_settings' ? 'active in' : '' }}" id="vat_logos">
            @include('vat::customer_statement.logos.index')
        </div>
        <div class="tab-pane {{ $vat_statement_tab === 'statement_fonts' ? 'active in' : '' }}" id="font-setting">
            @include('vat::customer_statement.font-setting')
        </div>
        @if($enable_126_statement)
            <div class="tab-pane {{ $vat_statement_tab === 'invoice_126' ? 'active in' : '' }}" id="126_statement">
                @include('vat::customer_statement.126_statement')
            </div>
        @endif
    </div>

    <div class="modal fade customer_statement_modal" tabindex="-1" role="dialog"></div>
    <div class="modal fade contact_modal" tabindex="-1" role="dialog"></div>
    <div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog"></div>
    <div class="modal fade fuel_tank_modal" tabindex="-1" role="dialog"></div>

    <div class="hide"><div id="report_print_div"></div></div>
</section>
@endsection

@section('javascript')
<script>
(function ($) {
    'use strict';

    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var statementTable = null;
    var statementListTable = null;
    var prefixesTable = null;
    var logosTable = null;
    var filterTimer = null;

    function notifyAjaxError(xhr) {
        var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
        var message = response.msg || response.message;
        if (!message && xhr && typeof xhr.responseText === 'string') {
            var text = $.trim(xhr.responseText);
            if (text && text.charAt(0) !== '<' && text.length <= 500) {
                message = text;
            }
        }
        if (!message && response.errors) {
            var keys = Object.keys(response.errors);
            if (keys.length) {
                message = response.errors[keys[0]][0];
            }
        }
        toastr.error(message || @json(__('messages.something_went_wrong')));
    }

    function initSelect2($container) {
        if (!$.fn.select2) { return; }
        ($container || $(document)).find('select.select2').each(function () {
            var $select = $(this);
            if ($select.data('select2')) { return; }
            var $modal = $select.closest('.modal');
            var options = { width: '100%' };
            if ($modal.length) { options.dropdownParent = $modal; }
            $select.select2(options);
        });
    }

    function reloadDataTable(selector, resetPaging) {
        var node = $(selector)[0];
        if (!node || !$.fn.dataTable || !$.fn.dataTable.isDataTable(node)) { return; }
        var table = $(node).DataTable();
        if (table.ajax) {
            table.ajax.reload(null, resetPaging === true);
        } else {
            table.draw(false);
        }
    }

    function initDateRange(selector, startMoment, endMoment, onChange) {
        var $input = $(selector);
        if (!$input.length || !$.fn.daterangepicker) { return; }

        var oldPicker = $input.data('daterangepicker');
        if (oldPicker && oldPicker.remove) { oldPicker.remove(); }

        $input.daterangepicker($.extend(true, {}, dateRangeSettings, {
            startDate: startMoment,
            endDate: endMoment
        }), function (start, end) {
            $input.val(start.format(moment_date_format) + ' - ' + end.format(moment_date_format));
            if (typeof onChange === 'function') { onChange(); }
        });

        var picker = $input.data('daterangepicker');
        picker.setStartDate(startMoment);
        picker.setEndDate(endMoment);
        $input.val(startMoment.format(moment_date_format) + ' - ' + endMoment.format(moment_date_format));

        $input.off('cancel.daterangepicker.vat change.vatRange')
            .on('cancel.daterangepicker.vat', function () {
                $input.val('');
                if (typeof onChange === 'function') { onChange(); }
            })
            .on('change.vatRange', function () {
                var values = ($input.val() || '').split(/\s+-\s+/);
                if (values.length === 2) {
                    var start = moment(values[0], moment_date_format, true);
                    var end = moment(values[1], moment_date_format, true);
                    if (start.isValid() && end.isValid()) {
                        picker.setStartDate(start);
                        picker.setEndDate(end);
                    }
                }
                if (typeof onChange === 'function') { onChange(); }
            });
    }

    function readDateRange(selector) {
        var $input = $(selector);
        var picker = $input.data('daterangepicker');
        if (!$input.val() || !picker) {
            return { start: '', end: '', displayStart: '', displayEnd: '' };
        }
        return {
            start: picker.startDate.format('YYYY-MM-DD'),
            end: picker.endDate.format('YYYY-MM-DD'),
            displayStart: picker.startDate.format('DD-MM-YYYY'),
            displayEnd: picker.endDate.format('DD-MM-YYYY')
        };
    }

    function getStatementPreviewFilters() {
        var range = readDateRange('#customer_statement_date_range');
        return {
            customer_id: $('#customer_statement_customer_id').val() || '',
            customer_type: $('#customer_statement_customer_type').val() || 'all',
            reference: $('#customer_statement_reference').val() || '',
            search_term: $('#customer_statement_search').val() || '',
            start_date: range.start,
            end_date: range.end,
            price_adjustment: typeof __read_number === 'function'
                ? __read_number($('#price_adjustment'))
                : ($('#price_adjustment').val() || 0),
            displayStart: range.displayStart,
            displayEnd: range.displayEnd
        };
    }

    function loadStatementHeader(filters) {
        $('.from_date').text(filters.displayStart);
        $('.to_date').text(filters.displayEnd);

        if (!filters.customer_id) {
            $('#print_header_div').empty();
            $('#print_footer_div').empty();
            return;
        }

        $.ajax({
            url: '/vat-module/get-customer-statement-no',
            method: 'GET',
            dataType: 'json',
            cache: false,
            data: {
                customer_id: filters.customer_id,
                start_date: filters.start_date,
                end_date: filters.end_date,
                price_adjustment: filters.price_adjustment
            }
        }).done(function (result) {
            $('#statement_no').val(result.statement_no || '');
            $('#print_header_div').html(result.header || '');
            $('#print_footer_div').html(result.footer || '');

            if (result.date_from) {
                $('.from_date').text(moment(result.date_from, 'YYYY-MM-DD').format('DD-MM-YYYY'));
            }
            if (result.date_to) {
                $('.to_date').text(moment(result.date_to, 'YYYY-MM-DD').format('DD-MM-YYYY'));
            }
        }).fail(notifyAjaxError);
    }

    window.loadStatements = function () {
        var filters = getStatementPreviewFilters();
        if (statementTable && statementTable.ajax) {
            statementTable.ajax.reload(null, false);
        }
        loadStatementHeader(filters);
    };

    function scheduleStatementReload() {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(window.loadStatements, 180);
    }

    function initStatementTable() {
        if (!$('#customer_statement_table').length) { return; }
        if ($.fn.dataTable.isDataTable('#customer_statement_table')) {
            statementTable = $('#customer_statement_table').DataTable();
            window.customer_statement_table = statementTable;
            return;
        }

        statementTable = $('#customer_statement_table').DataTable({
            processing: true,
            serverSide: false,
            searching: false,
            ordering: false,
            pageLength: -1,
            ajax: {
                url: '/vat-module/customer-statement',
                data: function (d) {
                    var filters = getStatementPreviewFilters();
                    d.customer_id = filters.customer_id;
                    d.customer_type = filters.customer_type;
                    d.reference = filters.reference;
                    d.search_term = filters.search_term;
                    d.start_date = filters.start_date;
                    d.end_date = filters.end_date;
                }
            },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'order_no', name: 'order_no' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'final_total', name: 'final_total', className: 'text-right' },
                { data: 'route_name', name: 'route_name' },
                { data: 'reference', name: 'reference' },
                { data: 'vehicle_number', name: 'vehicle_number' },
                { data: 'quantity', name: 'quantity', className: 'text-right' },
                { data: 'product', name: 'product' },
                { data: 'unit_price', name: 'unit_price', className: 'text-right' },
                { data: 'final_total', name: 'final_total', className: 'text-right' },
                { data: 'due_amount', name: 'due_amount', className: 'text-right' }
            ],
            drawCallback: function () {
                if (typeof sum_table_col === 'function') {
                    $('#due_total').val(sum_table_col($('#customer_statement_table'), 'due'));
                }
            }
        });
        window.customer_statement_table = statementTable;
    }

    function listRangeData(selector) {
        var range = readDateRange(selector);
        return { start: range.start, end: range.end };
    }

    function initStatementListTable() {
        if (!$('#customer_statement_list_table').length) { return; }
        if ($.fn.dataTable.isDataTable('#customer_statement_list_table')) {
            statementListTable = $('#customer_statement_list_table').DataTable();
            window.customer_statement_list_table = statementListTable;
            return;
        }

        statementListTable = $('#customer_statement_list_table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            responsive: false,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [[1, 'desc']],
            ajax: {
                url: '/vat-module/customer-statement/get-statement-list',
                cache: false,
                data: function (d) {
                    var statementRange = listRangeData('#list_customer_statement_date_range');
                    var printedRange = listRangeData('#printed_list_customer_statement_date_range');
                    d.location_id = $('#list_customer_statement_location_id').val() || '';
                    d.customer_id = $('#list_customer_statement_customer_id').val() || '';
                    d.customer_type = $('#list_customer_statement_customer_type').val() || 'all';
                    d.search_term = $('#list_customer_statement_search').val() || '';
                    d.start_date = statementRange.start;
                    d.end_date = statementRange.end;
                    d.printed_start = printedRange.start;
                    d.printed_end = printedRange.end;
                }
            },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' },
                { data: 'print_date', name: 'vat_customer_statements.print_date' },
                { data: 'date_from', name: 'vat_customer_statements.date_from' },
                { data: 'date_to', name: 'vat_customer_statements.date_to' },
                { data: 'customer', name: 'contacts.name' },
                { data: 'statement_no', name: 'vat_customer_statements.statement_no' },
                { data: 'amount', searchable: false, orderable: false, className: 'text-right' },
                { data: 'payment_status', searchable: false, orderable: false, className: 'text-center' },
                { data: 'username', name: 'added_user.username' },
                { data: 'description', searchable: false, orderable: false }
            ],
            columnDefs: [
                { targets: 0, width: '90px' },
                { targets: [1, 2, 3], width: '95px' },
                { targets: 4, width: '190px' },
                { targets: 5, width: '130px' },
                { targets: 6, width: '125px' },
                { targets: 7, width: '90px' },
                { targets: 8, width: '110px' },
                { targets: 9, width: '180px' }
            ],
            drawCallback: function () {
                if (typeof sum_table_col === 'function') {
                    $('#grand_total').html(__number_f(sum_table_col($('#customer_statement_list_table'), 'amount')));
                }
                if (typeof __currency_convert_recursively === 'function') {
                    __currency_convert_recursively($('#customer_statement_list_table'));
                }
            }
        });
        window.customer_statement_list_table = statementListTable;
    }

    function initPrefixesTable() {
        if (!$('#prefixes_table').length) { return; }
        if ($.fn.dataTable.isDataTable('#prefixes_table')) {
            prefixesTable = $('#prefixes_table').DataTable();
            window.prefixes_table = prefixesTable;
            return;
        }

        prefixesTable = $('#prefixes_table').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: @json(action('\Modules\Vat\Http\Controllers\VatStatementPrefixController@index')),
                cache: false
            },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'prefix', name: 'prefix' },
                { data: 'starting_no', name: 'starting_no' },
                { data: 'user_created', name: 'users.username' },
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' }
            ]
        });
        window.prefixes_table = prefixesTable;
    }

    function initLogosTable() {
        if (!$('#logos_table').length) { return; }
        if ($.fn.dataTable.isDataTable('#logos_table')) {
            logosTable = $('#logos_table').DataTable();
            window.logos_table = logosTable;
            return;
        }

        logosTable = $('#logos_table').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: {
                url: @json(action('\Modules\Vat\Http\Controllers\VatStatementLogoController@index')),
                cache: false
            },
            @include('layouts.partials.datatable_export_button')
            columns: [
                { data: 'action', searchable: false, orderable: false, className: 'notexport noColvis' },
                { data: 'statement_date_display', name: 'created_at' },
                { data: 'logo', name: 'logo', searchable: false, orderable: false },
                { data: 'image_name', name: 'image_name' },
                { data: 'alignment', name: 'alignment' },
                { data: 'text_position', name: 'text_position' },
                { data: 'statement_note', name: 'statement_note' },
                { data: 'username', name: 'username' }
            ]
        });
        window.logos_table = logosTable;
    }

    function openAjaxModal(url, containerSelector) {
        var $modal = $(containerSelector || '.fuel_tank_modal');
        if (!$modal.length) { return; }
        $modal.empty();
        $.ajax({ url: url, method: 'GET', dataType: 'html', cache: false })
            .done(function (html) {
                $modal.html(html).modal({ backdrop: true, keyboard: true, show: true });
                initSelect2($modal);
            })
            .fail(notifyAjaxError);
    }

    $(document).on('click.vatAjaxModal', '.vat-ajax-modal-trigger', function (event) {
        event.preventDefault();
        openAjaxModal($(this).data('href') || $(this).attr('href'), $(this).data('container'));
    });

    $(document).on('submit.vatAjaxForm', 'form.vat-modal-ajax-form', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
        var $form = $(this);
        if ($form.data('submitting')) { return; }
        if (this.checkValidity && !this.checkValidity()) {
            this.reportValidity();
            return;
        }

        $form.data('submitting', true);
        var $button = $form.find('button[type="submit"]');
        var original = $button.html();
        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function (result) {
            if (!result.success) {
                toastr.error(result.msg || @json(__('messages.something_went_wrong')));
                return;
            }
            toastr.success(result.msg);
            var tableSelector = $form.attr('data-vat-reload-table');
            $form.closest('.modal').modal('hide');
            reloadDataTable(tableSelector, false);
            if (tableSelector === '#prefixes_table') {
                window.loadStatements();
            }
        }).fail(notifyAjaxError).always(function () {
            $form.data('submitting', false);
            $button.prop('disabled', false).html(original);
        });
    });

    $(document).on('click.vatDelete', 'a.delete_task, a.delete_customer_statement', function (event) {
        event.preventDefault();
        var href = $(this).data('href');
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_brand,
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (confirmed) {
            if (!confirmed) { return; }
            $.ajax({
                url: href,
                method: 'DELETE',
                dataType: 'json',
                data: { _token: csrfToken }
            }).done(function (result) {
                result.success ? toastr.success(result.msg) : toastr.error(result.msg);
                reloadDataTable('#prefixes_table', false);
                reloadDataTable('#customer_statement_list_table', false);
                reloadDataTable('#logos_table', false);
                window.loadStatements();
            }).fail(notifyAjaxError);
        });
    });

    $(document).on('click.vatTransactionDelete', 'a.customer-statement-delete', function (event) {
        event.preventDefault();
        var href = $(this).attr('href');
        var id = $(this).data('id');
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_customer_statement,
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (confirmed) {
            if (!confirmed) { return; }
            $.ajax({
                url: href,
                method: 'DELETE',
                dataType: 'json',
                data: { id: id, _token: csrfToken }
            }).done(function (result) {
                result.success ? toastr.success(result.msg) : toastr.error(result.msg);
                window.loadStatements();
            }).fail(notifyAjaxError);
        });
    });

    $(document).on('click.vatStatementPayment', 'a.pay_statement_amount', function (event) {
        event.preventDefault();
        openAjaxModal($(this).data('href') || $(this).attr('href'), '.pay_contact_due_modal');
    });

    function loadPrintable(url, callback) {
        $.ajax({ url: url, method: 'GET', dataType: 'html', cache: false })
            .done(function (html) {
                $('#report_print_div').html(html);
                callback(html);
            })
            .fail(notifyAjaxError);
    }

    $(document).on('click.vatPrint', '.reprint_statement', function (event) {
        event.preventDefault();
        loadPrintable($(this).data('href'), function () { $('#report_print_div').printThis(); });
    });
    $(document).on('click.vatPdf', '.pdf_statement', function (event) {
        event.preventDefault();
        loadPrintable($(this).data('href'), function (html) { generatePdf(html, 'pdf'); });
    });
    $(document).on('click.vatEmail', '.email_statement', function (event) {
        event.preventDefault();
        loadPrintable($(this).data('href'), function (html) { generatePdf(html, 'email'); });
    });
    $(document).on('click.vat126', '.print_126_statement', function (event) {
        event.preventDefault();
        loadPrintable($(this).data('href'), function () { $('#report_print_div').printThis(); });
    });

    window.generatePdf = function (html, actionName) {
        $.ajax({
            url: @json(action('\Modules\Vat\Http\Controllers\CustomerStatementController@downloadPdf')),
            method: 'POST',
            dataType: 'json',
            data: { html: html, _token: csrfToken }
        }).done(function (result) {
            if (actionName === 'email') {
                var mailto = 'mailto:?subject=' + encodeURIComponent('VAT Statement')
                    + '&body=' + encodeURIComponent('Please find the VAT Statement PDF at: ' + result.path);
                window.open(mailto, '_blank');
                return;
            }
            var link = document.createElement('a');
            link.href = result.path;
            link.download = 'vat-statement.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }).fail(notifyAjaxError);
    };

    window.saveDiv = function () {
        var filters = getStatementPreviewFilters();
        var logo = $('#logo').val();
        if (!filters.customer_id) {
            toastr.error(@json(__('lang_v1.please_select') . ' ' . __('contact.customer')));
            return false;
        }
        if (!logo) {
            toastr.error(@json(__('lang_v1.please_select') . ' ' . __('lang_v1.customer_statement_logos')));
            return false;
        }

        $.ajax({
            url: '/vat-module/customer-statement',
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: {
                _token: csrfToken,
                customer_id: filters.customer_id,
                start_date: filters.start_date,
                end_date: filters.end_date,
                statement_no: $('#statement_no').val(),
                logo: logo,
                price_adjustment: filters.price_adjustment
            }
        }).done(function (result) {
            if (!result.success) {
                toastr.error(result.msg || @json(__('messages.something_went_wrong')));
                return;
            }
            toastr.success(result.msg);
            reloadDataTable('#customer_statement_list_table', false);
            reloadDataTable('#prefixes_table', false);
            window.loadStatements();
        }).fail(notifyAjaxError);

        return false;
    };

    $(function () {
        initSelect2($('.vat-statement-page'));

        initDateRange(
            '#customer_statement_date_range',
            moment().startOf('month'),
            moment().endOf('month'),
            scheduleStatementReload
        );
        initDateRange(
            '#printed_list_customer_statement_date_range',
            moment().startOf('year'),
            moment().endOf('year'),
            function () { reloadDataTable('#customer_statement_list_table', false); }
        );
        initDateRange(
            '#list_customer_statement_date_range',
            moment().startOf('year'),
            moment().endOf('year'),
            function () { reloadDataTable('#customer_statement_list_table', false); }
        );

        initStatementTable();
        initStatementListTable();
        initPrefixesTable();
        initLogosTable();

        $('#customer_statement_customer_id, #customer_statement_customer_type, #customer_statement_reference, #price_adjustment')
            .off('.vatPreviewFilter')
            .on('change.vatPreviewFilter input.vatPreviewFilter', scheduleStatementReload);
        $('#customer_statement_search')
            .off('.vatPreviewSearch')
            .on('input.vatPreviewSearch', scheduleStatementReload);

        $('#list_customer_statement_location_id, #list_customer_statement_customer_id, #list_customer_statement_customer_type')
            .off('.vatListFilter')
            .on('change.vatListFilter', function () { reloadDataTable('#customer_statement_list_table', false); });
        $('#list_customer_statement_search')
            .off('.vatListSearch')
            .on('input.vatListSearch', function () {
                window.clearTimeout(filterTimer);
                filterTimer = window.setTimeout(function () {
                    reloadDataTable('#customer_statement_list_table', false);
                }, 250);
            });

        // S584: the active section is selected by the server through ?tab=.
        // Adjust only the visible section after its DataTable has been created.
        window.setTimeout(function () {
            $('#vat_statement_main_tab_content > .tab-pane.active').find('table').each(function () {
                if ($.fn.dataTable && $.fn.dataTable.isDataTable(this)) {
                    var table = $(this).DataTable();
                    table.columns.adjust();

                    if ($(this).attr('id') === 'prefixes_table' && table.ajax) {
                        table.ajax.reload(null, false);
                    }
                }
            });
        }, 0);

        // Re-check transaction locks after returning from an invoice/statement
        // delete page through the browser Back button (including bfcache).
        $(window).off('pageshow.vatStatement').on('pageshow.vatStatement', function () {
            reloadDataTable('#prefixes_table', false);
            reloadDataTable('#customer_statement_list_table', false);
        });

        $('.fuel_tank_modal, .customer_statement_modal, .pay_contact_due_modal')
            .off('hidden.bs.modal.vatCleanup')
            .on('hidden.bs.modal.vatCleanup', function () {
                $(this).empty();
            });

        window.loadStatements();
    });
})(jQuery);

function roundingOnclick() {
    var totalNode = document.getElementById('total-invoice-amount');
    var beforeNode = document.getElementById('amount-before-rounding-off');
    if (!totalNode || !beforeNode) { return; }
    var total = parseFloat((totalNode.innerText || '').replace(/,/g, '')) || 0;
    var before = parseFloat((beforeNode.innerText || '').replace(/,/g, '')) || 0;
    var field = document.getElementById('price_adjustment');
    if (field) {
        field.value = (total - before).toFixed(8);
        field.focus();
        $(field).trigger('change');
    }
}
</script>
@endsection
